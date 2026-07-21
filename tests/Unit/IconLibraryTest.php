<?php

namespace Tests\Unit;

use App\Support\IconLibrary;
use Tests\TestCase;

class IconLibraryTest extends TestCase
{
    public function test_knows_lucide_icons(): void
    {
        $this->assertTrue(IconLibrary::has('hammer'));
        $this->assertFalse(IconLibrary::has('definitely-not-an-icon'));
    }

    public function test_returns_inner_svg_markup(): void
    {
        $this->assertStringContainsString('<path', IconLibrary::inner('hammer'));
        $this->assertNull(IconLibrary::inner('definitely-not-an-icon'));
    }

    public function test_searches_by_name_with_prefix_matches_first(): void
    {
        $results = IconLibrary::search('hammer');

        $this->assertSame('hammer', $results[0]);
    }

    public function test_searches_by_keyword_tags(): void
    {
        $this->assertContains('hammer', IconLibrary::search('mallet'));
    }

    public function test_blank_search_returns_nothing(): void
    {
        $this->assertSame([], IconLibrary::search('  '));
    }
}
