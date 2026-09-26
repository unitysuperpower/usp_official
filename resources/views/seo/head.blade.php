<title>{{ $seo['title'] }}</title>
<meta name="description" content="{{ $seo['description'] }}">
<meta name="robots" content="{{ $seo['robots'] }}">
@if($seo['canonical'])
<link rel="canonical" href="{{ $seo['canonical'] }}">
@endif
@if($seo['public'])
<meta property="og:site_name" content="{{ config('seo.name') }}">
<meta property="og:type" content="{{ $seo['type'] }}">
<meta property="og:title" content="{{ $seo['title'] }}">
<meta property="og:description" content="{{ $seo['description'] }}">
<meta property="og:url" content="{{ $seo['canonical'] }}">
<meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $seo['title'] }}">
<meta name="twitter:description" content="{{ $seo['description'] }}">
@if($seo['image'])
<meta property="og:image" content="{{ $seo['image'] }}">
<meta property="og:image:alt" content="{{ $seo['heading'] }}">
<meta name="twitter:image" content="{{ $seo['image'] }}">
<meta name="twitter:image:alt" content="{{ $seo['heading'] }}">
@endif
@if($page === 'blogs.show')
@if(!empty($props['blog']['published_at']))
<meta property="article:published_time" content="{{ $props['blog']['published_at'] }}">
@endif
<meta property="article:modified_time" content="{{ $props['blog']['updated_at'] }}">
@endif
@foreach(config('seo.verification') as $name => $value)
@if($value)<meta name="{{ $name }}" content="{{ $value }}">@endif
@endforeach
<script type="application/ld+json">{!! json_encode($seo['schema'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) !!}</script>
@endif
