<?php

namespace App\Console\Commands;

use App\Services\SitemapService;
use App\Support\Seo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';

    protected $description = 'Generate a private sitemap-index snapshot; public sitemaps always update live';

    public function handle(SitemapService $sitemap): int
    {
        $directory = storage_path('app/private/seo');
        File::ensureDirectoryExists($directory);
        File::replace($directory.'/sitemap.xml', $sitemap->index());
        $this->info('Live sitemap: '.Seo::url('/sitemap.xml'));
        $this->info('Private index snapshot saved to storage/app/private/seo/sitemap.xml. No public static files are generated.');
        if (! config('seo.indexable')) {
            $this->warn('Indexing is disabled. Set SEO_INDEXABLE=true only on the public production site.');
        }

        return self::SUCCESS;
    }
}
