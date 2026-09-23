<?php

namespace App\Http\Controllers;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;

/**
 * /sitemap.xml — public marketing pages, the blog index, every published
 * post and every category that has published posts.
 *
 * <lastmod> is only emitted when we actually know it:
 *  - posts: updated_at (real edits only — view counts and hero pins never
 *    touch it, see BlogPost::recordView)
 *  - /blog and categories: the newest post update inside them
 *  - static pages: omitted (stamping "today" on every request tells crawlers
 *    nothing and trains them to ignore our lastmod values)
 */
class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim(config('app.url', url('/')), '/');

        $urls = [
            ['loc' => $base . '/',         'priority' => '1.0', 'changefreq' => 'weekly'],
            ['loc' => $base . '/register', 'priority' => '0.8', 'changefreq' => 'monthly'],
            ['loc' => $base . '/login',    'priority' => '0.5', 'changefreq' => 'yearly'],
            ['loc' => $base . '/terms',    'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => $base . '/privacy',  'priority' => '0.3', 'changefreq' => 'yearly'],
            ['loc' => $base . '/delete-account', 'priority' => '0.3', 'changefreq' => 'yearly'],
        ];

        try {
            $posts = BlogPost::published()
                ->with('category:id,slug')
                ->latestFirst()
                ->limit(5000)
                ->get(['id', 'slug', 'category_id', 'published_at', 'updated_at']);

            $lastmodOf = fn (BlogPost $p): ?CarbonInterface => $p->updated_at ?? $p->published_at;

            $urls[] = [
                'loc'        => $base . '/blog',
                'lastmod'    => $posts->map($lastmodOf)->filter()->max(),
                'priority'   => '0.9',
                'changefreq' => 'daily',
            ];

            foreach ($posts as $p) {
                $urls[] = [
                    'loc'        => $base . '/blog/' . $p->slug,
                    'lastmod'    => $lastmodOf($p),
                    'priority'   => '0.7',
                    'changefreq' => 'monthly',
                ];
            }

            // Categories derived from the published posts themselves (not the
            // denormalised post_count), so the list can never drift.
            $posts->filter(fn ($p) => $p->category)
                ->groupBy(fn ($p) => $p->category->slug)
                ->each(function ($group, $slug) use (&$urls, $base, $lastmodOf) {
                    $urls[] = [
                        'loc'        => $base . '/blog/category/' . $slug,
                        'lastmod'    => $group->map($lastmodOf)->filter()->max(),
                        'priority'   => '0.5',
                        'changefreq' => 'weekly',
                    ];
                });
        } catch (\Throwable $e) {
            report($e);
            // DB unreachable — still serve the static URLs + blog index.
            $urls[] = ['loc' => $base . '/blog', 'priority' => '0.9', 'changefreq' => 'daily'];
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
             . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= '  <url><loc>' . htmlspecialchars($u['loc'], ENT_XML1) . '</loc>'
                  . (! empty($u['lastmod']) ? '<lastmod>' . $u['lastmod']->toAtomString() . '</lastmod>' : '')
                  . '<changefreq>' . $u['changefreq'] . '</changefreq>'
                  . '<priority>' . $u['priority'] . '</priority></url>' . "\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
