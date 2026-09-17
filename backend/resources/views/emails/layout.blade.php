<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? 'AUTOSECURE' }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; height: 100% !important; background-color: #F8FAFC; font-family: Helvetica, Arial, -apple-system, sans-serif; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC; color: #1E293B;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; table-layout: fixed;">
        <tr>
            <td align="center" style="padding: 40px 16px 40px 16px;">
                <!-- Main Container -->
                <table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 580px; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05); border: 1px solid #E2E8F0;">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #1E2538; padding: 36px 32px 32px 32px; border-bottom: 3px solid #DE8635;">
                            <table border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <!-- Logo Badge & Wordmark -->
                                        <div style="display: inline-block; vertical-align: middle;">
                                            <span style="font-size: 26px; font-weight: 800; font-style: italic; color: #FFFFFF; letter-spacing: -0.5px;">auto</span><span style="font-size: 26px; font-weight: 800; font-style: italic; color: #DE8635; letter-spacing: -0.5px;">Secure</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin: 8px 0 0 0; font-size: 13px; color: #94A3B8; letter-spacing: 0.5px; text-transform: uppercase; font-weight: 600;">
                                Vehicle Security & Care Ecosystem
                            </p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 36px 36px 28px 36px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Security Notice / Divider -->
                    <tr>
                        <td style="padding: 0 36px;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="border-top: 1px solid #F1F5F9; padding: 20px 0;">
                                        <p style="margin: 0; font-size: 12px; line-height: 18px; color: #94A3B8; text-align: center;">
                                            🔒 This is an automated security transmission from AUTOSECURE. Never share your OTP or account verification codes with anyone.
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #FAFBFD; padding: 24px 36px; border-top: 1px solid #E2E8F0; text-align: center;">
                            <p style="margin: 0 0 6px 0; font-size: 13px; font-weight: 700; color: #1E2538;">
                                AUTOSECURE 2.0
                            </p>
                            <p style="margin: 0 0 12px 0; font-size: 12px; color: #64748B;">
                                Smart vehicle tracking, fleet security & verified automotive care.
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #94A3B8;">
                                &copy; {{ date('Y') }} AUTOSECURE. All rights reserved. &bull; Lagos, Nigeria
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
