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
     * Suggest a category for an entry based on its description.
     *
     * @param  string  $description  Entry description
     * @param  string  $type         'in' | 'out'
     * @param  array   $categories   Existing category names to prefer
     * @return array|null            ['category' => string, 'confidence' => float] or null
     */
    public function suggestCategory(string $description, string $type = 'out', array $categories = []): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $typeLabel = $type === 'in' ? 'income/money received' : 'expense/money spent';
        $categoryList = !empty($categories)
            ? 'Prefer one of these existing categories (exact spelling): ' . implode(', ', $categories) . '. If none fit, suggest a short, common business category.'
            : 'Suggest a short, common business category name (2–3 words max).';

        $prompt = <<<PROMPT
Categorize this business transaction.

Description: "{$description}"
Transaction type: {$typeLabel}
{$categoryList}

Return ONLY a valid JSON object:
{"category": "category name", "confidence": 0.0 to 1.0}

No explanation, no markdown fences.
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(10)->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => 60,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => $prompt,
                ]],
            ]);

            if (!$response->successful()) {
                return null;
            }

            $content      = $response->json('content.0.text', '');
            $inputTokens  = $response->json('usage.input_tokens', 0);
            $outputTokens = $response->json('usage.output_tokens', 0);
            $cost = ($inputTokens * $this->inputCostPerToken) + ($outputTokens * $this->outputCostPerToken);

            AiUsageLog::create([
                'user_id'    => auth()->id(),
                'type'       => 'categorize',
                'tokens_in'  => $inputTokens,
                'tokens_out' => $outputTokens,
                'cost_usd'   => $cost,
            ]);

            if (preg_match('/\{.*?\}/s', $content, $matches)) {
                $data = json_decode($matches[0], true);
                if (is_array($data) && !empty($data['category'])) {
                    return [
                        'category'   => mb_substr(trim($data['category']), 0, 100),
                        'confidence' => isset($data['confidence']) ? (float) $data['confidence'] : 0.8,
                    ];
                }
            }
        } catch (\Exception $e) {
            Log::warning('AI categorization failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Generate AI cash flow insights for a book.
     *
     * @param  array       $current   Aggregated data for the current book
     * @param  array|null  $previous  Aggregated data for the previous book (optional)
     * @param  string      $currency  ISO 4217 currency code
     * @param  int         $recurringCount  Active recurring entries count
     * @return array|null  { sentiment, sentiment_reason, bullets[], tip } or null on failure
     */
    public function generateInsights(
        array $current,
        ?array $previous = null,
        string $currency = 'USD',
        int $recurringCount = 0
    ): ?array {
        if (empty($this->apiKey)) {
            return null;
        }

        $currentBlock  = $this->formatBookBlock('Current book', $current, $currency);
        $previousBlock = $previous
            ? $this->formatBookBlock('Previous book (for comparison)', $previous, $currency)
            : 'No previous book data available.';

        $prompt = <<<PROMPT
You are a financial analyst for a small business. Analyze the cash flow data below and return concise insights.

{$currentBlock}

{$previousBlock}

Active recurring entries: {$recurringCount}
Currency: {$currency}

Return ONLY a valid JSON object with exactly these fields:
{
  "sentiment": "healthy" | "watch" | "concern",
  "sentiment_reason": "one short phrase, max 8 words",
  "bullets": ["insight 1", "insight 2", "insight 3"],
  "tip": "one concrete actionable suggestion, max 18 words"
}

Rules:
- sentiment: healthy = positive net + stable or improving; watch = tight margins or slight decline; concern = negative net or sharp decline
- If previous book data is available, bullet 1 MUST compare current vs previous with a specific % or absolute change
- bullets: specific numbers only — no vague language like "significant" or "notable". Max 20 words each.
- tip: one actionable next step the business owner can take today. Not generic advice.
- Write amounts as plain numbers without currency symbols
- No markdown, no explanation outside the JSON

Return ONLY the JSON object.
PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $this->apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(25)->post('https://api.anthropic.com/v1/messages', [
                'model'      => $this->model,
                'max_tokens' => 380,
                'messages'   => [[
                    'role'    => 'user',
                    'content' => $prompt,
                ]],
            ]);

            if (! $response->successful()) {
                Log::warning('AI insights API error', [
                    'status'  => $response->status(),
                    'message' => $response->json('error.message', 'Unknown'),
                ]);
                return null;
            }

            $content      = $response->json('content.0.text', '');
            $inputTokens  = $response->json('usage.input_tokens', 0);
            $outputTokens = $response->json('usage.output_tokens', 0);
            $cost = ($inputTokens * $this->inputCostPerToken) + ($outputTokens * $this->outputCostPerToken);

            AiUsageLog::create([
                'user_id'    => auth()->id(),
                'type'       => 'insights',
                'tokens_in'  => $inputTokens,
                'tokens_out' => $outputTokens,
                'cost_usd'   => $cost,
            ]);

            if (preg_match('/\{.*?\}/s', $content, $matches)) {
                $data = json_decode($matches[0], true);
                if (is_array($data)) {
                    return $this->sanitiseInsights($data);
                }
            }
        } catch (\Exception $e) {
            Log::warning('AI insights generation failed', ['error' => $e->getMessage()]);
        }

        return null;
    }

    /**
     * Format a book's aggregated data as a readable prompt block.
     */
    private function formatBookBlock(string $label, array $data, string $currency): string
    {
        $lines = ["{$label} — {$data['name']}:"];
        $lines[] = "  Period: {$data['period']}";
        $lines[] = "  Total Cash In: {$data['totalIn']} {$currency}";
        $lines[] = "  Total Cash Out: {$data['totalOut']} {$currency}";
        $lines[] = "  Net Balance: {$data['balance']} {$currency}";
        $lines[] = "  Entry count: {$data['entryCount']}";

        if (! empty($data['topCategoriesOut'])) {
            $lines[] = '  Top expense categories: ' . implode(', ', $data['topCategoriesOut']);
        }
        if (! empty($data['topCategoriesIn'])) {
            $lines[] = '  Top income categories: ' . implode(', ', $data['topCategoriesIn']);
        }

        return implode("\n", $lines);
    }

    /**
     * Sanitise and validate the insights JSON from the API.
     */
    private function sanitiseInsights(array $data): array
    {
        $out = [];

        // Sentiment — strict whitelist
        $validSentiments = ['healthy', 'watch', 'concern'];
        $out['sentiment'] = in_array($data['sentiment'] ?? '', $validSentiments)
            ? $data['sentiment']
            : 'watch';

        // Sentiment reason
        if (! empty($data['sentiment_reason']) && is_string($data['sentiment_reason'])) {
            $out['sentiment_reason'] = mb_substr(trim($data['sentiment_reason']), 0, 100);
        }

        // Bullets — exactly 3, plain strings only
        if (isset($data['bullets']) && is_array($data['bullets'])) {
            $out['bullets'] = array_values(
                array_map(
                    fn ($b) => mb_substr(trim((string) $b), 0, 220),
                    array_filter(array_slice($data['bullets'], 0, 3), fn ($b) => is_string($b) || is_numeric($b))
                )
            );
        }

        // Tip
        if (! empty($data['tip']) && is_string($data['tip'])) {
            $out['tip'] = mb_substr(trim($data['tip']), 0, 220);
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
