# SEO and automatic sitemap system

The canonical production domain is **https://usp.com.pk** (no www).

## What updates automatically

Laravel serves `/sitemap.xml` as a live sitemap index. It links to `/sitemap-pages.xml` and separate, automatically numbered files for active services, published articles, active service categories, and active blog categories. Each content file contains at most 1,000 URLs and only retrieves the fields needed for XML.

Every request reads current database state. Creating, editing, renaming, disabling, unpublishing, or deleting content affects the next sitemap request. No cron, queue worker, cache invalidation observer, or manual rebuild is required. Future-dated published articles become public and enter the sitemap when their publication time arrives. Null publication dates remain supported for existing published records.

- XML includes canonical absolute URLs and available content images.
- Content modification dates reflect record updates; view/like/comment counters no longer update those timestamps. Historical timestamps previously changed by counters are not rewritten.
- Static and category-page dates are omitted rather than inventing freshness dates.
- ETags allow unchanged sitemap content to return HTTP 304. Responses require revalidation.
- Private, inactive, draft, future-dated, search, and filtered URLs are not included.
- `php artisan sitemap:generate` remains compatible with deployment scripts but now saves only a **private index snapshot** in `storage/app/private/seo/sitemap.xml`. The public sitemap never depends on that snapshot.

## Production configuration

Set these in the deployed `.env`:

```dotenv
APP_ENV=production
APP_URL=https://usp.com.pk
SEO_URL=https://usp.com.pk
SEO_INDEXABLE=true
```

Then rebuild cached configuration with `php artisan config:cache`. Keep `SEO_INDEXABLE=false` on staging and development. Without an explicit override, indexing defaults to enabled only in the production environment. The example environment deliberately disables indexing.

Optional values:

```dotenv
# Use a real, publicly accessible brand image; leave blank until one is available.
SEO_DEFAULT_IMAGE=
GOOGLE_SITE_VERIFICATION=
BING_SITE_VERIFICATION=
YANDEX_SITE_VERIFICATION=
BAIDU_SITE_VERIFICATION=
```

Paste only the verification token, not the entire HTML tag. Search-engine ownership verification still requires the account owner to finish verification in the relevant console. Article/service images override the default social image. No fabricated business address, ratings, social handle, or review data is emitted.

## Remove obsolete static copies on deployment

The repository's old `public/robots.txt` and `public/sitemap.xml` were removed because static files take precedence over Laravel routes. File uploads do not always delete obsolete server files: remove existing copies from the deployed public directory (including `public_html` on cPanel), then purge any cached copies at your CDN.

Apache's supplied front-controller rewrite handles these routes when static copies are absent. With Nginx, ensure extension-specific static-file rules do not return 404 for these endpoints. For example, route discovery requests through Laravel:

```nginx
location = /robots.txt {
    try_files $uri /index.php?$query_string;
}
location ~ ^/sitemap(?:-[a-z-]+(?:-[0-9]+)?)?\.xml$ {
    try_files $uri /index.php?$query_string;
}
```

Adapt this to the existing site's PHP/FastCGI configuration. The standard `location / { try_files $uri $uri/ /index.php?$query_string; }` also works when no conflicting extension rule exists. Preserve the revalidation headers rather than applying a long CDN cache lifetime to these endpoints.

## Metadata and indexability

`app/Support/Seo.php` is the single metadata source for active React pages. It supplies unique titles/descriptions, configured-domain canonicals, Open Graph/social cards, and safely encoded JSON-LD. Article editor SEO titles/descriptions are honored. JSON-LD uses Organization, WebSite, WebPage/CollectionPage/ContactPage, BreadcrumbList, BlogPosting, and Service as appropriate. Visible breadcrumbs use the same source.

Paginated listing pages have self-referencing canonicals with `?page=N`; tracking parameters are excluded. Out-of-range pages return HTTP 404. Search/filter pages, authentication, dashboards, profiles, and account settings receive noindex directives. Public robots rules let crawlers read account-page noindex tags; authentication remains the access control. Staging blocks crawling and also emits noindex.

Public responses include readable, escaped HTML content and links inside the app root before React starts. This is visible to all visitors, including those without JavaScript, and uses the same content records. React replaces it with the interactive interface. This is a Blade fallback, **not React server rendering/hydration**, and its layout is simpler; interactive forms, account features, and chat require JavaScript. There is no bot-specific rendering.

## Search-console rollout

1. Open `https://usp.com.pk/robots.txt` and confirm it advertises `https://usp.com.pk/sitemap.xml` and does not contain a site-wide `Disallow: /` on production.
2. Open the index and its child sitemaps; all URLs should use HTTPS and the selected domain.
3. Verify ownership in Google Search Console and Bing Webmaster Tools (and any other console you use), then submit **https://usp.com.pk/sitemap.xml** once. The engine can revisit that same URL for updates.
4. Inspect a service and an article using Google's URL Inspection and Rich Results Test; inspect source HTML for canonical, robots, structured data, and content. Not every schema type produces a rich result.
5. Configure HTTPS and permanent redirects for HTTP/www aliases at the host/CDN, once the certificates and alias domains are configured. Canonical tags alone do not perform redirects.

No search-engine accounts were connected, no sitemap was submitted externally, and no production server configuration was changed. Discovery and indexing timing remain controlled by each search engine; sitemap freshness does not guarantee indexing or ranking.

## Verification

- `php artisan test --compact`: 71 tests, 334 assertions, including 12 SEO cases.
- `npm run build`: passed; existing stale Browserslist-data warning remains.
- Tests cover canonical domain isolation, pagination, noindex, safe schema encoding, article metadata, readable HTML, sitemap XML/ETags, automatic edits/deletions/publication, bounded splitting, and stable lastmod dates.
- Browser checks use an isolated SQLite database and exercise public pages with JavaScript enabled and disabled, plus the live robots/sitemap HTTP endpoints.

## Standards used

- [Google: build and submit sitemaps](https://developers.google.com/search/docs/crawling-indexing/sitemaps/build-sitemap)
- [Google: JavaScript SEO](https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics)
- [Google: canonical URLs](https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls)
- [Bing Webmaster Guidelines](https://www.bing.com/webmasters/help/bing-webmaster-guidelines-30fba23a)
- [Sitemaps XML protocol](https://www.sitemaps.org/protocol.html)

The implementation uses shared web standards rather than separate competing SEO systems for each search engine.
