<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;
use App\Models\Blog;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\BlogCategory;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate the sitemap for the website';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating sitemap...');

        $sitemap = Sitemap::create();

        // Add static pages
        $sitemap->add(Url::create(route('home'))
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(1.0));

        $sitemap->add(Url::create(route('services.index'))
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
            ->setPriority(0.9));

        $sitemap->add(Url::create(route('blogs.index'))
            ->setLastModificationDate(now())
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
            ->setPriority(0.9));

        // Add services
        Service::where('is_active', true)->each(function (Service $service) use ($sitemap) {
            $sitemap->add(Url::create(route('services.show', $service->slug))
                ->setLastModificationDate($service->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY)
                ->setPriority(0.8));
        });

        // Add service categories
        ServiceCategory::where('is_active', true)->each(function (ServiceCategory $category) use ($sitemap) {
            $sitemap->add(Url::create(route('services.category', $category->slug))
                ->setLastModificationDate($category->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.7));
        });

        // Add published blogs
        Blog::where('is_published', true)->each(function (Blog $blog) use ($sitemap) {
            $sitemap->add(Url::create(route('blogs.show', $blog->slug))
                ->setLastModificationDate($blog->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.8));
        });

        // Add blog categories
        BlogCategory::where('is_active', true)->each(function (BlogCategory $category) use ($sitemap) {
            $sitemap->add(Url::create(route('blogs.category', $category->slug))
                ->setLastModificationDate($category->updated_at)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(0.7));
        });

        // Write to public directory
        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully at public/sitemap.xml!');
        
        return Command::SUCCESS;
    }
}
