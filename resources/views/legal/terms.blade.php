@extends('legal._layout')

@section('title', 'Terms of Service')
@section('description', 'Terms of Service for ' . config('app.name', 'CashFlow'))
@section('updated', date('F Y'))

@section('content')
    @php $app = config('app.name', 'CashFlow'); @endphp

    <p>These Terms of Service ("Terms") govern your access to and use of {{ $app }} (the "Service"). By creating an account, accessing, or using the Service you agree to be bound by these Terms. If you do not agree, do not use the Service.</p>

    <h2>1. Account registration</h2>
    <p>You must provide accurate, current, and complete information when you register an account and keep your account information updated. You are responsible for maintaining the confidentiality of your password and for all activity under your account. Notify us immediately of any unauthorised access or security breach.</p>

    <h2>2. Use of the Service</h2>
    <p>You may use the Service only in compliance with these Terms and all applicable laws. You agree not to:</p>
    <ul>
        <li>Reverse engineer, decompile, or attempt to extract source code from the Service</li>
        <li>Use the Service to store or transmit unlawful, defamatory, infringing, obscene, or fraudulent content</li>
        <li>Attempt to gain unauthorised access to any account, system, or data that is not yours</li>
        <li>Interfere with or disrupt the integrity, performance, or security of the Service</li>
        <li>Use automated means (bots, scrapers) to access or abuse the Service beyond our documented APIs</li>
        <li>Resell, sublicense, or commercially exploit the Service without our written permission</li>
    </ul>

    <h2>3. Your data</h2>
    <p>You retain ownership of all financial records, receipts, team information, and other data you enter into the Service ("Your Data"). You grant {{ $app }} a limited licence to host, copy, process, and display Your Data solely to provide and improve the Service. We will not sell Your Data to third parties.</p>

    <h2>4. Plans, billing, and cancellation</h2>
    <p>The Service is offered on a Free plan and paid Pro plan. Paid subscriptions are billed monthly in advance via our payment processor (Stripe). You may cancel at any time from your billing settings; cancellation takes effect at the end of the current billing period, and you retain access to Pro features until then. We do not offer refunds for partial billing periods except where required by law.</p>
    <p>Prices and features may change; we will give reasonable notice before any change that affects active subscribers.</p>

    <h2>5. AI features</h2>
    <p>The Service includes AI-assisted features (receipt scanning, auto-categorization, cash flow insights). AI outputs may contain errors and should be reviewed before relying on them for financial, tax, or legal decisions. {{ $app }} does not guarantee the accuracy of AI-generated content and is not responsible for decisions made based on it.</p>

    <h2>6. Third-party services</h2>
    <p>The Service integrates with third-party providers including Stripe (payments) and Anthropic (AI). Your use of those integrations is also subject to the respective provider's terms and privacy policies.</p>

    <h2>7. Availability and changes</h2>
    <p>We aim to keep the Service available but do not guarantee uninterrupted access. We may modify, suspend, or discontinue any part of the Service at any time. We will give reasonable notice for material changes that reduce functionality available to paying customers.</p>

    <h2>8. Termination</h2>
    <p>You may close your account at any time from your profile settings. We may suspend or terminate your account for violation of these Terms, fraudulent activity, abusive behaviour, or non-payment. On termination, access to the Service and Your Data through the Service will end; export your data before closing if you wish to retain it.</p>

    <h2>9. Disclaimer of warranties</h2>
    <p>THE SERVICE IS PROVIDED "AS IS" AND "AS AVAILABLE" WITHOUT WARRANTIES OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE, AND NON-INFRINGEMENT. {{ $app }} IS NOT A SUBSTITUTE FOR PROFESSIONAL ACCOUNTING, TAX, OR LEGAL ADVICE.</p>

    <h2>10. Limitation of liability</h2>
    <p>TO THE MAXIMUM EXTENT PERMITTED BY LAW, {{ $app }} AND ITS AFFILIATES WILL NOT BE LIABLE FOR ANY INDIRECT, INCIDENTAL, SPECIAL, CONSEQUENTIAL, OR PUNITIVE DAMAGES, OR ANY LOSS OF PROFITS, REVENUE, DATA, OR GOODWILL, ARISING OUT OF OR RELATED TO YOUR USE OF THE SERVICE. OUR TOTAL LIABILITY FOR ANY CLAIM WILL NOT EXCEED THE AMOUNTS YOU PAID US IN THE TWELVE MONTHS PRECEDING THE EVENT GIVING RISE TO THE CLAIM.</p>

    <h2>11. Indemnification</h2>
    <p>You agree to indemnify and hold {{ $app }} harmless from any claims, damages, or expenses arising from your breach of these Terms, your violation of any law, or your infringement of any third-party rights.</p>

    <h2>12. Changes to these Terms</h2>
    <p>We may update these Terms from time to time. When we make material changes we will notify users by email or through the Service. Continued use after changes take effect constitutes acceptance of the revised Terms.</p>

    <h2>13. Governing law</h2>
    <p>These Terms are governed by the laws of the jurisdiction in which {{ $app }} operates, without regard to conflict-of-law principles. Disputes will be resolved in the competent courts of that jurisdiction.</p>

    <h2>14. Contact</h2>
    <p>Questions about these Terms? Email <a href="mailto:{{ \App\Helpers\Setting::get('app.support_email', 'hello@' . parse_url(config('app.url', 'https://cashflow.app'), PHP_URL_HOST)) }}">{{ \App\Helpers\Setting::get('app.support_email', 'hello@' . parse_url(config('app.url', 'https://cashflow.app'), PHP_URL_HOST)) }}</a>.</p>

    <div class="callout">
        These Terms are provided as a general template and may not cover every scenario or jurisdiction. For production use, have a qualified lawyer review them against your specific business, location, and user base.
    </div>
@endsection
