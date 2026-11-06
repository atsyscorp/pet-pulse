<?php

declare(strict_types=1);

namespace App\Services\OwnerApp;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/** Inline SVG QR for a link code; the app's scanner reads "PETPULSE:<code>". */
final class LinkCodeQr
{
    public static function svg(string $code, int $size = 220): string
    {
        $writer = new Writer(new ImageRenderer(new RendererStyle($size, 1), new SvgImageBackEnd));

        $svg = $writer->writeString(LinkCode::QR_PREFIX.$code);

        // Drop the XML prolog so the SVG can be inlined in HTML.
        return trim(preg_replace('/^<\?xml[^>]*\?>/', '', $svg) ?? $svg);
    }
}
