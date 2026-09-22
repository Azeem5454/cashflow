{{-- Bulletproof table-based button + plain-text fallback URL. Vars: $url, $label --}}
<tr><td class="section-pad" bgcolor="#ffffff" style="padding:28px 32px 0;background-color:#ffffff;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr><td align="center">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" class="cta-table">
                <tr>
                    <td align="center" bgcolor="#1a56db" style="background-color:#1a56db;border-radius:8px;mso-padding-alt:14px 32px;">
                        <a href="{{ $url }}" class="cta-btn" target="_blank"
                           style="display:inline-block;padding:14px 32px;background-color:#1a56db;color:#ffffff;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;font-size:15px;font-weight:600;line-height:20px;text-decoration:none;border-radius:8px;border:1px solid #1a56db;">
                            <span style="color:#ffffff;">{{ $label }}</span>
                        </a>
                    </td>
                </tr>
            </table>
        </td></tr>
    </table>
</td></tr>
<tr><td class="section-pad" bgcolor="#ffffff" style="padding:16px 32px 0;background-color:#ffffff;">
    <div style="font-size:12px;color:#64748b;text-align:center;line-height:1.6;word-break:break-all;">
        If the button doesn't work, copy and paste this link into your browser:<br>
        <a href="{{ $url }}" style="color:#1a56db;text-decoration:underline;">{{ $url }}</a>
    </div>
</td></tr>
