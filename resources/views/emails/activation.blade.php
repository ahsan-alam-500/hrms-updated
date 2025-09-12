<!DOCTYPE html>
<html lang="en" style="margin: 0; padding: 0;">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Account Activated</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f5f8fa;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: #333333;
    }

    .email-wrapper {
      max-width: 600px;
      margin: 40px auto;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
      overflow: hidden;
    }

    .email-header {
      background: linear-gradient(135deg, #4a90e2, #357ABD);
      color: #ffffff;
      padding: 25px;
      text-align: center;
      font-size: 22px;
      font-weight: 700;
      letter-spacing: 1px;
    }

    .email-body {
      padding: 35px 40px;
      font-size: 16px;
      line-height: 1.7;
      color: #444444;
    }

    .btn-box {
      text-align: center;
      margin: 30px 0;
    }

    .btn-primary {
      display: inline-block;
      padding: 14px 32px;
      font-size: 16px;
      font-weight: 600;
      background: #4a90e2;
      color: #ffffff;
      text-decoration: none;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(74, 144, 226, 0.3);
    }

    .btn-primary:hover {
      background: #357ABD;
    }

    .email-footer {
      background: #f9fafb;
      padding: 20px 30px;
      font-size: 13px;
      text-align: center;
      color: #777777;
    }

    .email-footer a {
      color: #4a90e2;
      text-decoration: none;
    }

    @media only screen and (max-width: 480px) {
      .email-body {
        padding: 20px;
        font-size: 14px;
      }

      .btn-primary {
        padding: 12px 24px;
        font-size: 15px;
      }
    }
  </style>
</head>

<body>
  <div class="email-wrapper">

    <!-- Header -->
    <div class="email-header">
      Sardar IT
    </div>

    <!-- Body -->
    <div class="email-body">
      <h2 style="color:#4a90e2; margin-top:0;">Hi {{ $userName ?? 'User' }},</h2>
      <p>Your account has been <strong style="color:green;">activated</strong> by the Admin.</p>
      <p>You can now log in and start using your dashboard.</p>

      <div class="btn-box">
        <a href="https://sardarit-hrms.vercel.app" target="_blank" class="btn-primary">Login to Dashboard</a>
      </div>

      <p style="margin-top:30px;">Thank you,<br><strong>Sardar IT Team</strong></p>
    </div>

    <!-- Footer -->
    <div class="email-footer">
      &copy; {{ date('Y') }} Sardar IT. All rights reserved.<br />
      Need help? Contact us at <a href="mailto:ahsanulalam.500@gmail.com">ahsanulalam.500@gmail.com</a>.
    </div>
  </div>
</body>
</html>
