<?php

namespace App\Console\Commands;

use App\Support\Brand;
use Illuminate\Console\Command;
use Imagick;
use ImagickDraw;
use ImagickPixel;

/**
 * Écrit les icônes du site dans public/ à partir de App\Support\Brand (source unique) :
 * favicon.svg, favicon.ico (32 px) et apple-touch-icon.png (180 px).
 * Outil de développement (Imagick). Les PNG utilisent une police serif du système (le SVG, Newsreader).
 */
class BrandExport extends Command
{
    protected $signature = 'brand:export {--font=/System/Library/Fonts/Supplemental/Georgia Bold.ttf : Police des icônes PNG}';

    protected $description = 'Génère les favicons depuis App\Support\Brand';

    public function handle(): int
    {
        file_put_contents(public_path('favicon.svg'), Brand::faviconSvg()."\n");
        $this->line('public/favicon.svg');

        foreach (['favicon.ico' => [32, 'ico'], 'apple-touch-icon.png' => [180, 'png']] as $file => [$size, $format]) {
            file_put_contents(public_path($file), $this->raster($size, $format));
            $this->line("public/$file ($size px)");
        }

        $this->info('Icônes générées.');

        return self::SUCCESS;
    }

    /** « DK » en blanc sur un aplat primary-600 aux coins arrondis, dessiné en 4× puis réduit. */
    private function raster(int $size, string $format): string
    {
        $scale = 4;
        $box = $size * $scale;

        $image = new Imagick;
        $image->newImage($box, $box, new ImagickPixel('transparent'));

        $shape = new ImagickDraw;
        $shape->setFillColor(new ImagickPixel(Brand::PRIMARY_600));
        $shape->roundRectangle(0, 0, $box - 1, $box - 1, $box * 14 / 64, $box * 14 / 64);
        $image->drawImage($shape);

        $text = new ImagickDraw;
        $text->setFont($this->option('font'));
        $text->setFontSize($box * 30 / 64);
        $text->setFillColor(new ImagickPixel('#FFFFFF'));
        $text->setGravity(Imagick::GRAVITY_CENTER);
        $image->annotateImage($text, 0, $box * 1 / 64, 0, Brand::MONOGRAM);

        $image->resizeImage($size, $size, Imagick::FILTER_LANCZOS, 1);
        $image->setImageFormat($format);

        return $image->getImageBlob();
    }
}
