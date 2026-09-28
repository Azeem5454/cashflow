{{--
    Cookie consent banner.

    Only rendered where prior consent is legally required (EEA, UK, CH, or an
    unknown country) — see App\Support\CookieConsent. Everywhere else this is
    not in the DOM at all, so the signup flow stays clean.

    Deliberately vanilla JS: this has to work on the landing page, where
    Alpine arrives from a CDN with `defer`, and inside the app, where Livewire
    owns Alpine. No load-order assumptions.

    Accept and Reject are the same size and weight. A banner where refusing is
    harder than agreeing is not consent, and regulators have said so.
--}}
@if(\App\Support\CookieConsent::isRequired() && config('services.analytics.ga4_id'))
<div id="cookie-consent" hidden
     role="dialog" aria-live="polite" aria-label="Cookie choices"
     class="fixed bottom-0 inset-x-0 z-[90] p-3 sm:p-4">
    <div class="mx-auto max-w-3xl rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl p-4 sm:p-5
                flex flex-col sm:flex-row sm:items-center gap-4">
        <p class="flex-1 text-sm leading-relaxed text-slate-300">
            We use analytics cookies to understand how the site is used. They are
            off until you say yes.
            <a href="{{ route('privacy') }}" class="underline text-blue-300 hover:text-blue-200">Privacy&nbsp;policy</a>
        </p>

        <div class="flex gap-2 sm:flex-shrink-0">
            <button type="button" data-consent="rejected"
                    class="flex-1 sm:flex-none px-4 py-2.5 rounded-lg text-sm font-semibold
                           border border-slate-600 text-slate-200 hover:bg-slate-800 transition-colors">
                Reject
            </button>
            <button type="button" data-consent="accepted"
                    class="flex-1 sm:flex-none px-4 py-2.5 rounded-lg text-sm font-semibold
                           bg-primary text-white hover:bg-accent transition-colors">
                Accept
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    var KEY = 'cashfox_cookie_consent';
    var el = document.getElementById('cookie-consent');
    if (!el) return;

    function read() {
        try { return localStorage.getItem(KEY); } catch (e) { return null; }
    }

    // Already answered: never ask again. A storage read that throws (private
    // window, blocked site data) counts as unanswered, so we ask and simply
    // cannot remember the answer — the safe way round.
    if (read() === 'accepted' || read() === 'rejected') return;

    el.hidden = false;

    el.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-consent]');
        if (!btn) return;

        var choice = btn.getAttribute('data-consent');
        try { localStorage.setItem(KEY, choice); } catch (err) {}

        el.hidden = true;

        if (choice === 'accepted') {
            window.dispatchEvent(new Event('cashfox:cookie-consent-accepted'));
        }
    });
})();
</script>
@endif
