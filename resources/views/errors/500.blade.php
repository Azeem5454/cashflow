@extends('errors.layout')

@section('title', '500 — Server Error')

@section('content')
    <div class="error-icon" style="background-color: rgba(239, 68, 68, 0.1);">
        <svg style="color: #ef4444;" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.049.58.025 1.194-.14 1.743"/>
        </svg>
    </div>

    <div class="error-code">500</div>
    <h1 class="error-title">Something Went Wrong</h1>
    <p class="error-description">
        We hit an unexpected error on our end. Our team has been notified. Please try again in a few moments.
    </p>

    <div class="btn-row">
        <a href="javascript:location.reload()" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182"/></svg>
            Try Again
        </a>
        <a href="{{ url('/dashboard') }}" class="btn btn-secondary">Go to Dashboard</a>
    </div>
@endsection
