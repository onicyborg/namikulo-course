<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title')</title>
</head>
<body style="margin:0;padding:0;background:#f6f7fb;color:#1b2437;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;font-size:1px;line-height:1px;color:#f6f7fb;max-height:0;max-width:0;opacity:0;overflow:hidden;">@yield('preheader')</div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f6f7fb;">
        <tr><td align="center" style="padding:32px 16px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;max-width:600px;background:#ffffff;border:1px solid #e6e9f2;">
                <tr><td style="padding:28px 32px;background:#1b2437;color:#ffffff;">
                    <p style="margin:0 0 18px;font-size:13px;font-weight:700;letter-spacing:2px;">NAMIKULO COURSE</p>
                    <h1 style="margin:0;font-size:25px;line-height:1.3;font-weight:700;color:#ffffff;">@yield('heading')</h1>
                </td></tr>
                <tr><td style="padding:30px 32px 34px;font-size:15px;line-height:1.6;">@yield('content')</td></tr>
                <tr><td style="padding:20px 32px;border-top:1px solid #e6e9f2;color:#6b7690;font-size:12px;line-height:1.6;">
                    Email otomatis dari Namikulo Course terkait pesanan Anda. Jika Anda tidak mengenali pesanan ini, hubungi admin melalui situs.
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
