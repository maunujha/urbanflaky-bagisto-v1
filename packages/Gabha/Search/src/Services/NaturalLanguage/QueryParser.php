<?php

declare(strict_types=1);

namespace Gabha\Search\Services\NaturalLanguage;

/**
 * Turns a free-text storefront query into a {@see SearchIntent}.
 *
 * Deliberately PURE: it depends only on the config array handed to the
 * constructor (no facades, no DB, no container), so it is fully unit-testable
 * and cheap enough to run on every search. The service provider binds it with
 * `config('gabha-search.natural_language')`; tests construct it with a fixture.
 *
 * The parser works by consuming spans of the query in priority order — price
 * cues, named sections, colours and gender are each detected and removed, and
 * whatever survives (minus stopwords) becomes the full-text `cleanQuery`. The
 * product type is detected non-destructively so it stays in the full-text query
 * where the Meilisearch synonym engine (tee<->tshirt, hoodie<->sweatshirt) can
 * act on it.
 */
class QueryParser
{
    /**
     * @param  array<string, mixed>  $config  the `natural_language` config block
     */
    public function __construct(protected array $config = []) {}

    /**
     * Parse a raw query string into structured search intent.
     */
    public function parse(?string $query): SearchIntent
    {
        $original = trim((string) $query);

        $work = $this->normalize($original);

        $matches = [];

        [$priceMin, $priceMax] = $this->extractPrice($work, $matches);

        $categorySlug = $this->extractCategory($work, $matches);

        $color = $this->extractColor($work, $matches);

        $gender = $this->extractGender($work, $matches);

        // Non-destructive: read from the full normalized query so it is recorded
        // for analytics while remaining in the cleaned full-text query.
        $productType = $this->detectProductType($this->normalize($original), $matches);

        return new SearchIntent(
            original: $original,
            cleanQuery: $this->cleanup($work),
            color: $color,
            priceMin: $priceMin,
            priceMax: $priceMax,
            gender: $gender,
            productType: $productType,
            categorySlug: $categorySlug,
            matches: $matches,
        );
    }

    /**
     * Lowercase + whitespace-collapse a query, surrounded by single spaces so
     * whole-word regexes have boundaries to anchor on at both ends.
     */
    protected function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');

        // Drop apostrophes so "men's"/"women's" collapse to the bare keyword.
        $value = (string) preg_replace('/[\x{0027}\x{2019}\x{0060}]/u', '', $value);

        return ' '.trim((string) preg_replace('/\s+/u', ' ', $value)).' ';
    }

    /**
     * Extract a price range. Requires an explicit cue (under/over/between/₹…) so
     * a bare number ("100% cotton") is never mistaken for a budget.
     *
     * @param  array<string, string>  $matches
     * @return array{0: float|null, 1: float|null}  [min, max] in base currency
     */
    protected function extractPrice(string &$work, array &$matches): array
    {
        $price = (array) ($this->config['price'] ?? []);

        $cur = $this->currencyGroup($price['currency_keywords'] ?? []);
        $curOpt = $cur !== null ? $cur.'?' : '';
        $maxKw = $this->keywordGroup($price['max_keywords'] ?? []);
        $minKw = $this->keywordGroup($price['min_keywords'] ?? []);
        $num = '(\d{2,6})';

        $min = null;
        $max = null;

        $consume = function (string $pattern, callable $apply) use (&$work): void {
            $work = (string) preg_replace_callback($pattern, function ($m) use ($apply) {
                $apply($m);

                return ' ';
            }, $work, 1);
        };

        // 1. Range: "between 300 and 500", "300-500", "300 to 500".
        if ($min === null && $max === null) {
            $consume(
                "/(?:between\\s+)?{$curOpt}\\s*{$num}\\s*(?:-|–|to|and)\\s*{$curOpt}\\s*(\\d{2,6})/u",
                function ($m) use (&$min, &$max, &$matches) {
                    $a = (float) $m[1];
                    $b = (float) $m[2];
                    $min = min($a, $b);
                    $max = max($a, $b);
                    $matches['price'] = trim($m[0]);
                }
            );
        }

        // 2. Max budget: "under 300", "below ₹500", "< 400".
        if ($max === null && $maxKw !== null) {
            $consume("/(?:{$maxKw})\\s*{$curOpt}\\s*{$num}/u", function ($m) use (&$max, &$matches) {
                $max = (float) $m[1];
                $matches['price'] = trim($m[0]);
            });
        }

        if ($max === null) {
            $consume("/<\\s*{$num}/u", function ($m) use (&$max, &$matches) {
                $max = (float) $m[1];
                $matches['price'] = trim($m[0]);
            });
        }

        // 3. Min budget: "over 200", "above ₹300", "> 250".
        if ($min === null && $minKw !== null) {
            $consume("/(?:{$minKw})\\s*{$curOpt}\\s*{$num}/u", function ($m) use (&$min, &$matches) {
                $min = (float) $m[1];
                $matches['price'] = trim(($matches['price'] ?? '').' '.$m[0]);
            });
        }

        if ($min === null) {
            $consume("/>\\s*{$num}/u", function ($m) use (&$min, &$matches) {
                $min = (float) $m[1];
                $matches['price'] = trim(($matches['price'] ?? '').' '.$m[0]);
            });
        }

        // 4. Bare currency with no comparator ("₹300", "300 rs") — treat per config.
        if ($cur !== null && $min === null && $max === null && ($price['bare_currency_as'] ?? 'max') !== 'ignore') {
            $apply = function ($m) use (&$max, &$matches) {
                $max = (float) $m[1];
                $matches['price'] = trim($m[0]);
            };

            $consume("/{$cur}\\s*{$num}/u", $apply);

            if ($max === null) {
                $consume("/{$num}\\s*(?:{$cur}|\\/-)/u", $apply);
            }
        }

        return [$min, $max];
    }

    /**
     * Match an explicitly named section phrase ("bottom wear", "combos") and
     * return its category slug. Longer phrases are tried first.
     *
     * @param  array<string, string>  $matches
     */
    protected function extractCategory(string &$work, array &$matches): ?string
    {
        $map = (array) ($this->config['categories'] ?? []);

        $phrases = array_keys($map);

        usort($phrases, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        foreach ($phrases as $phrase) {
            $pattern = '/\b'.$this->phrasePattern($phrase).'\b/u';

            if (preg_match($pattern, $work)) {
                $work = (string) preg_replace($pattern, ' ', $work, 1);
                $matches['category'] = $phrase;

                return (string) $map[$phrase];
            }
        }

        return null;
    }

    /**
     * Match an unambiguous colour word and return its canonical option name.
     *
     * @param  array<string, string>  $matches
     */
    protected function extractColor(string &$work, array &$matches): ?string
    {
        // When colour drives a hard filter the word is consumed (removed from the
        // full-text query). When it doesn't, the word is LEFT in the query so
        // Meilisearch can still match it against product names / the black<->dark
        // synonym — keeping the colour signal even where the colour attribute is
        // unset. Either way the canonical colour is recorded as intent.
        $asFilter = (bool) ($this->config['color_as_filter'] ?? true);

        foreach ((array) ($this->config['colors'] ?? []) as $word => $canonical) {
            $pattern = '/\b'.preg_quote((string) $word, '/').'\b/u';

            if (preg_match($pattern, $work)) {
                if ($asFilter) {
                    $work = (string) preg_replace($pattern, ' ', $work, 1);
                }

                $matches['color'] = (string) $word;

                return (string) $canonical;
            }
        }

        return null;
    }

    /**
     * Match a gender keyword and return its canonical intent ('men' | 'women').
     *
     * @param  array<string, string>  $matches
     */
    protected function extractGender(string &$work, array &$matches): ?string
    {
        foreach ((array) ($this->config['gender'] ?? []) as $intent => $definition) {
            foreach ((array) ($definition['keywords'] ?? []) as $keyword) {
                $pattern = '/\b'.preg_quote((string) $keyword, '/').'\b/u';

                if (preg_match($pattern, $work)) {
                    $work = (string) preg_replace($pattern, ' ', $work, 1);
                    $matches['gender'] = (string) $keyword;

                    return (string) $intent;
                }
            }
        }

        return null;
    }

    /**
     * Detect (without removing) the product type for analytics. Left in the
     * full-text query so Meilisearch synonyms resolve tee/tshirt, hoodie/etc.
     *
     * @param  array<string, string>  $matches
     */
    protected function detectProductType(string $normalized, array &$matches): ?string
    {
        foreach ((array) ($this->config['product_types'] ?? []) as $canonical => $aliases) {
            foreach ((array) $aliases as $alias) {
                if (preg_match('/\b'.$this->phrasePattern((string) $alias).'\b/u', $normalized)) {
                    $matches['product_type'] = (string) $alias;

                    return (string) $canonical;
                }
            }
        }

        return null;
    }

    /**
     * Drop stopwords + leftover connective/currency tokens and collapse the
     * residue into the full-text query Meilisearch should run.
     */
    protected function cleanup(string $work): string
    {
        $stopwords = array_map('strval', (array) ($this->config['stopwords'] ?? []));

        $currency = array_map(
            fn ($c) => mb_strtolower((string) $c, 'UTF-8'),
            (array) (($this->config['price']['currency_keywords'] ?? []))
        );

        $drop = array_flip(array_merge($stopwords, $currency, ['to', 'and', 'or', '-', '–', '/-']));

        $tokens = array_filter(
            preg_split('/\s+/u', trim($work)) ?: [],
            function ($token) use ($drop) {
                if ($token === '' || isset($drop[$token])) {
                    return false;
                }

                // Drop tokens that carry no alphanumeric signal (stray punctuation).
                return preg_match('/[\p{L}\p{N}]/u', $token) === 1;
            }
        );

        return trim(implode(' ', $tokens));
    }

    /**
     * Build a non-capturing alternation of currency cues (longest first so
     * "rs." beats "rs"), or null when none are configured.
     *
     * @param  array<int, string>  $keywords
     */
    protected function currencyGroup(array $keywords): ?string
    {
        $keywords = array_values(array_filter(array_map('strval', $keywords)));

        if (empty($keywords)) {
            return null;
        }

        usort($keywords, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return '(?:'.implode('|', array_map(fn ($k) => preg_quote($k, '/'), $keywords)).')';
    }

    /**
     * Build a non-capturing alternation of multi-word keyword cues, or null.
     *
     * @param  array<int, string>  $keywords
     */
    protected function keywordGroup(array $keywords): ?string
    {
        $keywords = array_values(array_filter(array_map('strval', $keywords)));

        if (empty($keywords)) {
            return null;
        }

        usort($keywords, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));

        return implode('|', array_map(fn ($k) => $this->phrasePattern($k), $keywords));
    }

    /**
     * Escape a phrase for regex and allow flexible inner whitespace so
     * "less than" matches "less  than" too.
     */
    protected function phrasePattern(string $phrase): string
    {
        return str_replace(' ', '\s+', preg_quote(trim($phrase), '/'));
    }
}
