{{ $heading }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
@foreach ($details as $label => $value)
@if ($value !== null && $value !== '')
{{ $label }}: {{ $value }}
@endif
@endforeach
@if ($actionLabel && $actionUrl)

{{ $actionLabel }}: {{ $actionUrl }}
@endif
@if ($footnote)

{{ $footnote }}
@endif

{{ config('mail.from.name') ?: config('app.name') }} — {{ url('/') }}
