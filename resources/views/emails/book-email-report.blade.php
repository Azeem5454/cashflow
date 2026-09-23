@php
    $symbol = $book->business->currencySymbol();
    $summary = $reportData['periodSummary'];
    $categories = $reportData['topCategories'] ?? [];
    $recentEntries = $reportData['recentEntries'] ?? [];
    $periodLabel = $reportData['periodLabel'] ?? '';
    $netFloat = (float) $summary['netBalance'];
    $bookUrl = route('businesses.books.show', [$book->business_id, $book->id]);
    $periodTitle = $frequency === 'weekly' ? 'Weekly' : 'Monthly';
    $mono = "'SFMono-Regular',Menlo,Consolas,'Liberation Mono','Courier New',monospace";
    $green = '#15803d';
    $red = '#b91c1c';
@endphp

@component('emails.partials.layout', [
    'emailTitle' => $periodTitle . ' Report — ' . $book->name,
    'badge' => $periodTitle,
    'preheader' => $book->name . ': ' . $symbol . number_format((float)$summary['totalIn'], 2) . ' in, ' . $symbol . number_format((float)$summary['totalOut'], 2) . ' out',
    'footerText' => 'You\'re receiving this because ' . strtolower($periodTitle) . ' email reports are enabled for <strong style="color:#334155;">' . e($book->name) . '</strong>. To change frequency or unsubscribe, open the book in ' . e(config('app.name')) . ' and go to Settings &gt; Email Reports.',
    'extraStyles' => '@media only screen and (max-width: 600px) {
        .summary-cell { display: block !important; width: 100% !important; padding: 0 0 8px 0 !important; }
        .summary-cell-last { padding-bottom: 0 !important; }
        .summary-amount { font-size: 18px !important; }
        .entry-amount { font-size: 13px !important; }
    }',
])

    {{-- Business logo (absolute URL — the recipient's mail client fetches it) --}}
    @php $bizLogo = $book->business->logoUrl(absolute: true); @endphp
    @if($bizLogo)
        <tr><td class="section-pad" bgcolor="#ffffff" style="padding:24px 32px 0;background-color:#ffffff;">
            <img src="{{ $bizLogo }}" alt="{{ $book->business->name }}" height="40" style="display:block;max-height:40px;width:auto;border:0;outline:none;text-decoration:none;">
        </td></tr>
    @endif

    {{-- Book name + period --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:{{ $bizLogo ? '12px' : '24px' }} 32px 0;background-color:#ffffff;">
        <div style="font-size:22px;font-weight:700;color:#0f172a;line-height:1.3;">{{ $book->name }}</div>
        <div style="font-size:14px;color:#64748b;margin-top:4px;">{{ $book->business->name }}@if($periodLabel) &middot; {{ $periodLabel }}@endif</div>
        @if($book->business->contact_phone || $book->business->contact_email)
            <div style="font-size:12px;color:#94a3b8;margin-top:4px;">
                {{ trim(collect([$book->business->contact_phone, $book->business->contact_email])->filter()->implode(' · ')) }}
            </div>
        @endif
    </td></tr>

    {{-- ===== SUMMARY CARDS ===== --}}
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:24px 32px 0;background-color:#ffffff;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
                @foreach([
                    ['label' => 'Cash In',  'sub' => $summary['inCount'] . ' ' . ($summary['inCount'] === 1 ? 'entry' : 'entries'),  'value' => $symbol . number_format((float)$summary['totalIn'], 2),  'color' => $green, 'border' => '#e2e8f0', 'pad' => 'padding-right:6px;', 'last' => false],
                    ['label' => 'Cash Out', 'sub' => $summary['outCount'] . ' ' . ($summary['outCount'] === 1 ? 'entry' : 'entries'), 'value' => $symbol . number_format((float)$summary['totalOut'], 2), 'color' => $red,   'border' => '#e2e8f0', 'pad' => 'padding:0 3px;', 'last' => false],
                    ['label' => 'Net',      'sub' => $summary['daySpan'] . ' day' . ($summary['daySpan'] !== 1 ? 's' : ''), 'value' => ($netFloat >= 0 ? '+' : '-') . $symbol . number_format(abs($netFloat), 2), 'color' => $netFloat >= 0 ? '#1a56db' : $red, 'border' => $netFloat >= 0 ? '#1a56db' : $red, 'pad' => 'padding-left:6px;', 'last' => true],
                ] as $card)
                <td width="33%" valign="top" class="summary-cell {{ $card['last'] ? 'summary-cell-last' : '' }}" style="{{ $card['pad'] }}">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f8fafc" style="background-color:#f8fafc;border:1px solid {{ $card['border'] }};border-radius:8px;border-collapse:separate;">
                        <tr>
                            <td align="center" style="padding:14px 10px;">
                                <div style="font-size:11px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">{{ $card['label'] }}</div>
                                <div class="summary-amount" style="font-size:17px;font-weight:700;color:{{ $card['color'] }};margin-top:6px;font-family:{!! $mono !!};white-space:nowrap;">{{ $card['value'] }}</div>
                                <div style="font-size:12px;color:#64748b;margin-top:4px;">{{ $card['sub'] }}</div>
                            </td>
                        </tr>
                    </table>
                </td>
                @endforeach
            </tr>
        </table>
    </td></tr>

    {{-- Daily average --}}
    @if((float)$summary['dailyAverage'] != 0)
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:12px 32px 0;background-color:#ffffff;">
        <div style="font-size:13px;color:#64748b;text-align:center;">
            Daily avg: <span style="color:#0f172a;font-weight:600;font-family:{!! $mono !!};">{{ $symbol }}{{ number_format(abs((float)$summary['dailyAverage']), 2) }}</span> {{ (float)$summary['dailyAverage'] >= 0 ? 'net inflow' : 'net outflow' }}
        </div>
    </td></tr>
    @endif

    {{-- ===== TOP CATEGORIES ===== --}}
    @if(count($categories) > 0)
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:28px 32px 0;background-color:#ffffff;">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">Top spending categories</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            @foreach($categories as $i => $cat)
            @php $pct = max(0, min((int) round($cat['percentage']), 100)); @endphp
            <tr>
                <td style="padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid #e2e8f0;' : '' }}">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="vertical-align:middle;font-size:14px;color:#334155;font-weight:500;">{{ $cat['name'] }}</td>
                            <td align="right" style="vertical-align:middle;white-space:nowrap;">
                                <span style="font-size:14px;color:#0f172a;font-weight:600;font-family:{!! $mono !!};">{{ $symbol }}{{ number_format((float)$cat['total'], 2) }}</span>
                                <span style="font-size:12px;color:#64748b;margin-left:4px;">{{ $cat['percentage'] }}%</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding-top:6px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#e2e8f0" style="background-color:#e2e8f0;border-radius:3px;border-collapse:separate;">
                                    <tr>
                                        @if($pct > 0)
                                        <td width="{{ $pct }}%" height="6" bgcolor="#1a56db" style="width:{{ $pct }}%;height:6px;background-color:#1a56db;border-radius:3px;font-size:0;line-height:0;">&nbsp;</td>
                                        @endif
                                        @if($pct < 100)
                                        <td height="6" bgcolor="#e2e8f0" style="height:6px;background-color:#e2e8f0;border-radius:3px;font-size:0;line-height:0;">&nbsp;</td>
                                        @endif
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            @endforeach
        </table>
    </td></tr>
    @endif

    {{-- ===== RECENT ENTRIES ===== --}}
    @if(count($recentEntries) > 0)
    <tr><td class="section-pad" bgcolor="#ffffff" style="padding:28px 32px 0;background-color:#ffffff;">
        <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.5px;">Recent entries</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            @foreach($recentEntries as $entry)
            @php $isIn = $entry['type'] === 'in'; @endphp
            <tr>
                <td style="padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid #e2e8f0;' : '' }}">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td style="vertical-align:top;">
                                <div style="font-size:14px;color:#334155;line-height:1.4;">{{ $entry['description'] ?: '—' }}</div>
                                <div style="font-size:12px;color:#64748b;margin-top:2px;">{{ $entry['date'] }} &middot; <span style="color:{{ $isIn ? $green : $red }};">{{ $isIn ? 'Cash In' : 'Cash Out' }}</span></div>
                            </td>
                            <td align="right" style="vertical-align:top;white-space:nowrap;padding-left:12px;">
                                <div class="entry-amount" style="font-size:14px;font-weight:600;color:{{ $isIn ? $green : $red }};font-family:{!! $mono !!};">
                                    {{ $isIn ? '+' : '-' }}{{ $symbol }}{{ number_format((float)$entry['amount'], 2) }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            @endforeach
        </table>
    </td></tr>
    @endif

    {{-- CTA + fallback link --}}
    @include('emails.partials.button', ['url' => $bookUrl, 'label' => 'Open Book in ' . config('app.name')])

@endcomponent
