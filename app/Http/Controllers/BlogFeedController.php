<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use Illuminate\Http\Response;

/**
 * RSS 2.0 feed for the blog. Listed 20 most recent published posts.
 * Link discovery is in `layouts/blog.blade.php` via
 * <link rel="alternate" type="application/rss+xml">.
 */
class BlogFeedController extends Controller
{
    public function rss(): Response
    {
        $appName = config('app.name', 'TheCashFox');
        $appUrl  = rtrim(config('app.url', url('/')), '/');
        $desc    = config('app.tagline', 'Insights and product updates.');

        $posts = BlogPost::published()
            ->with(['category', 'author'])
            ->orderByDesc('published_at')
            ->limit(20)
            ->get();

        $lastBuild = ($posts->first()?->published_at ?? now())->toRfc2822String();

        $items = $posts->map(function (BlogPost $p) use ($appName) {
            return sprintf(
                "    <item>\n"
                . "      <title>%s</title>\n"
                . "      <link>%s</link>\n"
                . "      <guid isPermaLink=\"true\">%s</guid>\n"
                . "      <pubDate>%s</pubDate>\n"
                . "      <description><![CDATA[%s]]></description>\n"
                . "      %s\n"
                . "      %s\n"
                . "    </item>\n",
                htmlspecialchars($p->title, ENT_XML1),
                htmlspecialchars($p->url(), ENT_XML1),
                htmlspecialchars($p->url(), ENT_XML1),
                $p->published_at?->toRfc2822String() ?? now()->toRfc2822String(),
                $p->excerpt ?: strip_tags((string) $p->body_html),
                $p->author ? '<dc:creator>' . htmlspecialchars($p->author->name, ENT_XML1) . '</dc:creator>' : '',
                $p->category ? '<category>' . htmlspecialchars($p->category->name, ENT_XML1) . '</category>' : ''
            );
        })->implode('');

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:atom="http://www.w3.org/2005/Atom">
  <channel>
    <title>{$appName} Blog</title>
    <link>{$appUrl}/blog</link>
    <atom:link href="{$appUrl}/blog/feed.xml" rel="self" type="application/rss+xml" />
    <description>{$desc}</description>
    <language>en-us</language>
    <lastBuildDate>{$lastBuild}</lastBuildDate>
{$items}  </channel>
</rss>
XML;

        return response($xml, 200, [
            'Content-Type'  => 'application/rss+xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }
}
