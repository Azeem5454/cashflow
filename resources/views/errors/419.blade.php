@extends('errors.layout')

@section('title', '419 — Session Expired')

@section('content')
    <div class="error-icon" style="background-color: rgba(245, 158, 11, 0.1);">
        <svg style="color: #f59e0b;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
        </svg>
    </div>

    <div class="error-code">419</div>
    <h1 class="error-title">Session Expired</h1>
    <p class="error-description">
        Your session has timed out for security. This usually happens if the page was left open for a while. Just refresh and try again.
    </p>

    <div class="btn-row">
        <a href="javascript:location.reload()" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
            Refresh Page
        </a>
        <a href="{{ url('/login') }}" class="btn btn-secondary">Sign In Again</a>
    </div>
@endsection
