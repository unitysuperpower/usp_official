<div class="section" data-server-content>
    <nav aria-label="Education"><a href="/">Home</a> · <a href="/courses">Courses</a> · <a href="/learn">My learning</a></nav>
    <h1>{{ $seo['heading'] }}</h1><p>{{ $seo['description'] }}</p>
    @if(isset($props['course']))
        <article><p>{{ $props['course']['description'] }}</p><p>{{ $props['course']['instructor'] }} · {{ $props['course']['duration_hours'] }} hours</p><p>{{ $props['course']['currency'] }} {{ number_format($props['course']['fee_minor'] / 100, 2) }}</p></article>
        <h2>Curriculum</h2><ol>@foreach($props['lessons'] as $lesson)<li>{{ $lesson['title'] }}</li>@endforeach</ol>
        <h2>Open batches</h2>@foreach($props['batches'] as $batch)<p>{{ $batch['name'] }} · {{ $batch['schedule'] }} · {{ substr($batch['starts_on'], 0, 10) }}</p>@endforeach
    @else
        @foreach($props['courses']['data'] as $course)<article><h2><a href="/courses/{{ $course['slug'] }}">{{ $course['title'] }}</a></h2><p>{{ $course['summary'] }}</p></article>@endforeach
        @if($props['courses']['last_page'] > 1)<nav aria-label="Pagination">@foreach($props['courses']['links'] as $link)@if($link['url'])<a href="{{ $link['url'] }}">{{ html_entity_decode(strip_tags($link['label'])) }}</a>@endif @endforeach</nav>@endif
    @endif
    <noscript><p>Enable JavaScript to apply, manage payments, and access the learning workspace.</p></noscript>
</div>
