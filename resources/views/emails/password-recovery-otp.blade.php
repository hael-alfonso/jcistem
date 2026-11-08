<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>JCISTEM password reset code</title>
    <style>
        @media only screen and (max-width: 480px) {
            .email-content { padding: 25px 20px 27px !important; }
            .email-heading { font-size: 24px !important; }
            .code-tile { height: 44px !important; line-height: 44px !important; font-size: 21px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#f4f7fa;color:#1d344b;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">Your JCISTEM password reset code expires in 10 minutes.</div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#f4f7fa;">
        <tr><td align="center" style="padding:28px 12px;">
            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="max-width:560px;background:#ffffff;border-radius:10px;">
                <tr><td class="email-content" style="padding:30px 34px 32px;">
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
                        <td valign="middle" style="padding-right:13px;"><img src="cid:jcistem-logo@jcistem.local" width="42" height="46" alt="JCISTEM logo" style="display:block;width:42px;height:46px;object-fit:contain;border:0;"></td>
                        <td valign="middle">
                            <p style="margin:0;color:#132b46;font-size:21px;font-weight:700;line-height:1.2;">JCISTEM</p>
                            <p style="margin:4px 0 0;color:#607589;font-size:12px;line-height:1.4;">JCI Carmona member portal</p>
                        </td>
                    </tr></table>

                    <p style="margin:34px 0 8px;color:#087cb5;font-size:11px;font-weight:700;letter-spacing:1.3px;text-transform:uppercase;">Account recovery</p>
                    <h1 class="email-heading" style="margin:0;color:#132b46;font-size:27px;font-weight:700;line-height:1.25;">Password reset code</h1>
                    <p style="margin:15px 0 0;color:#465c70;font-size:15px;line-height:1.65;">Hi {{ $name }}, enter this code in JCISTEM to reset your password.</p>

                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-top:28px;">
                        <tr>
                            <td style="padding-bottom:11px;color:#1d344b;font-size:12px;font-weight:700;">Your 6-digit code</td>
                            <td align="right" style="padding-bottom:11px;color:#607589;font-size:12px;">Expires in 10 minutes</td>
                        </tr>
                    </table>
                    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="width:100%;max-width:380px;table-layout:fixed;">
                        <tr>
                            @foreach (str_split($code) as $digit)
                                <td width="16.66%" style="padding-right:{{ $loop->last ? '0' : '6px' }};">
                                    <div class="code-tile" style="height:52px;border-radius:9px;background:#eef5f9;color:#132b46;font-size:24px;font-weight:700;line-height:52px;text-align:center;">{{ $digit }}</div>
                                </td>
                            @endforeach
                        </tr>
                    </table>
                    <p style="margin:17px 0 0;color:#607589;font-size:12px;line-height:1.6;">The code can be used once. Request a new one if it expires.</p>

                    <p style="margin:29px 0 0;color:#607589;font-size:12px;line-height:1.6;">Didn't request this? You can safely ignore this email.</p>
                </td></tr>
            </table>
            <p style="margin:16px 0 0;color:#718596;font-size:11px;line-height:1.5;">JCI Carmona &middot; JCISTEM member portal</p>
        </td></tr>
    </table>
</body>
</html>
