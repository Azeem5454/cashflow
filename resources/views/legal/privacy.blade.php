@extends('legal._layout')

@section('title', 'Privacy Policy')
@section('description', 'Privacy Policy for ' . config('app.name', 'TheCashFox'))
@section('updated', date('F Y'))

@section('content')
    @php $app = config('app.name', 'TheCashFox'); @endphp

    <p>Your privacy matters. This Privacy Policy explains what personal information {{ $app }} collects, how we use it, how we protect it, and the choices you have. It applies to our website and all services operated under {{ $app }} (the "Service").</p>

    <h2>1. Information we collect</h2>

    <h3>Information you provide</h3>
    <ul>
        <li><strong>Account information</strong> — name, email address, and password (stored as a cryptographic hash, never in plain text)</li>
        <li><strong>Business and financial data</strong> — business names, book titles, entry amounts, categories, descriptions, receipts, comments, and attachments you upload</li>
        <li><strong>Team and collaboration data</strong> — invitations you send, team member emails, and role assignments</li>
        <li><strong>Payment information</strong> — billing details are collected and stored directly by Stripe; we receive only a customer identifier and subscription status. <strong>We never see or store your card number.</strong></li>
        <li><strong>Support correspondence</strong> — messages you send us</li>
    </ul>

    <h3>Information collected automatically</h3>
    <ul>
        <li><strong>Usage data</strong> — pages visited, features used, device type, browser, operating system, referring URL, timestamps</li>
        <li><strong>Log data</strong> — IP address, access times, and errors encountered (used for security, debugging, and abuse prevention)</li>
        <li><strong>Cookies and similar technologies</strong> — used to keep you signed in, remember preferences (e.g. theme), and measure usage. You can disable cookies in your browser but some features may stop working.</li>
    </ul>

    <h2>2. How we use your information</h2>
    <ul>
        <li>To provide, operate, and maintain the Service</li>
        <li>To authenticate you and protect your account</li>
        <li>To process payments and manage subscriptions (via Stripe)</li>
        <li>To send transactional emails — account verification, password reset, team invitations, report emails you have opted into</li>
        <li>To improve the Service, fix bugs, and add features</li>
        <li>To detect and prevent fraud, abuse, and security incidents</li>
        <li>To comply with legal obligations</li>
    </ul>
    <p>We do <strong>not</strong> sell your personal information, and we do not use your financial data to train AI models.</p>

    <h2>3. AI features and third-party processing</h2>
    <p>When you use AI-assisted features (receipt scanning, auto-categorization, cash flow insights) your input — the image or aggregated numbers — is sent to our AI provider (Anthropic) for processing. We do not send raw entry descriptions, customer names, or any data that is not strictly required for the requested feature. Our provider is bound by its own data-processing terms and does not retain content for training.</p>

    <h2>4. Sharing and disclosure</h2>
    <p>We share your information only with:</p>
    <ul>
        <li><strong>Service providers</strong> who help us operate {{ $app }} — hosting (e.g. Railway), payments (Stripe), email delivery (e.g. Mailgun or Amazon SES), error monitoring, and AI (Anthropic). Each is bound by contract to protect your data and use it only for the services they provide us.</li>
        <li><strong>Team members you invite</strong> — if you invite another user to a business, they will see the business data you share access to, scoped by their role.</li>
        <li><strong>Legal requests</strong> — if required by valid law, court order, or subpoena, or to protect rights, property, or safety.</li>
        <li><strong>Successors</strong> — in a merger, acquisition, or sale of assets, your data may transfer to the successor entity, subject to this Policy.</li>
    </ul>

    <h2>5. Data retention</h2>
    <p>We retain your account and business data for as long as your account is active. If you close your account, we will delete or anonymise your personal data within a reasonable period, unless we are required to retain it to comply with legal, tax, or accounting obligations or to resolve disputes.</p>

    <h2>6. Security</h2>
    <p>We use industry-standard measures to protect your information, including HTTPS encryption in transit, encrypted passwords, access controls, and regular security review. No system is perfectly secure, but we work hard to keep your data safe and will notify affected users promptly of any confirmed data breach as required by law.</p>

    <h2>7. Your rights</h2>
    <p>Depending on where you live, you may have the right to:</p>
    <ul>
        <li>Access the personal information we hold about you</li>
        <li>Correct inaccurate information</li>
        <li>Delete your account and associated personal data</li>
        <li>Export a copy of your data in a portable format</li>
        <li>Object to or restrict certain processing</li>
        <li>Withdraw consent where processing is based on consent</li>
    </ul>
    <p>You can exercise most of these rights directly inside the Service (profile settings, account deletion, data export). For anything else, email us.</p>

    <h2>8. Children</h2>
    <p>{{ $app }} is not directed at children under 16 and we do not knowingly collect personal information from them. If you believe a child has given us information, contact us and we will delete it.</p>

    <h2>9. International data transfers</h2>
    <p>Your information may be processed in countries other than the one you live in. By using the Service you consent to the transfer of your information to those countries, subject to appropriate safeguards.</p>

    <h2>10. Cookies</h2>
    <p>We use a small number of strictly necessary cookies (session, authentication, theme preference, CSRF protection). We do not use advertising or cross-site tracking cookies. If we add analytics or marketing cookies in the future we will update this Policy and provide appropriate notice.</p>

    <h2>11. Changes to this Policy</h2>
    <p>We may update this Policy from time to time. When we make material changes we will notify users by email or through the Service. The "last updated" date at the top of this page reflects the most recent revision.</p>

    <h2>12. Contact</h2>
    @php $support = config('app.support_email') ?: 'hello@' . (parse_url(config('app.url', 'https://cashflow.app'), PHP_URL_HOST) ?: 'cashflow.app'); @endphp
    <p>Questions about this Policy, or want to exercise your rights? Email <a href="mailto:{{ $support }}">{{ $support }}</a>.</p>

    <div class="callout">
        This Policy is a general template. For production use with real customers, and especially if you operate in the EU, UK, California, or other jurisdictions with strict data-protection laws (GDPR, UK GDPR, CCPA), have a qualified lawyer tailor it to your data flows.
    </div>
@endsection
