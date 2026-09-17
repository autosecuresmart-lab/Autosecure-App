@extends('emails.layout')

@section('content')
<h1 style="margin: 0 0 16px 0; font-size: 24px; font-weight: 800; color: #0F172A; letter-spacing: -0.5px; text-align: center;">
    Welcome to autoSecure!
</h1>

<p style="margin: 0 0 20px 0; font-size: 15px; line-height: 23px; color: #475569;">
    Hello {{ $name ?? 'there' }},
</p>

<p style="margin: 0 0 20px 0; font-size: 14.5px; line-height: 22px; color: #475569;">
    Thank you for registering with <strong>AUTOSECURE</strong>. Your account has been created, giving you immediate access to real-time vehicle monitoring, automated maintenance records, and top-tier security services.
</p>

<!-- Feature Box -->
<table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; margin: 0 0 24px 0; padding: 20px;">
    <tr>
        <td>
            <p style="margin: 0 0 10px 0; font-size: 14px; font-weight: 700; color: #1E2538;">
                Here is what you can do right away:
            </p>
            <ul style="margin: 0; padding-left: 20px; font-size: 13.5px; line-height: 22px; color: #475569;">
                <li>Add and manage your vehicle profiles</li>
                <li>Connect GPS trackers and smart dashcams</li>
                <li>Track vehicle care history and schedule renewal reminders</li>
                <li>Locate verified automotive care vendors across Nigeria</li>
            </ul>
        </td>
    </tr>
</table>

<p style="margin: 0; font-size: 14px; line-height: 21px; color: #475569; text-align: center;">
    Ready to hit the road? Launch your AUTOSECURE mobile app anytime to get started!
</p>
@endsection
