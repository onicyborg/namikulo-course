<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>@yield('title')</title>
</head>
<body style="margin:0;padding:0;background:#f3f6f8;color:#0f172a;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
    <div style="display:none!important;font-size:1px;line-height:1px;color:#f3f6f8;max-height:0;max-width:0;opacity:0;overflow:hidden;mso-hide:all;">@yield('preheader')</div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;background:#f3f6f8;">
        <tr><td align="center" style="padding:32px 16px 40px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;max-width:600px;border-collapse:separate;background:#ffffff;border:1px solid #dce5e9;border-radius:12px;">
                <tr><td style="padding:0;height:5px;background:#0f766e;font-size:1px;line-height:1px;border-radius:12px 12px 0 0;">&nbsp;</td></tr>
                <tr><td style="padding:27px 32px 0;">
                    <a href="{{ route('site.home') }}" style="color:#0f766e;text-decoration:none;font-size:15px;font-weight:700;letter-spacing:1.2px;">NAMIKULO <span style="color:#0f172a;">COURSE</span></a>
                </td></tr>
                <tr><td style="padding:30px 32px 0;">
                    <p style="margin:0 0 10px;color:#0f766e;font-size:11px;line-height:1.5;font-weight:700;letter-spacing:1.4px;">@yield('eyebrow', 'PEMBARUAN AKUN')</p>
                    <h1 style="margin:0;color:#0f172a;font-size:26px;line-height:1.3;font-weight:700;letter-spacing:-.5px;">@yield('heading')</h1>
                </td></tr>
                <tr><td style="padding:22px 32px 32px;color:#334155;font-size:14px;line-height:1.75;">@yield('content')</td></tr>
                <tr><td style="padding:22px 32px;border-top:1px solid #e2e8f0;background:#f8fafc;border-radius:0 0 12px 12px;color:#64748b;font-size:12px;line-height:1.7;">
                    <p style="margin:0 0 7px;">@hasSection('footer')@yield('footer')@else Email otomatis dari Namikulo Course. @endif</p>
                    <a href="{{ route('site.home') }}" style="color:#0f766e;text-decoration:underline;">Buka Namikulo Course</a>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
