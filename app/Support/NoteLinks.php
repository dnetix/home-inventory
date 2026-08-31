<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Renders item notes with clickable links: `[Title](https://url)` becomes an
 * anchor labelled "Title", and bare http(s) URLs become anchors with a
 * compacted label (scheme/www stripped, truncated). Everything else is
 * HTML-escaped, so notes stay plain text otherwise.
 */
class NoteLinks
{
    /**
     * Markdown-style titled link, or a bare URL (trailing punctuation excluded).
     */
    private const PATTERN = '/\[([^\[\]]+)\]\((https?:\/\/[^\s)]+)\)|(https?:\/\/[^\s<>]+?)(?=[.,;:!?]*(?:\s|$))/u';

    private const ANCHOR_CLASS = 'font-semibold text-accent underline decoration-accent/40 underline-offset-2 hover:decoration-accent';

    public static function toHtml(?string $note): HtmlString
    {
        return new HtmlString(self::replace($note, function (string $url, string $label): string {
            return '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer" class="'.self::ANCHOR_CLASS.'">'.e($label).'</a>';
        }, escape: true));
    }

    /**
     * Link-free version for one-line list rows: titled links collapse to their
     * title, bare URLs to their compact form.
     */
    public static function toPlain(?string $note): string
    {
        return self::replace($note, fn (string $url, string $label): string => $label, escape: false);
    }

    /**
     * @param  callable(string, string): string  $renderLink  ($url, $label) => replacement
     */
    private static function replace(?string $note, callable $renderLink, bool $escape): string
    {
        $note = (string) $note;
        $out = '';
        $offset = 0;

        preg_match_all(self::PATTERN, $note, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

        foreach ($matches as $match) {
            [$full, $at] = $match[0];
            $before = substr($note, $offset, $at - $offset);
            $out .= $escape ? e($before) : $before;

            if (isset($match[3])) {
                $url = $match[3][0];
                $label = self::compact($url);
            } else {
                $url = $match[2][0];
                $label = trim($match[1][0]);
            }

            $out .= $renderLink($url, $label);
            $offset = $at + strlen($full);
        }

        $rest = substr($note, $offset);

        return $out.($escape ? e($rest) : $rest);
    }

    /**
     * A bare URL's display label: no scheme or www, capped length.
     */
    private static function compact(string $url): string
    {
        $label = (string) preg_replace('/^https?:\/\/(www\.)?/i', '', $url);

        return Str::limit(rtrim($label, '/'), 32, '…');
    }
}
