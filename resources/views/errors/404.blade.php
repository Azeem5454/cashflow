@extends('errors.layout')

@section('title', '404 — Page Not Found')

@section('content')
    <div class="error-icon">
        <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
        </svg>
    </div>

    <div class="error-code">404</div>
    <h1 class="error-title">This page wandered off</h1>
    <p class="error-description">
        The link you followed is broken or the page has moved. Try one of the common destinations below, or head back to the homepage.
    </p>

    <div class="btn-row">
        <a href="{{ url('/') }}" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955a1.126 1.126 0 0 1 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
            </svg>
            Back to homepage
        </a>
        <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
    </div>

    <div class="quick-links">
        @auth
            <a href="{{ url('/dashboard') }}" class="ql">Dashboard</a>
            <a href="{{ url('/settings/billing') }}" class="ql">Billing</a>
            <a href="{{ url('/profile') }}" class="ql">Profile</a>
        @else
            <a href="{{ route('register') }}" class="ql">Create free account</a>
            <a href="{{ route('login') }}" class="ql">Sign in</a>
            <a href="{{ route('terms') }}" class="ql">Terms</a>
            <a href="{{ route('privacy') }}" class="ql">Privacy</a>
        @endauth
    </div>
@endsection
