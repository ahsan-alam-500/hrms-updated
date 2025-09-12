<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <meta name="x-apple-disable-message-reformatting">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Password Reset OTP</title>

  <style type="text/css">
    body {
      margin: 0;
      padding: 0;
      -webkit-text-size-adjust: 100%;
      background-color: #f0f0f0;
      color: #000000;
    }

    table,
    td {
      border-collapse: collapse;
      vertical-align: top;
    }

    .u-row {
      width: 100% !important;
      max-width: 600px;
      margin: 0 auto;
    }

    .u-col {
      display: block;
      width: 100% !important;
    }

    img {
      border: none;
      display: block;
      max-width: 100%;
      height: auto;
    }

    a[x-apple-data-detectors] {
      color: inherit !important;
      text-decoration: none !important;
    }

    @media only screen and (min-width: 620px) {
      .u-row .u-col-100 {
        width: 600px !important;
      }
    }

    @media (max-width: 480px) {
      .hide-mobile {
        display: none !important;
        max-height: 0px;
        overflow: hidden;
      }
    }
  </style>

  <!--[if !mso]><!-->
  <link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet" type="text/css">
  <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,700&display=swap" rel="stylesheet" type="text/css">
  <!--<![endif]-->
</head>

<body>

  <!-- Top Logo -->
  <table class="u-row" bgcolor="#ffffff" cellpadding="0" cellspacing="0" style="margin-top:10px;margin-bottom:10px;">
    <tr>
      <td align="center" style="padding:20px;">
        <img src="https://sardaritbd.com/wp-content/uploads/2023/10/Sardarit-logo-updated-1.png" alt="{{ config('app.name') }}"
          width="150">
      </td>
    </tr>
  </table>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" bgcolor="#f0f0f0">
    <tr>
      <td align="center">

        <!-- Banner -->
        <table class="u-row" style="background: #a5b7f0;" cellpadding="0" cellspacing="0">
          <tr>
            <td align="center" style="padding: 20px;">
              <img src="{{ asset('public/otpimage.png') }}" alt="{{ config('app.name') }} Banner" width="190">
            </td>
          </tr>
        </table>

        <!-- Main Content -->
        <table class="u-row" bgcolor="#ffffff" cellpadding="0" cellspacing="0">
          <tr>
            <td style="padding: 30px 20px 10px; text-align:center;">
              <h1 style="margin:0; font-family:'Montserrat',sans-serif; font-size:22px; font-weight:700; color:#000;">
                Hi {{ $userName ?? 'User' }}, your one-time code is:
              </h1>
            </td>
          </tr>
          <tr>
            <td style="padding: 10px; text-align:center;">
              <a href="#"
                style="display:inline-block; font-size:22px; color:#000; border:2px solid #000; padding:14px 30px; text-decoration:none; font-weight:700; letter-spacing:6px;">
                {{ $otpCode }}
              </a>
            </td>
          </tr>
          <tr>
            <td
              style="padding: 20px; text-align:center; font-family:Arial, Helvetica, sans-serif; font-size:14px; color:#444;">
              <p style="margin:0;">This code is valid for <strong>2 minutes</strong>. Please enter it to continue with your password reset.</p>
              <p style="margin:0;">If you did not request a password reset, you can safely ignore this email.</p>
              <p style="margin-top:20px;">Best regards,<br><strong>The {{ config('app.name') }} Custom dev Team</strong></p>
            </td>
          </tr>
        </table>

        <!-- Footer -->
        <table class="u-row" bgcolor="#ffffff" cellpadding="0" cellspacing="0" style="margin-top:10px;margin-bottom:20px;">
          <tr>
            <td style="padding:20px; text-align:center; font-size:13px; color:#777777; font-family:Arial, sans-serif;">
              &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br>
              Need help? Contact us at
              <a href="mailto:ahsanulalam.500@gmail.com" style="color:#4a90e2; text-decoration:none;">ahsanulalam.500@gmail.com</a>
            </td>
          </tr>
        </table>

      </td>
    </tr>
  </table>
</body>

</html>
