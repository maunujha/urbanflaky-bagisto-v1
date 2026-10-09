<?php

namespace App\Support;

/**
 * Render-time fixes for admin-authored rich text (product descriptions, blog
 * posts, CMS pages, category descriptions).
 */
class RichText
{
    /**
     * Demote <h1> to <h2>. The page template owns the single H1 (product name,
     * post title, …); editors often use H1 for section headings in TinyMCE,
     * which left pages with up to 14 H1s.
     */
    public static function demoteH1(?string $html): string
    {
        if (! $html || stripos($html, '<h1') === false) {
            return (string) $html;
        }

        return preg_replace('~<(/?)h1\b~i', '<$1h2', $html) ?? $html;
    }

    /**
     * Keep the first <h1> and demote the rest to <h2>. For CMS pages, whose
     * template has no heading of its own — the content's first H1 is the page H1.
     */
    public static function singleH1(?string $html): string
    {
        $position = $html ? stripos($html, '</h1>') : false;

        if ($position === false) {
            return (string) $html;
        }

        $position += strlen('</h1>');

        return substr($html, 0, $position).self::demoteH1(substr($html, $position));
    }
}
