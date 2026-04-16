@php
    $appName = config('app.name', 'TheCashFox');
    $appUrl  = rtrim(config('app.url', 'https://cashflow.app'), '/');
    // Use absolute URL — emails are rendered far from a request context,
    // and the route helper can't emit relative URLs to image src anyway.
    $hasLogo = \App\Models\UploadedAsset::has('logo-dark');
    $logoUrl = $hasLogo
        ? $appUrl . route('brand-asset', 'logo-dark', false) . '?v=' . \App\Models\UploadedAsset::cacheBuster('logo-dark')
        : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $emailTitle ?? $appName }}</title>
    <!--[if mso]>
    <noscript><xml>
    <o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings>
    </xml></noscript>
    <![endif]-->
    <style>
        body, table, td { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        @media only screen and (max-width: 600px) {
            .outer-padding { padding: 16px 8px 32px !important; }
            .inner-card { border-radius: 12px !important; }
            .section-pad { padding-left: 20px !important; padding-right: 20px !important; }
            .header-pad { padding: 24px 20px 0 !important; }
            .cta-btn { display: block !important; text-align: center !important; }
            .footer-pad { padding: 24px 20px 28px !important; }
        }
    </style>
    @if(isset($extraStyles))
    <style>{!! $extraStyles !!}</style>
    @endif
</head>
<body style="margin:0;padding:0;background-color:#0a0f1e;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">

{{-- Preheader --}}
@if(isset($preheader))
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">{{ $preheader }}</div>
@endif

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#0a0f1e;">
<tr><td align="center" class="outer-padding" style="padding:32px 16px 48px;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" class="inner-card" style="max-width:560px;background-color:#111827;border-radius:16px;overflow:hidden;border:1px solid #1e293b;">

        {{-- Accent bar --}}
        <tr><td style="height:4px;background:linear-gradient(90deg,#1a56db,#3b82f6);font-size:0;line-height:0;">&nbsp;</td></tr>

        {{-- Logo --}}
        <tr><td class="header-pad" style="padding:28px 32px 0;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td>
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $appName }}" height="32" style="height:32px;width:auto;display:block;">
                        @else
                            <div style="font-size:22px;font-weight:800;color:#ffffff;letter-spacing:-0.3px;">{{ $appName }}</div>
                        @endif
                    </td>
                    @if(isset($badge))
                    <td align="right">
                        <span style="display:inline-block;padding:4px 12px;background-color:rgba(26,86,219,0.15);color:#93c5fd;font-size:11px;font-weight:600;border-radius:20px;letter-spacing:0.5px;text-transform:uppercase;">{{ $badge }}</span>
                    </td>
                    @endif
                </tr>
            </table>
        </td></tr>

        {{-- Content slot --}}
        {{ $slot }}

        {{-- Footer --}}
        <tr><td class="section-pad footer-pad" style="padding:28px 32px 32px;">
            <div style="height:1px;background-color:#1e293b;margin-bottom:20px;"></div>
            @if(isset($footerText))
                <div style="font-size:11px;color:#475569;line-height:1.6;text-align:center;">
                    {!! $footerText !!}
                </div>
            @endif
            <div style="font-size:11px;color:#334155;text-align:center;{{ isset($footerText) ? 'margin-top:12px;' : '' }}">
                &copy; {{ date('Y') }} {{ $appName }}
            </div>
        </td></tr>

    </table>

</td></tr>
</table>

</body>
</html>
