@extends('errors.layout')

@section('title', '429 — Too Many Requests')

@section('content')
    <div class="error-icon" style="background-color: rgba(245, 158, 11, 0.1);">
        <svg style="color: #f59e0b;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
        </svg>
    </div>

    <div class="error-code">429</div>
    <h1 class="error-title">Slow Down</h1>
    <p class="error-description">
        You've made too many requests in a short time. Please wait a moment and try again. This limit protects the service for everyone.
    </p>

    <div class="btn-row">
        <a href="javascript:location.reload()" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
            Try Again
        </a>
        <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
    </div>
@endsection
