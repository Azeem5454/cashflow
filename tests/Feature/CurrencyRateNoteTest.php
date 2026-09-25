<?php

namespace Tests\Feature;

use App\Services\AiService;
use Tests\TestCase;

/**
 * The conversion note under a scanned receipt used to format the rate to two
 * decimals, so every currency weaker than the book's read "1 PKR = 0.00 USD".
 */
class CurrencyRateNoteTest extends TestCase
{
    public static function rates(): array
    {
        return [
            'weak currency keeps its precision' => [0.0036,   'PKR', '1 PKR = 0.0036 USD'],
            'very weak currency'                => [0.000061, 'IDR', '1 IDR = 0.000061 USD'],
            'near parity'                       => [1.0784,   'EUR', '1 EUR = 1.0784 USD'],
            'trailing zeros are trimmed'        => [1.3400,   'GBP', '1 GBP = 1.34 USD'],
            'identity'                          => [1.0,      'USD', '1 USD = 1 USD'],
        ];
    }

    /** @dataProvider rates */
    public function test_it_scales_precision_to_the_rate(float $rate, string $from, string $expected): void
    {
        $this->assertSame($expected, AiService::rateNote($rate, $from, 'USD'));
    }

    public function test_it_never_renders_a_rate_as_zero(): void
    {
        foreach ([0.0036, 0.00012, 0.0000004, 0.5, 900.0] as $rate) {
            $note = AiService::rateNote($rate, 'XXX', 'USD');
            $this->assertStringNotContainsString('= 0.00 ', $note, "Rate {$rate} rendered as zero: {$note}");
        }
    }
}
