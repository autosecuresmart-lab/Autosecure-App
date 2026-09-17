@extends('emails.layout')

@section('content')
<h1 style="margin: 0 0 16px 0; font-size: 24px; font-weight: 800; color: #0F172A; letter-spacing: -0.5px; text-align: center;">
    Reset Your Password
</h1>

<p style="margin: 0 0 20px 0; font-size: 14.5px; line-height: 22px; color: #475569; text-align: center;">
    Hello {{ $name ?? 'there' }}, we received a request to reset the password for your AUTOSECURE account.
</p>

<!-- OTP / Reset Code Box -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 28px 0;">
    <tr>
        <td align="center">
            <div style="display: inline-block; background-color: #F8FAFC; border: 2px dashed #DE8635; border-radius: 12px; padding: 18px 36px; text-align: center;">
                <span style="font-family: 'Courier New', Courier, monospace, Helvetica; font-size: 32px; font-weight: 800; letter-spacing: 10px; color: #1E2538; display: block; margin-left: 10px;">
                    {{ $token ?? $otp ?? '6930' }}
                </span>
            </div>
        </td>
    </tr>
</table>

@if(!empty($resetUrl))
<!-- Direct App Button (if deep link supported) -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 24px 0;">
    <tr>
        <td align="center">
            <a href="{{ $resetUrl }}" style="display: inline-block; background-color: #1E2538; color: #FFFFFF; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 32px; border-radius: 10px; box-shadow: 0 4px 12px rgba(30, 37, 56, 0.25);">
                Open in AUTOSECURE App &rarr;
            </a>
        </td>
    </tr>
</table>
@endif

<p style="margin: 0 0 18px 0; font-size: 13.5px; line-height: 20px; color: #64748B; text-align: center;">
    ⏱️ This reset code is valid for <strong style="color: #0F172A;">{{ $expiresIn ?? '60' }} minutes</strong>.
</p>

<p style="margin: 0; font-size: 13px; line-height: 19px; color: #94A3B8; text-align: center;">
    If you did not request a password reset, you can safely disregard this message. Your password will remain unchanged.
</p>
@endsection
