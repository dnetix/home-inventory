<?php

namespace App\Support;

use GdImage;

/**
 * Server-side safety net for photo size: photos are normally downscaled in the
 * browser before upload, but anything that arrives oversized (old browsers,
 * decode failures) is re-encoded here. Non-image or already-small bytes pass
 * through untouched.
 */
class PhotoShrinker
{
    public function __construct(
        private readonly int $maxEdge = 1600,
        private readonly int $quality = 82,
    ) {}

    /**
     * The variant used for list/grid thumbnails: tiles render at ≤160 CSS px,
     * so 320px covers retina at a fraction of the full photo's weight.
     */
    public static function thumbnail(): self
    {
        return new self(maxEdge: 320, quality: 75);
    }

    /**
     * JPEG bytes capped at maxEdge on the longest side, or the input unchanged
     * when it is not a decodable oversized image.
     */
    public function shrink(string $bytes): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $bytes;
        }

        set_error_handler(fn (): bool => true);

        try {
            $image = imagecreatefromstring($bytes);
        } finally {
            restore_error_handler();
        }

        if ($image === false) {
            return $bytes;
        }

        $image = $this->applyOrientation($image, $this->orientation($bytes));

        if (max(imagesx($image), imagesy($image)) <= $this->maxEdge) {
            imagedestroy($image);

            return $bytes;
        }

        $scale = $this->maxEdge / max(imagesx($image), imagesy($image));
        $scaled = imagescale(
            $image,
            (int) round(imagesx($image) * $scale),
            (int) round(imagesy($image) * $scale),
        );

        if ($scaled !== false) {
            imagedestroy($image);
            $image = $scaled;
        }

        ob_start();
        imagejpeg($image, null, $this->quality);
        imagedestroy($image);
        $jpeg = ob_get_clean();

        return $jpeg === false || $jpeg === '' ? $bytes : $jpeg;
    }

    /**
     * EXIF orientation tag (1 when absent or unreadable). Only relevant when
     * re-encoding: untouched originals keep their EXIF and browsers orient them.
     */
    private function orientation(string $bytes): int
    {
        if (! function_exists('exif_read_data')) {
            return 1;
        }

        $stream = fopen('php://memory', 'r+b');
        fwrite($stream, $bytes);
        rewind($stream);
        $exif = @exif_read_data($stream);
        fclose($stream);

        return is_array($exif) ? (int) ($exif['Orientation'] ?? 1) : 1;
    }

    private function applyOrientation(GdImage $image, int $orientation): GdImage
    {
        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => false,
        };

        if ($rotated === false) {
            return $image;
        }

        imagedestroy($image);

        return $rotated;
    }
}
