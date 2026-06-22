<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'Gigateam Notification' }}</title>
</head>
<body style="margin:0;padding:0;background:#f0f0f0;font-family:Arial,sans-serif;font-size:14px;color:#1a1a1a;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#f0f0f0;padding:30px 0;">
<tr><td align="center">
<table width="620" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:6px;overflow:hidden;box-shadow:0 2px 12px rgba(0,0,0,0.1);max-width:620px;">

    {{-- ── HEADER ── --}}
    <tr>
        <td style="background:#1a1a1a;padding:20px 30px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="vertical-align:middle;">
                        <img src="{{ config('app.url') }}/images/gigateam-logo.png"
                             alt="Gigateam Solutions"
                             width="55" height="55"
                             style="display:inline-block;vertical-align:middle;margin-right:12px;border-radius:4px;">
                        <span style="display:inline-block;vertical-align:middle;">
                            <span style="display:block;color:#ffffff;font-size:17px;font-weight:900;letter-spacing:1px;text-transform:uppercase;">GIGATEAM SOLUTIONS</span>
                            <span style="display:block;color:#DC2626;font-size:11px;font-style:italic;margin-top:2px;">Secured &amp; Connected</span>
                        </span>
                    </td>
                    <td align="right" style="vertical-align:middle;">
                        <span style="background:#DC2626;color:#ffffff;font-size:11px;font-weight:700;padding:5px 14px;border-radius:3px;text-transform:uppercase;letter-spacing:1px;display:inline-block;">{{ $badgeText ?? 'Notification' }}</span>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ── COLOR BAR ── --}}
    <tr>
        <td style="padding:0;font-size:0;line-height:0;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="60%" style="background:#DC2626;height:5px;font-size:0;line-height:0;">&nbsp;</td>
                    <td width="40%" style="background:#16a34a;height:5px;font-size:0;line-height:0;">&nbsp;</td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- ── BODY ── --}}
    <tr>
        <td style="padding:28px 30px;">
            @yield('content')
        </td>
    </tr>

    {{-- ── FOOTER ── --}}
    <tr>
        <td style="background:#f8f8f8;border-top:1px solid #eeeeee;padding:18px 30px;text-align:center;">
            <p style="margin:0;font-size:13px;font-weight:700;color:#1a1a1a;">Gigateam Solutions Limited</p>
            <p style="margin:5px 0 0;font-size:11px;color:#aaaaaa;">
                White Angle House, 1st Floor &ndash; Suite 62, Nairobi &nbsp;&bull;&nbsp;
                <a href="mailto:sales@gigateamltd.com" style="color:#DC2626;text-decoration:none;">sales@gigateamltd.com</a>
                &nbsp;&bull;&nbsp; +254 111292948
            </p>
            <p style="margin:5px 0 0;font-size:10px;color:#bbbbbb;">
                This is an automated message from your Gigateam POS system. Do not reply to this email.
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>