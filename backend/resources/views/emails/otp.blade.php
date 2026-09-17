@extends('emails.layout')

@section('content')
<h1 style="margin: 0 0 16px 0; font-size: 24px; font-weight: 800; color: #0F172A; letter-spacing: -0.5px; text-align: center;">
    Your Verification Code
</h1>

<p style="margin: 0 0 24px 0; font-size: 14.5px; line-height: 22px; color: #475569; text-align: center;">
    Hello {{ $name ?? 'there' }}, use the one-time verification code (OTP) below to authenticate your action on AUTOSECURE.
</p>

<!-- OTP Box Card -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 28px 0;">
    <tr>
        <td align="center">
            <div style="display: inline-block; background-color: #F8FAFC; border: 2px dashed #DE8635; border-radius: 12px; padding: 18px 36px; text-align: center;">
                <span style="font-family: 'Courier New', Courier, monospace, Helvetica; font-size: 32px; font-weight: 800; letter-spacing: 10px; color: #1E2538; display: block; margin-left: 10px;">
                    {{ $otp ?? $code ?? '1234' }}
                </span>
            </div>
        </td>
    </tr>
</table>

<p style="margin: 0 0 20px 0; font-size: 13.5px; line-height: 20px; color: #64748B; text-align: center;">
    ⏱️ This code will expire in <strong style="color: #0F172A;">{{ $expiresIn ?? '10' }} minutes</strong>.
</p>

<p style="margin: 0; font-size: 13px; line-height: 19px; color: #94A3B8; text-align: center;">
    If you did not request this verification code, someone may have entered your email by mistake. You can safely ignore this email.
</p>
@endsection
