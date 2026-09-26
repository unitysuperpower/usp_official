<?php

namespace App\Services;

use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;

class SitemapService
{
    public const PAGE_SIZE = 1000;

    public const SECTIONS = ['services', 'blogs', 'service-categories', 'blog-categories'];

    public function query(string $section): Builder
    {
        return match ($section) {
            'services' => Service::query()->where('is_active', true),
            'blogs' => Blog::query()->published(),
            'service-categories' => ServiceCategory::query()->where('is_active', true),
            'blog-categories' => BlogCategory::query()->where('is_active', true),
            default => abort(404),
        };
    }

    public function locations(): array
    {
        if (! config('seo.indexable')) {
            return [];
        }
        $locations = [Seo::url('/sitemap-pages.xml')];
        foreach (self::SECTIONS as $section) {
            $pages = (int) ceil($this->query($section)->count() / self::PAGE_SIZE);
            for ($page = 1; $page <= $pages; $page++) {
                $locations[] = Seo::url("/sitemap-{$section}-{$page}.xml");
            }
        }

        return $locations;
    }

    public function index(): string
    {
        $body = '';
        foreach ($this->locations() as $url) {
            $body .= '<sitemap><loc>'.$this->escape($url).'</loc></sitemap>';
        }

        return $this->document('sitemapindex', $body);
    }

    public function pages(): string
    {
        $body = '';
        if (config('seo.indexable')) {
            foreach (['home', 'services.index', 'blogs.index', 'contact'] as $route) {
                $body .= '<url><loc>'.$this->escape(Seo::route($route)).'</loc></url>';
            }
        }

        return $this->document('urlset', $body);
    }

    public function section(string $section, int $page): string
    {
        abort_unless(config('seo.indexable') && $page >= 1 && $page <= 50000, 404);
        $columns = ['id', 'slug', 'updated_at'];
        if ($section === 'services') {
            $columns[] = 'image';
        } elseif ($section === 'blogs') {
            $columns[] = 'featured_image';
        }
        $items = $this->query($section)->select($columns)->orderBy('id')->forPage($page, self::PAGE_SIZE)->get();
        abort_if($items->isEmpty(), 404);
        $route = match ($section) {
            'services' => 'services.show',
            'blogs' => 'blogs.show',
            'service-categories' => 'services.category',
            'blog-categories' => 'blogs.category',
        };
        $body = '';
        foreach ($items as $item) {
            $body .= '<url><loc>'.$this->escape(Seo::route($route, $item->slug)).'</loc>';
            // Omit category dates: child additions/deletions change those pages too.
            if (in_array($section, ['services', 'blogs'], true) && $item->updated_at) {
                $body .= '<lastmod>'.$item->updated_at->toAtomString().'</lastmod>';
            }
            $image = $item->featured_image ?? $item->image;
            if ($image) {
                $body .= '<image:image><image:loc>'.$this->escape(Seo::url('storage/'.$image)).'</image:loc></image:image>';
            }
            $body .= '</url>';
        }

        return $this->document('urlset', $body);
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private function document(string $root, string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<'.$root.' xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">'.$body.'</'.$root.'>';
    }
}
