<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f3ee;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#1f2a24;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f3ee;padding:32px 12px;">
<tr><td align="center">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:14px;overflow:hidden;border:1px solid #e3e5dc;">
    <tr><td style="background:#173d2f;padding:22px 32px;">
        <span style="display:inline-block;width:34px;height:34px;line-height:34px;text-align:center;border-radius:8px;background:#ffffff;color:#173d2f;font-weight:800;font-size:18px;">u.</span>
        <span style="color:#ffffff;font-weight:700;font-size:17px;margin-left:10px;vertical-align:middle;">{{ config('mail.from.name') ?: config('app.name') }}</span>
    </td></tr>
    <tr><td style="padding:32px 32px 8px;">
        <h1 style="margin:0 0 18px;font-size:23px;line-height:1.3;color:#173d2f;">{{ $heading }}</h1>
        @foreach ($lines as $line)
            <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#33413a;">{{ $line }}</p>
        @endforeach
    </td></tr>
    @if (! empty(array_filter($details, fn ($v) => $v !== null && $v !== '')))
    <tr><td style="padding:6px 32px 8px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e3e5dc;border-radius:10px;border-collapse:separate;">
            @foreach ($details as $label => $value)
                @continue($value === null || $value === '')
                <tr>
                    <td style="padding:10px 14px;font-size:13px;color:#6b7670;width:38%;border-bottom:1px solid #eef0e9;vertical-align:top;">{{ $label }}</td>
                    <td style="padding:10px 14px;font-size:14px;color:#1f2a24;font-weight:600;border-bottom:1px solid #eef0e9;word-break:break-all;">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    </td></tr>
    @endif
    @if ($actionLabel && $actionUrl)
    <tr><td style="padding:22px 32px 6px;">
        <a href="{{ $actionUrl }}" style="display:inline-block;background:#173d2f;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:13px 24px;border-radius:9px;">{{ $actionLabel }}</a>
    </td></tr>
    @endif
    <tr><td style="padding:22px 32px 30px;">
        @if ($footnote)
            <p style="margin:0 0 12px;font-size:13px;line-height:1.6;color:#6b7670;">{{ $footnote }}</p>
        @endif
        <p style="margin:0;font-size:12px;color:#8a948e;">{{ config('mail.from.name') ?: config('app.name') }} · <a href="{{ url('/') }}" style="color:#8a948e;">{{ preg_replace('#^https?://#', '', url('/')) }}</a></p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
