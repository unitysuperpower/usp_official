{{-- Visible to every visitor before React mounts; no user-agent-specific content. --}}
<div class="section" data-server-content>
    <header>
        <a href="/">{{ config('seo.name') }}</a>
        <nav aria-label="Main navigation">
            <a href="/services">Services</a> · <a href="/blogs">Insights</a> · <a href="/contact">Contact</a>
        </nav>
    </header>
    <main>
        @if(count($seo['breadcrumbs']) > 1)
        <nav aria-label="Breadcrumbs"><ol>
            @foreach($seo['breadcrumbs'] as $crumb)
            <li><a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a></li>
            @endforeach
        </ol></nav>
        @endif
        <h1>{{ $seo['heading'] }}</h1>
        <p>{{ $seo['description'] }}</p>
        @php($detail = $props['blog'] ?? $props['service'] ?? null)
        @if($detail)
            <article>
                @if($detail['featured_image'] ?? $detail['image'] ?? null)
                <img src="{{ \App\Support\Seo::url('storage/'.($detail['featured_image'] ?? $detail['image'])) }}" alt="{{ $detail['title'] }}">
                @endif
                <p>{{ \App\Support\Seo::text($detail['content'] ?? $detail['description'] ?? '') }}</p>
                @if(isset($props['blog']))
                    <p>{{ $detail['user']['name'] ?? '' }}
                    @if($detail['published_at'])<time datetime="{{ $detail['published_at'] }}">{{ \Carbon\Carbon::parse($detail['published_at'])->toDateString() }}</time>@endif</p>
                @else
                    <p>${{ number_format((float) $detail['price'], 2) }} / {{ $detail['price_unit'] }}</p>
                    <ul>@foreach($detail['features'] ?? [] as $feature)<li>{{ $feature }}</li>@endforeach</ul>
                    <a href="/contact">Discuss this service</a>
                @endif
            </article>
        @endif
        @foreach(['featuredServices' => 'Services', 'latestBlogs' => 'Latest insights', 'services' => 'Services', 'blogs' => 'Articles', 'relatedServices' => 'Related services', 'relatedBlogs' => 'Related articles'] as $key => $label)
            @if(isset($props[$key]))
            <section><h2>{{ $label }}</h2>
                @php($items = $props[$key]['data'] ?? ($key === 'latestBlogs' ? array_slice($props[$key], 0, 3) : $props[$key]))
                @foreach($items as $item)
                <article>
                    <h3><a href="{{ \App\Support\Seo::route(str_contains(strtolower($key), 'blog') ? 'blogs.show' : 'services.show', $item['slug']) }}">{{ $item['title'] }}</a></h3>
                    <p>{{ \Illuminate\Support\Str::limit(\App\Support\Seo::text($item['excerpt'] ?? $item['short_description'] ?? $item['description'] ?? ''), 150) }}</p>
                </article>
                @endforeach
                @if(isset($props[$key]['last_page']) && $props[$key]['last_page'] > 1)
                <nav aria-label="Pagination">
                    @foreach($props[$key]['links'] as $link)
                        @if($link['url'])<a href="{{ $link['url'] }}">{{ html_entity_decode(strip_tags($link['label'])) }}</a>@endif
                    @endforeach
                </nav>
                @endif
            </section>
            @endif
        @endforeach
        @if($page !== 'home' && (isset($props['categories']) || isset($props['allCategories'])))
        <nav aria-label="Categories">
            @foreach($props['categories'] ?? $props['allCategories'] as $category)
                <a href="{{ \App\Support\Seo::route(str_starts_with($page, 'blogs.') ? 'blogs.category' : 'services.category', $category['slug']) }}">{{ $category['name'] }}</a>
            @endforeach
        </nav>
        @endif
        <noscript><p>Enable JavaScript to use interactive forms, account features, and chat.</p></noscript>
    </main>
</div>
