{{--
    Google Analytics 4, consent-gated.

    Replaces four near-identical copies of this snippet across the landing
    page and the three layouts. `spa` turns on virtual page views for
    wire:navigate swaps, which only the authenticated layout needs.

    Where consent is required (see App\Support\CookieConsent), gtag.js is not
    requested at all until the visitor accepts. Loading it and asking
    afterwards would set the cookies first, which is the thing the rule is
    about. Everywhere else it loads as before.
--}}
@props(['spa' => false])

@php($gaId = config('services.analytics.ga4_id'))
@php($needsConsent = \App\Support\CookieConsent::isRequired())

@if($gaId)
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}

        (function () {
            var ID = @json($gaId);
            var NEEDS_CONSENT = @json($needsConsent);
            var SPA = @json((bool) $spa);
            var started = false;

            function start() {
                if (started) return;
                started = true;

                var s = document.createElement('script');
                s.async = true;
                s.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(ID);
                document.head.appendChild(s);

                gtag('js', new Date());
                gtag('config', ID, {send_page_view: true});

                if (SPA) {
                    document.addEventListener('livewire:navigated', function () {
                        gtag('event', 'page_view', {
                            page_path: window.location.pathname + window.location.search
                        });
                    });
                }
            }

            if (!NEEDS_CONSENT) {
                start();
                return;
            }

            // Private browsing and blocked site data make storage throw, so a
            // read that fails is treated as "not answered yet".
            var choice = null;
            try { choice = localStorage.getItem('cashfox_cookie_consent'); } catch (e) {}

            if (choice === 'accepted') {
                start();
            } else {
                window.addEventListener('cashfox:cookie-consent-accepted', start);
            }
        })();
    </script>
@endif
