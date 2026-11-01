<?php

namespace Gabha\Notifications\Support;

/**
 * Plain-text version of an HTML email, for emails without their own text
 * template: keeps paragraphs, list and table rows, and writes links as
 * "text (url)" so every button still works in text-only clients.
 */
class HtmlToText
{
    public static function convert(string $html): string
    {
        /* Head, styles and the hidden inbox preheader are not content. */
        $html = preg_replace('#<(head|style|script)\b[^>]*>.*?</\1>#is', '', $html);
        $html = preg_replace('#<div[^>]*display:\s*none[^>]*>.*?</div>#is', '', $html);

        $html = preg_replace_callback('#<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>#is', function ($m) {
            $text = trim(strip_tags($m[2]));
            $url = html_entity_decode($m[1]);

            if (str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:') || $text === $url || $text === '') {
                return $text ?: $url;
            }

            return $text.' ('.$url.')';
        }, $html);

        $html = preg_replace('#<br\s*/?>#i', "\n", $html);
        $html = preg_replace('#</(p|div|h[1-6]|tr|li|table)>#i', "\n", $html);
        $html = preg_replace('#</td>#i', '  ', $html);

        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        /* Invisible preheader filler characters. */
        $text = preg_replace('/[\x{200B}-\x{200D}\x{2007}\x{FEFF}\x{034F}]/u', '', $text);

        $lines = array_map(fn ($line) => trim(preg_replace('/[ \t]+/', ' ', $line)), explode("\n", $text));

        return trim(preg_replace("/\n{3,}/", "\n\n", implode("\n", $lines)));
    }
}
