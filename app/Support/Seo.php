<?php

namespace App\Support;

use Illuminate\Support\Str;

class Seo
{
    public static function url(string $path = '/'): string
    {
        return rtrim(config('seo.url'), '/').'/'.ltrim($path, '/');
    }

    public static function route(string $name, mixed $parameters = []): string
    {
        return self::url(route($name, $parameters, false));
    }

    public static function text(?string $html): string
    {
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html ?? '');
        $html = preg_replace('#</?(?:p|div|br|li|h[1-6])\b[^>]*>#i', ' ', $html);

        return Str::squish(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    public static function image(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return preg_match('#^https?://#i', $path) ? $path : self::url($path);
    }

    public static function forPage(string $page, array $props): array
    {
        $public = in_array($page, ['home', 'services.index', 'services.category', 'services.show', 'blogs.index', 'blogs.category', 'blogs.show', 'contact'], true);
        $blog = str_starts_with($page, 'blogs.');
        $item = $props['blog'] ?? $props['service'] ?? null;
        $category = $props['category'] ?? null;
        $listing = $props['blogs'] ?? $props['services'] ?? null;
        $pageNumber = is_array($listing) ? ($listing['current_page'] ?? 1) : 1;
        $heading = match ($page) {
            'home' => 'Big ideas. Beautifully built.',
            'services.index' => 'Solutions built around you.',
            'blogs.index' => 'Ideas worth exploring.',
            'services.category', 'blogs.category' => $category['name'],
            'services.show', 'blogs.show' => $item['title'],
            'contact' => 'Great things start with a conversation.',
            default => Str::headline(str_replace(['admin.', 'auth.', '.'], ['', '', ' '], $page)),
        };
        $title = match ($page) {
            'home' => 'Digital Design & Technology',
            'services.index' => 'Our Services',
            'blogs.index' => 'Insights & Articles',
            'services.category' => $heading.' Services',
            'blogs.category' => $heading.' Articles',
            'contact' => 'Contact Us',
            default => $item['meta_title'] ?? $heading,
        };
        $description = self::text(($item['meta_description'] ?? null) ?: ($item['excerpt'] ?? $item['short_description'] ?? $item['content'] ?? $item['description'] ?? $category['description'] ?? match ($page) {
            'services.index' => 'Discover how we can bring your next project to life. Explore the services available from USP Tech Solution.',
            'blogs.index' => 'Perspectives on technology, design, and what comes next. Read the latest articles from USP Tech Solution.',
            'contact' => 'Tell USP Tech Solution about your project. Get in touch to discuss your goals and the services you need.',
            default => config('seo.description'),
        }));
        $description = Str::limit($description, 160, '…');
        $canonical = $public ? self::url(request()->getPathInfo()) : null;
        if ($pageNumber > 1) {
            $canonical .= '?page='.$pageNumber;
            $title .= ' — Page '.$pageNumber;
        }
        $filtered = $page === 'services.index' && (request()->filled('search') || request()->filled('category'));
        $indexable = $public && config('seo.indexable') && ! $filtered;
        $imagePath = $item['featured_image'] ?? $item['image'] ?? null;
        $image = $imagePath ? self::url('storage/'.$imagePath) : self::image(config('seo.image'));
        $breadcrumbs = [['name' => 'Home', 'url' => self::url()]];
        if ($public && $page !== 'home') {
            if ($page !== 'contact') {
                $breadcrumbs[] = ['name' => $blog ? 'Insights' : 'Services', 'url' => self::url($blog ? '/blogs' : '/services')];
            }
            if ($category) {
                $breadcrumbs[] = ['name' => $category['name'], 'url' => self::route($blog ? 'blogs.category' : 'services.category', $category['slug'])];
            } elseif ($item) {
                if (! empty($item['category']['is_active'])) {
                    $breadcrumbs[] = ['name' => $item['category']['name'], 'url' => self::route($blog ? 'blogs.category' : 'services.category', $item['category']['slug'])];
                }
                $breadcrumbs[] = ['name' => $heading, 'url' => $canonical];
            } elseif ($page === 'contact') {
                $breadcrumbs[] = ['name' => 'Contact', 'url' => $canonical];
            }
        }
        $organization = ['@type' => 'Organization', '@id' => self::url().'#organization', 'name' => config('seo.name'), 'url' => self::url()];
        $website = ['@type' => 'WebSite', '@id' => self::url().'#website', 'name' => config('seo.name'), 'url' => self::url(), 'publisher' => ['@id' => $organization['@id']]];
        $webpage = ['@type' => $page === 'contact' ? 'ContactPage' : ($listing ? 'CollectionPage' : 'WebPage'), '@id' => $canonical.'#webpage', 'url' => $canonical, 'name' => $title, 'description' => $description, 'isPartOf' => ['@id' => $website['@id']], 'inLanguage' => str_replace('_', '-', app()->getLocale())];
        $graph = [$organization, $website, $webpage];
        if (count($breadcrumbs) > 1) {
            $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => array_map(fn ($crumb, $index) => ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $crumb['name'], 'item' => $crumb['url']], $breadcrumbs, array_keys($breadcrumbs))];
        }
        if ($item && $blog) {
            $article = ['@type' => 'BlogPosting', '@id' => $canonical.'#article', 'headline' => $item['title'], 'description' => $description, 'mainEntityOfPage' => ['@id' => $webpage['@id']], 'publisher' => ['@id' => $organization['@id']]];
            if (! empty($item['user']['name'])) {
                $article['author'] = ['@type' => 'Person', 'name' => $item['user']['name']];
            }
            foreach (['published_at' => 'datePublished', 'updated_at' => 'dateModified'] as $key => $property) {
                if (! empty($item[$key])) {
                    $article[$property] = $item[$key];
                }
            }
            if ($image) {
                $article['image'] = [$image];
            }
            $graph[] = $article;
        } elseif ($item) {
            $graph[] = array_filter(['@type' => 'Service', '@id' => $canonical.'#service', 'name' => $item['title'], 'description' => $description, 'url' => $canonical, 'image' => $image, 'provider' => ['@id' => $organization['@id']]]);
        }

        return compact('public', 'heading', 'description', 'canonical', 'indexable', 'image', 'breadcrumbs') + [
            'title' => self::text($title).' | '.config('seo.name'),
            'type' => $page === 'blogs.show' ? 'article' : 'website',
            'robots' => $indexable ? 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1' : 'noindex, follow',
            'schema' => $public ? ['@context' => 'https://schema.org', '@graph' => $graph] : null,
        ];
    }
}
