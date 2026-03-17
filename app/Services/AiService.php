<?php

namespace App\Services;

use App\Models\AiUsageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected string $apiKey;

    // Haiku: cheapest model with vision support
    protected string $model = 'claude-haiku-4-5-20251001';

    // Haiku pricing (per token)
    protected float $inputCostPerToken  = 0.0000008;  // $0.80 / 1M
    protected float $outputCostPerToken = 0.000004;   // $4.00 / 1M

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key', '');
    }

    /**
     * Extract structured entry data from a receipt / invoice image.
     *
     * @param  string   $imagePath   Absolute path to the temp file
     * @param  string   $mimeType    e.g. image/jpeg
     * @param  string   $currency    Business currency code shown to AI for context
     * @param  array    $categories  Existing category names to guide matching
     * @return array|null            Structured data or null on failure
     */
    public function extractFromReceipt(
        string $imagePath,
        string $mimeType,
        string $currency = 'USD',
        array $categories = []
    ): ?array {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY is not configured.');
        }

        $imageData = base64_encode(file_get_contents($imagePath));

        $categoryHint = !empty($categories)
            ? 'Prefer matching one of these existing categories (exact spelling): ' . implode(', ', $categories) . '. If none fit well, suggest your own.'
            : 'Suggest a short, common business category name.';

        $prompt = <<<PROMPT
Extract information from this receipt or invoice image.

Book currency: {$currency}
{$categoryHint}

Return ONLY a valid JSON object with these fields. Omit any field you cannot determine with confidence:
{
  "type": "in" or "out"  (purchases/expenses = "out"; payments received/sales = "in"),
  "amount": number (digits and decimal point only, no currency symbols or commas),
  "receipt_currency": "ISO 4217 currency code detected on the receipt (e.g. USD, PKR, GBP, EUR). Only include if you can clearly identify it.",
  "date": "YYYY-MM-DD",
  "description": "concise what this transaction is (max 60 chars)",
  "category": "category name",
  "payment_mode": one of: Cash, Card, Bank Transfer, Cheque, Online
}

Return ONLY the JSON object. No explanation, no markdown fences.
PROMPT;

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->model,
            'max_tokens' => 400,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mimeType,
                                'data'       => $imageData,
                            ],
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
        ]);

        if (!$response->successful()) {
            // Log sanitized details only — never log the full response body
            $apiError = $response->json('error.message', 'Unknown error');
            Log::error('Claude API error', [
                'status'  => $response->status(),
                'message' => $apiError,
            ]);
            if (str_contains($apiError, 'credit balance')) {
                throw new \RuntimeException('AI service is not configured. Please contact support.');
            }
            throw new \RuntimeException('AI service error: ' . $response->status());
        }

        $content    = $response->json('content.0.text', '');
        $inputTokens  = $response->json('usage.input_tokens', 0);
        $outputTokens = $response->json('usage.output_tokens', 0);
        $cost = ($inputTokens * $this->inputCostPerToken) + ($outputTokens * $this->outputCostPerToken);

        // Log usage for analytics and billing protection
        AiUsageLog::create([
            'user_id'    => auth()->id(),
            'type'       => 'ocr',
            'tokens_in'  => $inputTokens,
            'tokens_out' => $outputTokens,
            'cost_usd'   => $cost,
        ]);

        // Parse JSON from response — strict whitelist approach
        if (preg_match('/\{.*?\}/s', $content, $matches)) {
            $data = json_decode($matches[0], true);
            if (is_array($data)) {
                // Whitelist: only process known keys — drop everything else
                $allowed = array_flip(['type', 'amount', 'receipt_currency', 'date', 'description', 'category', 'payment_mode']);
                $data = array_intersect_key($data, $allowed);
                return $this->sanitise($data);
            }
        }

        return null;
    }

    /**
     * Sanitise and normalise extracted fields before handing to Livewire.
     */
    protected function sanitise(array $data): array
    {
        $out = [];

        if (isset($data['type']) && in_array($data['type'], ['in', 'out'])) {
            $out['type'] = $data['type'];
        }

        if (isset($data['amount']) && is_numeric($data['amount']) && $data['amount'] > 0) {
            $out['amount'] = (string) round((float) $data['amount'], 2);
        }

        if (!empty($data['date'])) {
            try {
                $out['date'] = \Carbon\Carbon::parse($data['date'])->format('Y-m-d');
            } catch (\Exception) {
                // skip unparseable dates
            }
        }

        if (!empty($data['description'])) {
            $out['description'] = mb_substr(trim($data['description']), 0, 255);
        }

        if (!empty($data['category'])) {
            $out['category'] = mb_substr(trim($data['category']), 0, 100);
        }

        if (!empty($data['payment_mode'])) {
            $allowed = ['Cash', 'Card', 'Bank Transfer', 'Cheque', 'Online'];
            $mode = trim($data['payment_mode']);
            if (in_array($mode, $allowed)) {
                $out['payment_mode'] = $mode;
            }
        }

        if (!empty($data['receipt_currency'])) {
            $out['receipt_currency'] = strtoupper(trim($data['receipt_currency']));
        }

        return $out;
    }

    /**
     * Convert an amount from one currency to another using open.er-api.com (free, 1500 req/month).
     * Returns ['converted_amount' => float, 'rate' => float] or null on failure.
     */
    public function convertCurrency(float $amount, string $from, string $to): ?array
    {
        $from = strtoupper(preg_replace('/[^A-Za-z]/', '', $from));
        $to   = strtoupper(preg_replace('/[^A-Za-z]/', '', $to));

        // Whitelist: only allow valid ISO 4217 currency codes (3 letters)
        if (!preg_match('/^[A-Z]{3}$/', $from) || !preg_match('/^[A-Z]{3}$/', $to)) {
            return null;
        }

        if ($from === $to) {
            return ['converted_amount' => $amount, 'rate' => 1.0];
        }

        try {
            $response = Http::timeout(5)
                ->get("https://open.er-api.com/v6/latest/{$from}");

            if ($response->successful() && $response->json('result') === 'success') {
                $rate = $response->json("rates.{$to}");
                if ($rate) {
                    return [
                        'converted_amount' => round($amount * $rate, 2),
                        'rate'             => round($rate, 4),
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('Currency conversion failed', ['error' => $e->getMessage()]);
        }

        return null;
    }
}
