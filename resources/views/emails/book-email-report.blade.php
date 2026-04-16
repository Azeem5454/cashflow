@php
    $symbol = $book->business->currencySymbol();
    $summary = $reportData['periodSummary'];
    $categories = $reportData['topCategories'] ?? [];
    $recentEntries = $reportData['recentEntries'] ?? [];
    $periodLabel = $reportData['periodLabel'] ?? '';
    $netFloat = (float) $summary['netBalance'];
    $bookUrl = route('businesses.books.show', [$book->business_id, $book->id]);
    $periodTitle = $frequency === 'weekly' ? 'Weekly' : 'Monthly';
@endphp

@component('emails.partials.layout', [
    'emailTitle' => $periodTitle . ' Report — ' . $book->name,
    'badge' => $periodTitle,
    'preheader' => $book->name . ': ' . $symbol . number_format((float)$summary['totalIn'], 2) . ' in, ' . $symbol . number_format((float)$summary['totalOut'], 2) . ' out',
    'footerText' => 'You\'re receiving this because ' . strtolower($periodTitle) . ' email reports are enabled for <strong style="color:#94a3b8;">' . e($book->name) . '</strong>. To change frequency or unsubscribe, open the book in ' . config('app.name') . ' and go to Settings &gt; Email Reports.',
    'extraStyles' => '@media only screen and (max-width: 600px) {
        .summary-row { display: block !important; width: 100% !important; }
        .summary-cell { display: block !important; width: 100% !important; padding: 0 0 8px 0 !important; }
        .summary-cell-last { padding-bottom: 0 !important; }
        .summary-box { padding: 12px 16px !important; }
        .summary-box-inner { display: flex !important; align-items: center !important; justify-content: space-between !important; text-align: left !important; }
        .summary-label { display: inline !important; }
        .summary-amount { margin-top: 0 !important; font-size: 17px !important; }
        .summary-count { display: inline !important; margin-top: 0 !important; margin-left: 8px !important; }
        .entry-amount { font-size: 13px !important; }
    }',
])

    {{-- Book name + period --}}
    <tr><td class="section-pad" style="padding:24px 32px 0;">
        <div style="font-size:20px;font-weight:700;color:#ffffff;">{{ $book->name }}</div>
        <div style="font-size:13px;color:#64748b;margin-top:4px;">{{ $book->business->name }}@if($periodLabel) &middot; {{ $periodLabel }}@endif</div>
    </td></tr>

    {{-- Divider --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;"><div style="height:1px;background-color:#1e293b;"></div></td></tr>

    {{-- ===== SUMMARY CARDS ===== --}}
    <tr><td class="section-pad" style="padding:20px 32px 0;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr class="summary-row">
                {{-- Cash In --}}
                <td width="33%" class="summary-cell" style="padding-right:6px;">
                    <div class="summary-box" style="background-color:#0a0f1e;border-radius:12px;padding:16px 14px;text-align:center;border:1px solid #1e293b;">
                        <div class="summary-box-inner">
                            <div>
                                <div class="summary-label" style="font-size:10px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Cash In</div>
                                <div class="summary-count" style="font-size:11px;color:#64748b;margin-top:4px;">{{ $summary['inCount'] }} {{ $summary['inCount'] === 1 ? 'entry' : 'entries' }}</div>
                            </div>
                            <div class="summary-amount" style="font-size:18px;font-weight:700;color:#22c55e;margin-top:6px;font-family:'Courier New',Courier,monospace;">{{ $symbol }}{{ number_format((float)$summary['totalIn'], 2) }}</div>
                        </div>
                    </div>
                </td>
                {{-- Cash Out --}}
                <td width="33%" class="summary-cell" style="padding:0 3px;">
                    <div class="summary-box" style="background-color:#0a0f1e;border-radius:12px;padding:16px 14px;text-align:center;border:1px solid #1e293b;">
                        <div class="summary-box-inner">
                            <div>
                                <div class="summary-label" style="font-size:10px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Cash Out</div>
                                <div class="summary-count" style="font-size:11px;color:#64748b;margin-top:4px;">{{ $summary['outCount'] }} {{ $summary['outCount'] === 1 ? 'entry' : 'entries' }}</div>
                            </div>
                            <div class="summary-amount" style="font-size:18px;font-weight:700;color:#ef4444;margin-top:6px;font-family:'Courier New',Courier,monospace;">{{ $symbol }}{{ number_format((float)$summary['totalOut'], 2) }}</div>
                        </div>
                    </div>
                </td>
                {{-- Net Balance --}}
                <td width="33%" class="summary-cell summary-cell-last" style="padding-left:6px;">
                    <div class="summary-box" style="background-color:#0a0f1e;border-radius:12px;padding:16px 14px;text-align:center;border:1px solid {{ $netFloat >= 0 ? '#1a56db' : '#dc2626' }};">
                        <div class="summary-box-inner">
                            <div>
                                <div class="summary-label" style="font-size:10px;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:1px;">Net</div>
                                <div class="summary-count" style="font-size:11px;color:#64748b;margin-top:4px;">{{ $summary['daySpan'] }} day{{ $summary['daySpan'] !== 1 ? 's' : '' }}</div>
                            </div>
                            <div class="summary-amount" style="font-size:18px;font-weight:700;color:{{ $netFloat >= 0 ? '#3b82f6' : '#ef4444' }};margin-top:6px;font-family:'Courier New',Courier,monospace;">{{ $netFloat >= 0 ? '+' : '' }}{{ $symbol }}{{ number_format($netFloat, 2) }}</div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </td></tr>

    {{-- Daily average --}}
    @if((float)$summary['dailyAverage'] != 0)
    <tr><td class="section-pad" style="padding:12px 32px 0;">
        <div style="font-size:12px;color:#64748b;text-align:center;">
            Daily avg: <span style="color:#93c5fd;font-weight:600;font-family:'Courier New',Courier,monospace;">{{ $symbol }}{{ number_format(abs((float)$summary['dailyAverage']), 2) }}</span> {{ (float)$summary['dailyAverage'] >= 0 ? 'net inflow' : 'net outflow' }}
        </div>
    </td></tr>
    @endif

    {{-- ===== TOP CATEGORIES ===== --}}
    @if(count($categories) > 0)
    <tr><td class="section-pad" style="padding:24px 32px 0;"><div style="height:1px;background-color:#1e293b;"></div></td></tr>
    <tr><td class="section-pad" style="padding:20px 32px 0;">
        <div style="font-size:13px;font-weight:700;color:#ffffff;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.5px;">Top Spending Categories</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            @foreach($categories as $i => $cat)
            @php $catColor = ['#3b82f6','#8b5cf6','#f59e0b','#10b981','#ef4444'][$i] ?? '#64748b'; @endphp
            <tr>
                <td style="padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid #1e293b;' : '' }}">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td width="24" style="vertical-align:middle;">
                                <div style="width:20px;height:20px;border-radius:6px;background-color:{{ $catColor }};opacity:0.2;"></div>
                            </td>
                            <td style="padding-left:10px;vertical-align:middle;">
                                <div style="font-size:13px;color:#e2e8f0;font-weight:500;">{{ $cat['name'] }}</div>
                            </td>
                            <td align="right" style="vertical-align:middle;white-space:nowrap;">
                                <span style="font-size:13px;color:#ffffff;font-weight:600;font-family:'Courier New',Courier,monospace;">{{ $symbol }}{{ number_format((float)$cat['total'], 2) }}</span>
                                <span style="font-size:11px;color:#64748b;margin-left:4px;">{{ $cat['percentage'] }}%</span>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td colspan="2" style="padding-top:6px;">
                                <div style="width:100%;height:3px;background-color:#1e293b;border-radius:2px;">
                                    <div style="width:{{ min($cat['percentage'], 100) }}%;height:3px;background-color:{{ $catColor }};border-radius:2px;"></div>
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

    {{-- ===== RECENT ENTRIES ===== --}}
    @if(count($recentEntries) > 0)
    <tr><td class="section-pad" style="padding:24px 32px 0;"><div style="height:1px;background-color:#1e293b;"></div></td></tr>
    <tr><td class="section-pad" style="padding:20px 32px 0;">
        <div style="font-size:13px;font-weight:700;color:#ffffff;margin-bottom:12px;text-transform:uppercase;letter-spacing:0.5px;">Recent Entries</div>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            @foreach($recentEntries as $entry)
            <tr>
                <td style="padding:10px 0;{{ !$loop->last ? 'border-bottom:1px solid #1e293b;' : '' }}">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td width="8" style="vertical-align:top;padding-top:5px;">
                                <div style="width:6px;height:6px;border-radius:50%;background-color:{{ $entry['type'] === 'in' ? '#22c55e' : '#ef4444' }};"></div>
                            </td>
                            <td style="padding-left:10px;">
                                <div style="font-size:13px;color:#e2e8f0;line-height:1.4;">{{ $entry['description'] ?: '—' }}</div>
                                <div style="font-size:11px;color:#64748b;margin-top:2px;">{{ $entry['date'] }}</div>
                            </td>
                            <td align="right" style="vertical-align:top;white-space:nowrap;">
                                <div class="entry-amount" style="font-size:14px;font-weight:600;color:{{ $entry['type'] === 'in' ? '#22c55e' : '#ef4444' }};font-family:'Courier New',Courier,monospace;">
                                    {{ $entry['type'] === 'in' ? '+' : '-' }}{{ $symbol }}{{ number_format((float)$entry['amount'], 2) }}
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

    {{-- ===== CTA BUTTON ===== --}}
    <tr><td class="section-pad" style="padding:28px 32px 0;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr><td align="center">
                <a href="{{ $bookUrl }}" class="cta-btn"
                   style="display:inline-block;padding:14px 36px;background-color:#1a56db;color:#ffffff;font-size:14px;font-weight:600;text-decoration:none;border-radius:10px;letter-spacing:0.2px;">
                    Open Book in {{ config('app.name') }} &rarr;
                </a>
            </td></tr>
        </table>
    </td></tr>

    {{-- Fallback plain link --}}
    <tr><td class="section-pad" style="padding:12px 32px 0;">
        <div style="font-size:11px;color:#64748b;text-align:center;line-height:1.5;word-break:break-all;">
            If the button doesn't work: <a href="{{ $bookUrl }}" style="color:#3b82f6;text-decoration:underline;">{{ $bookUrl }}</a>
        </div>
    </td></tr>

@endcomponent
