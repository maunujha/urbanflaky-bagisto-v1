<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SitemapController extends Controller
{
    /**
     * Serve a dynamic XML sitemap of all public, indexable URLs.
     *
     * Cached for an hour so catalog crawls never hammer the database. The
     * cache is keyed on the channel base URL so it stays correct per domain.
     */
    public function index(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn () => $this->build());

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
        ]);
    }

    /**
     * Build the sitemap XML from catalog, CMS and blog content.
     */
    protected function build(): string
    {
        $locale = app()->getLocale();

        $urls = [];

        /* Static, always-indexable pages. */
        foreach (['', 'contact-us', 'blog', 'faqs'] as $path) {
            $urls[] = ['loc' => url($path), 'priority' => $path === '' ? '1.0' : '0.6'];
        }

        /* Visible, individually purchasable products served at /{url_key}. */
        DB::table('product_flat')
            ->where('status', 1)
            ->where('visible_individually', 1)
            ->whereNull('parent_id')
            ->whereNotNull('url_key')
            ->where('locale', $locale)
            ->orderBy('product_id')
            ->select('url_key', 'updated_at')
            ->get()
            ->each(function ($p) use (&$urls) {
                $urls[] = [
                    'loc'     => url($p->url_key),
                    'lastmod' => $this->date($p->updated_at),
                    'priority' => '0.8',
                ];
            });

        /* Active categories served at /{slug} (root category excluded). */
        DB::table('category_translations as ct')
            ->join('categories as c', 'c.id', '=', 'ct.category_id')
            ->where('c.status', 1)
            ->whereNotNull('c.parent_id')
            ->where('ct.locale', $locale)
            ->whereNotNull('ct.slug')
            ->orderBy('c.id')
            ->select('ct.slug', 'c.updated_at')
            ->get()
            ->each(function ($c) use (&$urls) {
                $urls[] = [
                    'loc'     => url($c->slug),
                    'lastmod' => $this->date($c->updated_at),
                    'priority' => '0.7',
                ];
            });

        /* CMS pages served at /{url_key}. */
        DB::table('cms_page_translations as cpt')
            ->join('cms_pages as cp', 'cp.id', '=', 'cpt.cms_page_id')
            ->where('cpt.locale', $locale)
            ->whereNotNull('cpt.url_key')
            ->orderBy('cpt.cms_page_id')
            ->select('cpt.url_key', 'cp.updated_at')
            ->get()
            ->each(function ($p) use (&$urls) {
                $urls[] = [
                    'loc'     => url($p->url_key),
                    'lastmod' => $this->date($p->updated_at),
                    'priority' => '0.5',
                ];
            });

        /* Published blog posts served at /blog/{slug}. */
        if (DB::getSchemaBuilder()->hasTable('blogs')) {
            DB::table('blogs')
                ->where('status', 1)
                ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->orderByDesc('published_at')
                ->select('slug', 'updated_at')
                ->get()
                ->each(function ($b) use (&$urls) {
                    $urls[] = [
                        'loc'     => url('blog/'.$b->slug),
                        'lastmod' => $this->date($b->updated_at),
                        'priority' => '0.6',
                    ];
                });
        }

        return $this->render($urls);
    }

    /**
     * Render the collected URLs into a urlset XML document.
     */
    protected function render(array $urls): string
    {
        $body = '';

        foreach ($urls as $url) {
            $body .= '  <url>'.PHP_EOL;
            $body .= '    <loc>'.htmlspecialchars($url['loc'], ENT_XML1).'</loc>'.PHP_EOL;

            if (! empty($url['lastmod'])) {
                $body .= '    <lastmod>'.$url['lastmod'].'</lastmod>'.PHP_EOL;
            }

            if (! empty($url['priority'])) {
                $body .= '    <priority>'.$url['priority'].'</priority>'.PHP_EOL;
            }

            $body .= '  </url>'.PHP_EOL;
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL
            .$body
            .'</urlset>'.PHP_EOL;
    }

    /**
     * Format a timestamp as a W3C date, or null when unparseable.
     */
    protected function date($value): ?string
    {
        if (! $value) {
            return null;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->toAtomString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}
