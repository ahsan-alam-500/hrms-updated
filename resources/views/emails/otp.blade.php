<!DOCTYPE html>
<html lang="en" style="background-color: #f5f8fa; margin: 0; padding: 0;">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Email Template</title>
    <style>
        body {
            background-color: #f5f8fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
        }

        .email-wrapper {
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .email-header {
            background-color: #4a90e2;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
        }

        .email-body {
            padding: 30px 40px;
            color: #333333;
            font-size: 16px;
            line-height: 1.5;
        }

        .otp-code {
            display: inline-block;
            margin: 20px 0;
            padding: 15px 25px;
            font-size: 28px;
            font-weight: 700;
            background-color: #f0f4ff;
            border: 2px dashed #4a90e2;
            border-radius: 8px;
            letter-spacing: 6px;
            color: #4a90e2;
            user-select: all;
        }

        .email-footer {
            padding: 20px 40px;
            font-size: 14px;
            color: #888888;
            text-align: center;
            background-color: #fafafa;
        }

        @media only screen and (max-width: 480px) {
            .email-wrapper {
                margin: 10px;
                width: auto !important;
            }

            .email-body {
                padding: 20px;
                font-size: 14px;
            }

            .otp-code {
                font-size: 24px;
                padding: 10px 20px;
            }
        }
    </style>
</head>

<body>
    <div class="email-wrapper">
        <div class="email-header">
            Your Company Name
        </div>
        <div class="email-body">
            <p>Dear {{ $userName ?? 'User' }},</p>

            <p>We received a request to reset your password. Please use the following OTP code to proceed:</p>

            <div class="otp-code">{{ $otpCode }}</div>

            <p>This code is valid for the next 5 minutes. If you did not request a password reset, please ignore this
                email.</p>

            <p>Thank you,<br />The Your Company Team</p>
        </div>
        <div class="email-footer">
            &copy; {{ date('Y') }} Your Company Name. All rights reserved.<br />
            If you have any questions, contact us at <a
                href="mailto:ahsanulalam.500@gmail.com">ahsanulalam.500@gmail.com</a>.
        </div>
    </div>
</body>

</html>
