<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Verification Code</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: Arial, sans-serif;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f6f9; padding: 40px 0;">
        <tr>
            <td>
                <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 500px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden;">
                    <tr><td height="6" style="background-color: #1e3a8a;"></td></tr>
                    <tr>
                        <td style="padding: 30px 40px;">
                            <h2 style="color: #0f172a; font-size: 22px; margin: 0 0 15px 0;">Verify Your Identity</h2>
                            <p style="color: #334155; font-size: 15px; line-height: 1.5; margin: 0 0 20px 0;">Hello {{ $adminName }},</p>
                            <p style="color: #334155; font-size: 15px; line-height: 1.5; margin: 0 0 25px 0;">Use the secure verification code below to complete your login session. This code is valid for 10 minutes.</p>
                            
                            <div style="background-color: #f8fafc; border: 1px dashed #cbd5e1; text-align: center; padding: 20px; border-radius: 6px; margin-bottom: 25px;">
                                <span style="font-family: 'Courier New', Courier, monospace; font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #1e3a8a;">{{ $code }}</span>
                            </div>

                            <p style="color: #64748b; font-size: 13px; line-height: 1.5; margin: 0;">If you did not attempt to sign in to your Rental Management account, please ignore this email or secure your Google account settings.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 20px 40px; background-color: #fafafa; border-top: 1px solid #e2e8f0; text-align: center; color: #94a3b8; font-size: 12px;">
                            Automated security system • Do not reply directly to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>