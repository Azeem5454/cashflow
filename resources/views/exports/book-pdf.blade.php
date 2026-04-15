<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>{{ $book->name }} — {{ $business->name }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'DejaVu Sans', sans-serif;
        font-size: 9px;
        color: #1e293b;
        background: #ffffff;
        padding: 28px 32px;
    }

    /* ── Header ─────────────────────────────────── */
    .header {
        border-bottom: 2px solid #1a56db;
        padding-bottom: 12px;
        margin-bottom: 16px;
    }
    .header-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    .brand-name {
        font-size: 18px;
        font-weight: 700;
        color: #1a56db;
        letter-spacing: -0.5px;
    }
    .export-meta {
        text-align: right;
        color: #64748b;
        font-size: 8px;
        line-height: 1.6;
    }
    .book-title {
        font-size: 15px;
        font-weight: 700;
        color: #0a0f1e;
        margin-top: 6px;
    }
    .book-sub {
        font-size: 8.5px;
        color: #64748b;
        margin-top: 2px;
    }

    /* ── Balance Summary ─────────────────────────── */
    .summary {
        display: flex;
        gap: 0;
        margin-bottom: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        overflow: hidden;
    }
    .summary-cell {
        flex: 1;
        padding: 10px 14px;
        border-right: 1px solid #e2e8f0;
    }
    .summary-cell:last-child { border-right: none; }
    .summary-label {
        font-size: 7px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        color: #64748b;
        margin-bottom: 3px;
    }
    .summary-amount {
        font-size: 16px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }
    .amount-in  { color: #16a34a; }
    .amount-out { color: #dc2626; }
    .amount-pos { color: #1a56db; }
    .amount-neg { color: #dc2626; }

    /* ── Entries Table ───────────────────────────── */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.5px;
    }
    thead tr {
        background: #0a0f1e;
        color: #ffffff;
    }
    thead th {
        padding: 7px 8px;
        text-align: left;
        font-weight: 700;
        font-size: 7.5px;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }
    thead th.text-right { text-align: right; }

    tbody tr { border-bottom: 1px solid #f1f5f9; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody tr:last-child { border-bottom: none; }

    tbody td {
        padding: 6px 8px;
        color: #334155;
        vertical-align: middle;
    }
    tbody td.text-right { text-align: right; }
    tbody td.mono { font-family: 'DejaVu Sans Mono', monospace; }

    .badge {
        display: inline-block;
        padding: 2px 5px;
        border-radius: 3px;
        font-size: 7px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .badge-in  { background: #dcfce7; color: #166534; }
    .badge-out { background: #fee2e2; color: #991b1b; }

    .cash-in  { color: #16a34a; font-weight: 600; }
    .cash-out { color: #dc2626; font-weight: 600; }
    .running-pos { color: #1a56db; font-weight: 600; }
    .running-neg { color: #dc2626; font-weight: 600; }

    .muted { color: #94a3b8; }

    /* ── Footer ──────────────────────────────────── */
    .footer {
        margin-top: 16px;
        padding-top: 8px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        color: #94a3b8;
        font-size: 7.5px;
    }

    /* ── Empty state ─────────────────────────────── */
    .empty {
        text-align: center;
        padding: 32px;
        color: #94a3b8;
        font-size: 9px;
    }
</style>
</head>
<body>

{{-- Header --}}
<div class="header">
    <div class="header-top">
        <div>
            <div class="brand-name">{{ config('app.name', 'TheCashFox') }}</div>
            <div class="book-title">{{ $book->name }}</div>
            <div class="book-sub">
                {{ $business->name }}
                @if($book->period_starts_at || $book->period_ends_at)
                    &nbsp;·&nbsp;
                    @if($book->period_starts_at && $book->period_ends_at)
                        {{ $book->period_starts_at->format('d M') }} – {{ $book->period_ends_at->format('d M Y') }}
                    @elseif($book->period_starts_at)
                        from {{ $book->period_starts_at->format('d M Y') }}
                    @else
                        until {{ $book->period_ends_at->format('d M Y') }}
                    @endif
                @endif
                @if($book->description)
                    &nbsp;·&nbsp; {{ $book->description }}
                @endif
            </div>
        </div>
        <div class="export-meta">
            <div>Exported {{ now()->format('d M Y, H:i') }}</div>
            <div>{{ $entries->count() }} {{ Str::plural('entry', $entries->count()) }}</div>
            <div>Currency: {{ $business->currency }}</div>
        </div>
    </div>
</div>

{{-- Balance Summary --}}
<div class="summary">
    @php $sym = $business->currencySymbol(); @endphp
    <div class="summary-cell">
        <div class="summary-label">Cash In</div>
        <div class="summary-amount amount-in">{{ $sym }}{{ number_format((float) $totalIn, 2) }}</div>
    </div>
    <div class="summary-cell">
        <div class="summary-label">Cash Out</div>
        <div class="summary-amount amount-out">{{ $sym }}{{ number_format((float) $totalOut, 2) }}</div>
    </div>
    <div class="summary-cell">
        <div class="summary-label">Net Balance</div>
        <div class="summary-amount {{ (float)$balance >= 0 ? 'amount-pos' : 'amount-neg' }}">
            {{ (float)$balance < 0 ? '−' : '' }}{{ $sym }}{{ number_format(abs((float) $balance), 2) }}
        </div>
    </div>
    @if((float)$book->opening_balance > 0)
    <div class="summary-cell">
        <div class="summary-label">Opening Balance</div>
        <div class="summary-amount" style="color:#1a56db;">{{ $sym }}{{ number_format((float) $book->opening_balance, 2) }}</div>
    </div>
    @endif
</div>

{{-- Entries Table --}}
@if($entries->isEmpty())
    <div class="empty">No entries in this book.</div>
@else
    <table>
        <thead>
            <tr>
                <th style="width:72px;">Date</th>
                <th>Description</th>
                <th style="width:80px;">Reference</th>
                <th style="width:80px;">Category</th>
                <th style="width:72px;">Pay Mode</th>
                <th class="text-right" style="width:80px;">Cash In</th>
                <th class="text-right" style="width:80px;">Cash Out</th>
                <th class="text-right" style="width:88px;">Running Bal.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entries as $entry)
                <tr>
                    <td class="mono">{{ $entry->date->format('d M Y') }}</td>
                    <td>{{ $entry->description }}</td>
                    <td class="muted">{{ $entry->reference ?? '—' }}</td>
                    <td class="muted">{{ $entry->category ?? '—' }}</td>
                    <td class="muted">{{ $entry->payment_mode ?? '—' }}</td>
                    <td class="text-right mono {{ $entry->type === 'in' ? 'cash-in' : 'muted' }}">
                        @if($entry->type === 'in')
                            {{ $sym }}{{ number_format((float) $entry->amount, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right mono {{ $entry->type === 'out' ? 'cash-out' : 'muted' }}">
                        @if($entry->type === 'out')
                            {{ $sym }}{{ number_format((float) $entry->amount, 2) }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-right mono {{ (float)$entry->running_balance >= 0 ? 'running-pos' : 'running-neg' }}">
                        {{ (float)$entry->running_balance < 0 ? '−' : '' }}{{ $sym }}{{ number_format(abs((float) $entry->running_balance), 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif

{{-- Footer --}}
<div class="footer">
    <span>{{ config('app.name', 'TheCashFox') }} · {{ $business->name }} · {{ $book->name }}</span>
    <span>Generated {{ now()->format('d M Y \a\t H:i') }}</span>
</div>

</body>
</html>
