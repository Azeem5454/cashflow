@extends('legal._layout')

@section('title', 'Privacy Policy')
@section('description', 'Privacy Policy for ' . config('app.name', 'TheCashFox'))
@section('updated', 'September 2026')

@section('content')
    @php $app = config('app.name', 'TheCashFox'); @endphp

    <p>Your privacy matters. This Privacy Policy explains what personal information {{ $app }} collects, how we use it, how we protect it, and the choices you have. It applies to our website and all services operated under {{ $app }} (the "Service").</p>

    <h2>1. Information we collect</h2>

    <h3>Information you provide</h3>
    <ul>
        <li><strong>Account information</strong> — name, email address, and password (stored as a cryptographic hash, never in plain text). If you sign in with Google, we receive your name, email address, and Google account ID from Google.</li>
        <li><strong>Business and financial data</strong> — business names, book titles, entry amounts, categories, descriptions, receipts, comments, and attachments you upload</li>
        <li><strong>Team and collaboration data</strong> — invitations you send, team member emails, and role assignments</li>
        <li><strong>Payment information</strong> — if you subscribe on our website, billing details are collected and stored directly by Stripe. If you subscribe inside our iOS or Android app, the purchase is handled by Apple or Google and we receive only an anonymous purchase identifier and your subscription status through RevenueCat, our subscription management provider. In every case we receive only a customer identifier and whether the subscription is active. <strong>We never see or store your card number.</strong></li>
        <li><strong>Support correspondence</strong> — messages you send us</li>
    </ul>

    <h3>Information collected automatically</h3>
    <ul>
        <li><strong>Usage data</strong> — pages visited, features used, device type, browser, operating system, referring URL, timestamps</li>
        <li><strong>Log data</strong> — IP address, access times, and errors encountered (used for security, debugging, and abuse prevention)</li>
        <li><strong>Cookies and similar technologies</strong> — used to keep you signed in, remember preferences (e.g. theme), and measure usage. You can disable cookies in your browser but some features may stop working.</li>
    </ul>

    <h3>Mobile app permissions</h3>
    <p>Our iOS and Android apps ask for permissions only when you use the related feature, and you can turn them off at any time in your device settings:</p>
    <ul>
        <li><strong>Camera</strong> — to photograph a receipt so it can be attached to an entry or scanned to fill in the entry for you</li>
        <li><strong>Photo library</strong> — to choose an existing receipt image to attach or scan</li>
        <li><strong>Face ID / fingerprint</strong> — to unlock the app. Biometric checks are performed entirely by your device's operating system; we never receive or store your biometric data.</li>
    </ul>
    <p>The app stores your sign-in token in your device's secure storage (iOS Keychain / Android Keystore). We do not use advertising identifiers or track you across other apps.</p>

    <h2>2. How we use your information</h2>
    <ul>
        <li>To provide, operate, and maintain the Service</li>
        <li>To authenticate you and protect your account</li>
        <li>To process payments and manage subscriptions (via Stripe on the web, and Apple or Google with RevenueCat in the apps)</li>
        <li>To send transactional emails — account verification, password reset, team invitations, report emails you have opted into</li>
        <li>To improve the Service, fix bugs, and add features</li>
        <li>To detect and prevent fraud, abuse, and security incidents</li>
        <li>To comply with legal obligations</li>
    </ul>
    <p>We do <strong>not</strong> sell your personal information, and we do not use your financial data to train AI models.</p>

    <h2>3. AI features and third-party processing</h2>
    <p>Some features use our AI provider, Anthropic, to process data on your behalf. We send only what each feature needs, and only when you use it:</p>
    <ul>
        <li><strong>Receipt scanning</strong> — the receipt image or PDF you choose to scan</li>
        <li><strong>Category suggestions</strong> — the description of the entry you are typing, plus your list of category names</li>
        <li><strong>Describe-a-transaction entry</strong> — the sentence you type (for example "Paid 5000 for rent yesterday")</li>
        <li><strong>Cash flow insights</strong> — totals, counts, and category and book names for the book you are viewing. Individual entry descriptions are not sent.</li>
    </ul>
    <p>Anthropic processes this data under its commercial terms, does not use it to train its models, and may keep it for a limited period for safety and abuse monitoring before deleting it. We do not sell your data or use your financial data to train AI models.</p>

    <h2>4. Sharing and disclosure</h2>
    <p>We share your information only with:</p>
    <ul>
        <li><strong>Service providers</strong> who help us operate {{ $app }} — hosting (Railway), payments (Stripe on the web; Apple and Google for in-app purchases, with RevenueCat managing subscription status), email delivery (Resend), error monitoring (Sentry), AI processing (Anthropic), sign-in with Google and website analytics (Google), and bot protection on sign-up (Cloudflare Turnstile). Each is bound by contract to protect your data and use it only for the services they provide us.</li>
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
        <li>Receive a copy of your data in a portable format</li>
        <li>Object to or restrict certain processing</li>
        <li>Withdraw consent where processing is based on consent</li>
    </ul>
    <p>You can update your profile and delete your account directly inside the Service, on the web and in the mobile app — see <a href="{{ route('delete-account') }}">how to delete your account</a> for the steps and for exactly what is removed and what we must keep. Pro users can also export any book as PDF or CSV. For a full copy of your data, or anything else, email us.</p>

    <h2>8. Children</h2>
    <p>{{ $app }} is not directed at children under 16 and we do not knowingly collect personal information from them. If you believe a child has given us information, contact us and we will delete it.</p>

    <h2>9. International data transfers</h2>
    <p>Your information may be processed in countries other than the one you live in. By using the Service you consent to the transfer of your information to those countries, subject to appropriate safeguards.</p>

    <h2>10. Cookies</h2>
    <p>We use strictly necessary cookies (session, authentication, theme preference, CSRF protection) and Google Analytics cookies, which help us understand which pages and features are used. Google Analytics collects usage data such as pages visited, approximate location, and device type; it does not receive your financial data. We do not use advertising or cross-site tracking cookies. You can block analytics cookies with your browser settings or Google's opt-out add-on without affecting how the Service works.</p>

    <h2>11. Changes to this Policy</h2>
    <p>We may update this Policy from time to time. When we make material changes we will notify users by email or through the Service. The "last updated" date at the top of this page reflects the most recent revision.</p>

    <h2>12. Contact</h2>
    @php $support = config('app.support_email') ?: 'hello@' . (parse_url(config('app.url', 'https://thecashfox.com'), PHP_URL_HOST) ?: 'thecashfox.com'); @endphp
    <p>Questions about this Policy, or want to exercise your rights? Email <a href="mailto:{{ $support }}">{{ $support }}</a>.</p>

@endsection
