<?php

namespace Tests\Unit;

use App\Support\PhotoShrinker;
use PHPUnit\Framework\TestCase;

class PhotoShrinkerTest extends TestCase
{
    public function test_oversized_photos_are_scaled_down_to_the_cap(): void
    {
        $shrunk = (new PhotoShrinker)->shrink($this->jpeg(3200, 2400));

        [$width, $height, $type] = getimagesizefromstring($shrunk);

        $this->assertSame(1600, $width);
        $this->assertSame(1200, $height);
        $this->assertSame(IMAGETYPE_JPEG, $type);
    }

    public function test_portrait_photos_cap_the_long_edge(): void
    {
        $shrunk = (new PhotoShrinker)->shrink($this->jpeg(1200, 3200));

        [$width, $height] = getimagesizefromstring($shrunk);

        $this->assertSame(600, $width);
        $this->assertSame(1600, $height);
    }

    public function test_small_photos_pass_through_untouched(): void
    {
        $bytes = $this->jpeg(800, 600);

        $this->assertSame($bytes, (new PhotoShrinker)->shrink($bytes));
    }

    public function test_non_image_bytes_pass_through_untouched(): void
    {
        $this->assertSame('not an image', (new PhotoShrinker)->shrink('not an image'));
    }

    private function jpeg(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width - 1, $height - 1, (int) imagecolorallocate($image, 40, 90, 200));
        ob_start();
        imagejpeg($image, null, 90);
        imagedestroy($image);

        return (string) ob_get_clean();
    }
}
