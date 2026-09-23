@extends('legal._layout')

@section('title', 'Delete Your Account')
@section('description', 'How to delete your ' . config('app.name', 'TheCashFox') . ' account and what happens to your data.')
@section('updated', 'September 2026')

@section('content')
    @php
        $app     = config('app.name', 'TheCashFox');
        $support = config('app.support_email') ?: 'hello@' . (parse_url(config('app.url', 'https://thecashfox.com'), PHP_URL_HOST) ?: 'thecashfox.com');
    @endphp

    <p>You can delete your {{ $app }} account at any time, from the app or from the website. This page explains how to do it, what gets deleted, and what we are required to keep.</p>

    <div class="callout">
        <strong>Before you delete:</strong> deletion is permanent and immediate. If you want to keep a copy of your records, export your books as PDF or CSV first — once the account is gone, neither you nor we can bring it back.
    </div>

    <h2>Delete from the mobile app</h2>
    <ul>
        <li>Open {{ $app }} and go to <strong>Profile</strong></li>
        <li>Scroll to the bottom and tap <strong>Delete account</strong></li>
        <li>Confirm with your password — or, if you signed up with Google, by typing your email address</li>
    </ul>

    <h2>Delete from the website</h2>
    <ul>
        <li>Sign in at <a href="{{ url('/login') }}">{{ rtrim(config('app.url', 'https://thecashfox.com'), '/') }}/login</a></li>
        <li>Go to <strong>Settings → Profile</strong></li>
        <li>Open the <strong>Danger Zone</strong> at the bottom of the page</li>
        <li>Type your email address to confirm, then choose <strong>Delete Account</strong></li>
    </ul>

    <h2>Request deletion by email</h2>
    <p>If you cannot sign in, email <a href="mailto:{{ $support }}">{{ $support }}</a> from the address on the account and ask us to delete it. We will verify that you own the address and complete the deletion within 30 days, usually much sooner.</p>

    <h2>What is deleted</h2>
    <p>Deleting your account immediately and permanently removes:</p>
    <ul>
        <li>Your profile — name, email address, password, and any linked Google sign-in</li>
        <li>Every business you own, and everything inside it: books, entries, opening balances, categories, payment methods, comments, activity history, recurring rules, and email report schedules</li>
        <li>Receipts and other files you attached to entries</li>
        <li>Invitations you sent, including any that are still pending</li>
        <li>Your membership of businesses owned by other people</li>
        <li>Notifications and saved preferences</li>
    </ul>

    <h2>What is kept, and why</h2>
    <ul>
        <li><strong>Businesses owned by someone else.</strong> If you were invited into another person's business as an editor or viewer, that business and its records belong to its owner and are not deleted. Entries you created there remain in their books, but are no longer linked to you.</li>
        <li><strong>Payment records.</strong> Stripe keeps invoices and payment history for as long as tax and accounting law requires. We hold no card details at any point — Stripe stores them, never us.</li>
        <li><strong>Anonymised usage counts.</strong> Records of AI usage and its cost are stripped of any link to you and kept as aggregate figures. They cannot be traced back to you or to your data.</li>
        <li><strong>Encrypted backups.</strong> Copies of the database may persist in routine encrypted backups for up to 30 days before they rotate out. We do not restore deleted accounts from them.</li>
        <li><strong>Security logs.</strong> Error and access logs, which may include an IP address, are retained for up to 90 days for fraud prevention and debugging, then deleted.</li>
    </ul>

    <h2>If you have a Pro subscription</h2>
    <p>Deleting your account cancels a subscription billed through our website straight away, so you are not charged again.</p>
    <p>If you subscribed <strong>inside the iOS or Android app</strong>, the subscription is held by Apple or Google and we cannot cancel it for you. Cancel it yourself before deleting your account:</p>
    <ul>
        <li><strong>iPhone or iPad</strong> — Settings → your name → Subscriptions → {{ $app }} → Cancel Subscription</li>
        <li><strong>Android</strong> — Play Store → profile icon → Payments &amp; subscriptions → Subscriptions → {{ $app }} → Cancel</li>
    </ul>

    <h2>Questions</h2>
    <p>Email <a href="mailto:{{ $support }}">{{ $support }}</a> and a person will answer. See our <a href="{{ route('privacy') }}">Privacy Policy</a> for the full picture of what we collect and how we handle it.</p>

@endsection
