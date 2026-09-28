<?php

namespace App\Support;

/**
 * Builds schema.org JSON-LD as ready-to-echo strings.
 *
 * This lives in a plain PHP file for one reason: Laravel 11 added a
 * `@context` Blade directive, and Blade's compiler is a text preprocessor
 * that runs before any PHP does. Writing "@context" inside a .blade.php
 * file — even as an array key in a quoted string inside a @php block —
 * gets compiled into `$__contextArgs = []; ...` and the JSON ships with a
 * block of PHP source where the key should be.
 *
 * It fails silently. The JSON still parses, the page still renders, and the
 * markup is simply ignored by every crawler that reads it. The landing
 * page's FAQPage block shipped that way for months before anyone noticed.
 *
 * So: no "@context" literal in any Blade template. Build it here, echo the
 * string there.
 */
final class JsonLd
{
    /** The key Blade would eat if this string appeared in a template. */
    private const CONTEXT = '@context';

    /**
     * What the company is, in plain words. Deliberately concrete and separate
     * from the marketing tagline: this is the text that has to distinguish us
     * from the other things called CashFox.
     */
    public const ORG_DESCRIPTION = 'Cash book and expense tracking software for small business '
        . 'owners, freelancers and their finance teams. Track cash in and cash out, scan '
        . 'receipts, and share books with a team.';

    /**
     * Encode one schema.org node, with the context prepended.
     *
     * @param  array<string, mixed>  $data
     */
    public static function encode(array $data): string
    {
        return (string) json_encode(
            [self::CONTEXT => 'https://schema.org'] + $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * The landing page's three nodes, keyed for readability in the template.
     *
     * @param  array<int, array{0: string, 1: string}>  $faqs
     * @return array<string, string>
     */
    public static function forLanding(
        string $appName,
        string $appUrl,
        string $description,
        string $image,
        string $proPrice,
        array $faqs,
    ): array {
        return [
            'software' => self::encode([
                '@type'               => 'SoftwareApplication',
                'name'                => $appName,
                'description'         => $description,
                'url'                 => $appUrl . '/',
                'image'               => $image,
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem'     => 'Web',
                'offers'              => [
                    ['@type' => 'Offer', 'price' => '0', 'priceCurrency' => 'USD', 'name' => 'Free'],
                    ['@type' => 'Offer', 'price' => $proPrice, 'priceCurrency' => 'USD', 'name' => 'Pro (monthly)'],
                ],
            ]),

            // "CashFox" is a crowded name — a rewards app, a budgeting app and
            // an AI tool all answer to it, and we do not appear in the first
            // ten results for it. These fields give Google something concrete
            // to tell the entities apart by.
            'organization' => self::encode([
                '@type'         => 'Organization',
                'name'          => $appName,
                'alternateName' => 'CashFox',
                'url'           => $appUrl . '/',
                'logo'          => $image,
                'description'   => self::ORG_DESCRIPTION,
            ]),

            'faq' => self::encode([
                '@type'      => 'FAQPage',
                'mainEntity' => array_map(fn (array $f) => [
                    '@type'          => 'Question',
                    'name'           => $f[0],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $f[1]],
                ], $faqs),
            ]),
        ];
    }
}
