@php
    $appName = config('app.name', 'TheCashFox');
    $appUrl  = rtrim(config('app.url', 'https://thecashfox.com'), '/');
    // Light transactional design: always use the LIGHT-mode logo (dark wordmark
    // on a white card). Absolute URL — emails render far from a request context.
    $hasLogo = \App\Models\UploadedAsset::has('logo-light');
    $logoUrl = $hasLogo
        ? $appUrl . route('brand-asset', 'logo-light', false) . '?v=' . \App\Models\UploadedAsset::cacheBuster('logo-light')
        : null;
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="color-scheme" content="light only">
    <meta name="supported-color-schemes" content="light only">
    <title>{{ $emailTitle ?? $appName }}</title>
    <!--[if mso]>
    <noscript><xml>
    <o:OfficeDocumentSettings><o:PixelsPerInch>96</o:PixelsPerInch></o:OfficeDocumentSettings>
    </xml></noscript>
    <![endif]-->
    <style>
        :root { color-scheme: light only; supported-color-schemes: light only; }
        body, table, td { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { color: #1a56db; }
        @media only screen and (max-width: 600px) {
            .outer-padding { padding: 16px 8px 24px !important; }
            .inner-card { border-radius: 12px !important; }
            .section-pad { padding-left: 16px !important; padding-right: 16px !important; }
            .header-pad { padding: 24px 16px 0 !important; }
            .footer-pad { padding: 24px 16px 24px !important; }
            .cta-table { width: 100% !important; }
            .cta-btn { display: block !important; text-align: center !important; }
        }
    </style>
    @if(isset($extraStyles))
    <style>{!! $extraStyles !!}</style>
    @endif
</head>
<body bgcolor="#f1f5f9" style="margin:0;padding:0;background-color:#f1f5f9;color:#334155;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-font-smoothing:antialiased;">

{{-- Preheader --}}
@if(isset($preheader))
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#f1f5f9;">{{ $preheader }}</div>
@endif

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f5f9" style="background-color:#f1f5f9;">
<tr><td align="center" class="outer-padding" bgcolor="#f1f5f9" style="padding:32px 16px 40px;background-color:#f1f5f9;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" class="inner-card" style="max-width:560px;background-color:#ffffff;border-radius:12px;border:1px solid #e2e8f0;border-collapse:separate;overflow:hidden;">

        {{-- Accent bar (solid colour — gradients are unsupported in many clients) --}}
        <tr><td height="4" bgcolor="#1a56db" style="height:4px;background-color:#1a56db;font-size:0;line-height:0;mso-line-height-rule:exactly;">&nbsp;</td></tr>

        {{-- Logo --}}
        <tr><td class="header-pad" bgcolor="#ffffff" style="padding:28px 32px 0;background-color:#ffffff;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr>
                    <td style="vertical-align:middle;">
                        @if($logoUrl)
                            <img src="{{ $logoUrl }}" alt="{{ $appName }}" height="32" style="height:32px;width:auto;display:block;border:0;">
                        @else
                            <div style="font-size:22px;font-weight:800;color:#0f172a;letter-spacing:-0.3px;">{{ $appName }}</div>
                        @endif
                    </td>
                    @if(isset($badge))
                    <td align="right" style="vertical-align:middle;">
                        <span style="display:inline-block;padding:4px 12px;background-color:#dbeafe;color:#1e40af;font-size:11px;font-weight:600;border-radius:20px;letter-spacing:0.5px;text-transform:uppercase;">{{ $badge }}</span>
                    </td>
                    @endif
                </tr>
            </table>
        </td></tr>

        {{-- Content slot --}}
        {{ $slot }}

        {{-- Footer --}}
        <tr><td class="section-pad footer-pad" bgcolor="#ffffff" style="padding:32px 32px 32px;background-color:#ffffff;">
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                <tr><td height="1" bgcolor="#e2e8f0" style="height:1px;background-color:#e2e8f0;font-size:0;line-height:0;">&nbsp;</td></tr>
            </table>
            @if(isset($footerText))
                <div style="font-size:12px;color:#64748b;line-height:1.6;text-align:center;margin-top:20px;">
                    {!! $footerText !!}
                </div>
            @endif
            <div style="font-size:11px;color:#64748b;text-align:center;margin-top:{{ isset($footerText) ? '12px' : '20px' }};">
                &copy; {{ date('Y') }} {{ $appName }} &middot; <a href="{{ $appUrl }}" style="color:#64748b;text-decoration:underline;">{{ preg_replace('#^https?://#', '', $appUrl) }}</a>
            </div>
        </td></tr>

    </table>

</td></tr>
</table>

</body>
</html>
