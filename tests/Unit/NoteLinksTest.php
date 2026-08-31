<?php

namespace Tests\Unit;

use App\Support\NoteLinks;
use PHPUnit\Framework\TestCase;

class NoteLinksTest extends TestCase
{
    public function test_titled_links_become_anchors(): void
    {
        $html = NoteLinks::toHtml('See the [Manual](https://example.com/docs/manual.pdf) first')->toHtml();

        $this->assertStringContainsString('href="https://example.com/docs/manual.pdf"', $html);
        $this->assertStringContainsString('>Manual</a>', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
        $this->assertStringStartsWith('See the ', $html);
        $this->assertStringEndsWith(' first', $html);
    }

    public function test_bare_urls_become_anchors_with_a_compact_label(): void
    {
        $html = NoteLinks::toHtml('Manual: https://www.example.com/support/product/12345/manual-download-page')->toHtml();

        $this->assertStringContainsString('href="https://www.example.com/support/product/12345/manual-download-page"', $html);
        $this->assertStringContainsString('>example.com/support/product/1234…</a>', $html);
    }

    public function test_trailing_punctuation_stays_out_of_bare_urls(): void
    {
        $html = NoteLinks::toHtml('Docs at https://example.com/help.')->toHtml();

        $this->assertStringContainsString('href="https://example.com/help"', $html);
        $this->assertStringEndsWith('</a>.', $html);
    }

    public function test_everything_else_is_escaped(): void
    {
        $html = NoteLinks::toHtml('<b>bold</b> & [x](https://a.io/?q=1&p=2)')->toHtml();

        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt; &amp; ', $html);
        $this->assertStringContainsString('href="https://a.io/?q=1&amp;p=2"', $html);
        $this->assertStringNotContainsString('<b>', $html);
    }

    public function test_notes_without_links_pass_through_untouched(): void
    {
        $this->assertSame('Spare fuses in the lid', NoteLinks::toHtml('Spare fuses in the lid')->toHtml());
        $this->assertSame('Spare fuses in the lid', NoteLinks::toPlain('Spare fuses in the lid'));
        $this->assertSame('', NoteLinks::toHtml(null)->toHtml());
    }

    public function test_plain_collapses_links_to_their_labels(): void
    {
        $this->assertSame(
            'See Manual and example.com/a for details',
            NoteLinks::toPlain('See [Manual](https://example.com/docs) and https://example.com/a for details'),
        );
    }

    public function test_only_http_schemes_are_linked(): void
    {
        $this->assertSame(
            '[bad](javascript:alert(1)) ftp://example.com',
            NoteLinks::toHtml('[bad](javascript:alert(1)) ftp://example.com')->toHtml(),
        );
    }
}
