<?php

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\SitemapService;

beforeEach(function () {
    $this->withoutVite();
    config(['seo.url' => 'https://usp.com.pk', 'seo.indexable' => true]);
});

function seoArticle(array $attributes = []): Blog
{
    $author = User::factory()->create();
    $category = BlogCategory::firstOrCreate(['slug' => 'insights'], ['name' => 'Insights', 'is_active' => true]);

    return Blog::create(array_merge(['user_id' => $author->id, 'category_id' => $category->id, 'title' => 'Designing useful websites', 'slug' => 'designing-useful-websites', 'content' => '<p>Useful websites start with clear content.</p>', 'is_published' => true, 'published_at' => now()->subDay()], $attributes));
}

function seoService(array $attributes = []): Service
{
    $category = ServiceCategory::firstOrCreate(['slug' => 'web'], ['name' => 'Web', 'is_active' => true]);

    return Service::create(array_merge(['category_id' => $category->id, 'title' => 'Web development', 'slug' => 'web-development', 'description' => 'A tailored website for your business.', 'price' => 100, 'price_unit' => 'project', 'is_active' => true], $attributes));
}

function seoDocument(string $html): DOMXPath
{
    $document = new DOMDocument;
    @$document->loadHTML($html);

    return new DOMXPath($document);
}

test('public metadata uses the canonical domain and ignores tracking parameters and request hosts', function () {
    $response = $this->get('http://untrusted.example/services?utm_source=test')->assertOk();
    $html = seoDocument($response->getContent());
    expect($html->query('//link[@rel="canonical"]')->length)->toBe(1);
    expect($html->evaluate('string(//link[@rel="canonical"]/@href)'))->toBe('https://usp.com.pk/services');
    expect($html->evaluate('string(//meta[@property="og:url"]/@content)'))->toBe('https://usp.com.pk/services');
    expect($html->query('//title')->length)->toBe(1);
    expect($html->query('//meta[@name="description"]')->length)->toBe(1);
    expect($html->evaluate('string(//meta[@name="robots"]/@content)'))->toStartWith('index, follow');
});

test('article SEO honors editor metadata and supplies safe structured data and readable HTML', function () {
    seoArticle(['meta_title' => 'Website design insights', 'meta_description' => 'Our custom search description.', 'featured_image' => 'blogs/cover.webp', 'content' => '<p>Useful websites start with clear content.</p><script>alert("bad")</script>']);
    $response = $this->get('/blogs/designing-useful-websites')->assertOk();
    $html = seoDocument($response->getContent());
    expect($html->evaluate('string(//title)'))->toBe('Website design insights | USP Tech Solution');
    expect($html->evaluate('string(//meta[@name="description"]/@content)'))->toBe('Our custom search description.');
    expect($html->evaluate('string(//meta[@property="og:image"]/@content)'))->toBe('https://usp.com.pk/storage/blogs/cover.webp');
    expect($html->query('//*[@data-server-content]//h1')->length)->toBe(1);
    expect($html->evaluate('string(//*[@data-server-content])'))->toContain('Useful websites start with clear content.')->not->toContain('alert("bad")');
    $schema = json_decode($html->evaluate('string(//script[@type="application/ld+json"])'), true, flags: JSON_THROW_ON_ERROR);
    $article = collect($schema['@graph'])->firstWhere('@type', 'BlogPosting');
    expect($article['headline'])->toBe('Designing useful websites');
    expect($article['publisher']['@id'])->toBe('https://usp.com.pk/#organization');
    expect(collect($schema['@graph'])->firstWhere('@type', 'BreadcrumbList'))->not->toBeNull();
});

test('structured data cannot break out of its script element', function () {
    seoArticle(['title' => '</script><script>alert(1)</script>Title']);
    $html = seoDocument($this->get('/blogs/designing-useful-websites')->assertOk()->getContent());
    $json = $html->evaluate('string(//script[@type="application/ld+json"])');
    $schema = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
    expect(collect($schema['@graph'])->firstWhere('@type', 'BlogPosting')['headline'])->toContain('</script>');
    expect($json)->not->toContain('</script>');
});

test('paginated listings keep their own canonical and expose crawlable pagination links', function () {
    for ($i = 1; $i <= 13; $i++) {
        seoService(['slug' => 'service-'.$i]);
    }
    $html = seoDocument($this->get('/services?page=2&utm_source=test')->assertOk()->getContent());
    expect($html->evaluate('string(//link[@rel="canonical"]/@href)'))->toBe('https://usp.com.pk/services?page=2');
    expect($html->evaluate('string(//title)'))->toContain('Page 2');
    expect($html->query('//*[@data-server-content]//nav[@aria-label="Pagination"]/a')->length)->toBeGreaterThan(0);
});

test('account and filtered search pages carry noindex in HTML and headers', function () {
    foreach (['/login', '/register', '/services?search=website'] as $url) {
        $response = $this->get($url)->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow');
        expect(seoDocument($response->getContent())->evaluate('string(//meta[@name="robots"]/@content)'))->toBe('noindex, follow');
    }
    $this->actingAs(User::factory()->create(['is_admin' => true]))->get('/admin/dashboard')->assertOk()->assertHeader('X-Robots-Tag', 'noindex, follow');
});

test('robots advertises the live sitemap and staging blocks indexing', function () {
    $this->get('/robots.txt')->assertOk()->assertSee('Sitemap: https://usp.com.pk/sitemap.xml', false)->assertDontSee('{{APP_URL}}', false);
    config(['seo.indexable' => false]);
    $this->get('/robots.txt')->assertSee('Disallow: /', false)->assertDontSee('Sitemap:');
    $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, follow');
    $this->get('/sitemap.xml')->assertOk()->assertDontSee('<sitemap>', false);
});

test('sitemap XML automatically reflects edits unpublishing deletions and additions', function () {
    $blog = seoArticle(['featured_image' => 'blogs/a&b.webp']);
    $service = seoService();
    $index = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
    expect(simplexml_load_string($index->getContent()))->not->toBeFalse();
    $index->assertSee('https://usp.com.pk/sitemap-blogs-1.xml', false)->assertSee('https://usp.com.pk/sitemap-services-1.xml', false);
    $this->get('/sitemap-pages.xml')->assertOk()->assertSee('https://usp.com.pk/contact', false)->assertDontSee('<lastmod>', false);
    $first = $this->get('/sitemap-blogs-1.xml')->assertOk()->assertSee('blogs/a&amp;b.webp', false);
    expect(simplexml_load_string($first->getContent()))->not->toBeFalse();
    $this->withHeader('If-None-Match', $first->headers->get('ETag'))->get('/sitemap-blogs-1.xml')->assertStatus(304);
    $this->flushHeaders();
    $blog->update(['slug' => 'updated-article']);
    $this->get('/sitemap-blogs-1.xml')->assertSee('/blogs/updated-article', false)->assertDontSee('/blogs/designing-useful-websites', false);
    $blog->update(['is_published' => false]);
    $this->get('/sitemap.xml')->assertDontSee('sitemap-blogs-1.xml');
    $this->get('/sitemap-blogs-1.xml')->assertNotFound();
    $blog->update(['is_published' => true]);
    $this->get('/sitemap.xml')->assertSee('sitemap-blogs-1.xml');
    $service->delete();
    $this->get('/sitemap.xml')->assertDontSee('sitemap-services-1.xml');
});

test('future articles stay out of public pages and sitemaps until publication time', function () {
    $this->freezeTime();
    $blog = seoArticle(['published_at' => now()->addHour()]);
    $this->get('/blogs/'.$blog->slug)->assertNotFound();
    $this->get('/blogs')->assertDontSee($blog->title);
    $this->get('/')->assertDontSee($blog->title);
    $this->get('/sitemap.xml')->assertDontSee('sitemap-blogs-1.xml');
    $this->travel(2)->hours();
    $this->get('/blogs/'.$blog->slug)->assertOk();
    $this->get('/sitemap-blogs-1.xml')->assertOk()->assertSee($blog->slug);
});

test('view and engagement counters do not falsify sitemap last modification dates', function () {
    $this->freezeTime();
    $blog = seoArticle();
    $service = seoService();
    $blogDate = $blog->updated_at->toAtomString();
    $serviceDate = $service->updated_at->toAtomString();
    $this->travel(1)->days();
    $this->get('/blogs/'.$blog->slug)->assertOk();
    $this->get('/services/'.$service->slug)->assertOk();
    $this->actingAs(User::factory()->create())->postJson('/blogs/'.$blog->id.'/like')->assertOk();
    $this->post('/blogs/'.$blog->id.'/comment', ['comment' => 'Thoughtful article.'])->assertSessionHasNoErrors();
    expect($blog->refresh()->updated_at->toAtomString())->toBe($blogDate);
    expect($service->refresh()->updated_at->toAtomString())->toBe($serviceDate);
    $this->get('/sitemap-blogs-1.xml')->assertSee($blogDate, false);
    $this->get('/sitemap-services-1.xml')->assertSee($serviceDate, false);
});

test('large sitemaps split into bounded sections and reject invalid section paths', function () {
    $category = ServiceCategory::create(['name' => 'Web', 'slug' => 'web']);
    $records = [];
    for ($i = 1; $i <= SitemapService::PAGE_SIZE + 1; $i++) {
        $records[] = ['category_id' => $category->id, 'title' => 'Service '.$i, 'slug' => 'service-'.$i, 'description' => 'Description', 'price' => 100, 'price_unit' => 'project', 'is_active' => true];
    }
    foreach (array_chunk($records, 100) as $chunk) {
        Service::insert($chunk);
    }
    $this->get('/sitemap.xml')->assertSee('sitemap-services-2.xml');
    $xml = simplexml_load_string($this->get('/sitemap-services-1.xml')->assertOk()->getContent());
    expect(count($xml->url))->toBe(SitemapService::PAGE_SIZE);
    $xml = simplexml_load_string($this->get('/sitemap-services-2.xml')->assertOk()->getContent());
    expect(count($xml->url))->toBe(1);
    $this->get('/sitemap-services-3.xml')->assertNotFound();
    $this->get('/sitemap-users-1.xml')->assertNotFound();
    $this->get('/sitemap-services-0.xml')->assertNotFound();
});

test('inactive records and private routes never enter sitemaps', function () {
    seoService(['is_active' => false]);
    seoArticle(['is_published' => false]);
    $this->get('/sitemap.xml')->assertDontSee('sitemap-services-1.xml')->assertDontSee('sitemap-blogs-1.xml');
    $this->get('/sitemap-pages.xml')->assertDontSee('/login')->assertDontSee('/admin')->assertDontSee('/profile');
    expect(is_file(public_path('robots.txt')))->toBeFalse();
    expect(is_file(public_path('sitemap.xml')))->toBeFalse();
});

test('pagination beyond available results is a real not found response', function () {
    $this->get('/services?page=2')->assertNotFound();
    $this->get('/blogs?page=2')->assertNotFound();
});
