<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to the System</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f9; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f6f9; padding: 40px 0;">
        <tr>
            <td>
                <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px; background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); overflow: hidden;">
                    
                    <tr>
                        <td height="6" style="background-color: #1e3a8a;"></td>
                    </tr>

                    <tr>
                        <td style="padding: 40px 40px 20px 40px;">
                            <span style="color: #1e3a8a; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; display: block; margin-bottom: 12px;">Security Alert & Onboarding</span>
                            <h1 style="color: #0f172a; font-size: 26px; font-weight: 700; margin: 0; line-height: 1.3;">Welcome, {{ $admin->full_name }}</h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px 30px 40px; color: #334155; font-size: 15px; line-height: 1.6;">
                            <p style="margin: 0 0 20px 0;">Your administrative credentials have been successfully initialized and configured through secure Google Identity verification.</p>
                            
                            <p style="margin: 0 0 25px 0;">Because you authenticated directly via your organizational Google account, your profile bypasses manual activation barriers, granting you immediate access to infrastructure management tools.</p>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 30px;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                            <tr>
                                                <td width="30%" style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; padding-bottom: 4px;">System Role</td>
                                                <td style="color: #0f172a; font-size: 14px; font-weight: 600; padding-bottom: 4px;">System Administrator</td>
                                            </tr>
                                            <tr>
                                                <td width="30%" style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase;">Identifier</td>
                                                <td style="color: #0f172a; font-size: 14px; font-family: 'Courier New', Courier, monospace;">{{ $admin->email }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left" style="padding-bottom: 20px;">
                                        <a href="http://127.0.0.1:8000" target="_blank" style="background-color: #1e3a8a; color: #ffffff; text-decoration: none; padding: 12px 28px; font-size: 14px; font-weight: 600; border-radius: 5px; display: inline-block; transition: background-color 0.2s ease;">Launch Management Console</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 40px;">
                            <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 0;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px 40px 40px 40px; background-color: #fafafa;">
                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td style="color: #94a3b8; font-size: 12px; line-height: 1.5; text-align: left;">
                                        This automated security transmission was dispatched by the Rental Room Management backend core.<br>
                                        If this account creation framework was initiated without your explicit authorization, please audit your Google Sign-In access keys immediately.
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>