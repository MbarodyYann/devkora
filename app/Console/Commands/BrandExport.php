<?php

namespace App\Console\Commands;

use App\Support\Brand;
use Illuminate\Console\Command;
use Imagick;
use ImagickDraw;
use ImagickPixel;

/**
 * Écrit les icônes du site dans public/ à partir de App\Support\Brand (source unique) :
 * favicon.svg, favicon.ico (32 px), apple-touch-icon.png (180 px) et l'image de partage og-default.png (1200 × 630).
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

        file_put_contents(public_path('images/og-default.png'), $this->ogImage());
        $this->line('public/images/og-default.png (1200 × 630)');

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

    /**
     * Image de partage par défaut (Open Graph) : aplat primary-800, arcs du bandeau, mot-symbole
     * « DevKora » (« Kora » en ambre) et l'accroche. Aucun dégradé.
     */
    private function ogImage(): string
    {
        $image = new Imagick;
        $image->newImage(1200, 630, new ImagickPixel('#1F2F55'));

        $arcs = new ImagickDraw;
        $arcs->setFillOpacity(0);
        $arcs->setStrokeColor(new ImagickPixel('rgba(255,255,255,0.08)'));
        $arcs->setStrokeWidth(2);
        foreach ([300, 460, 620] as $radius) {
            $arcs->circle(0, 630, $radius, 630);
        }
        $image->drawImage($arcs);

        $font = $this->option('font');
        $word = new ImagickDraw;
        $word->setFont($font);
        $word->setFontSize(120);
        $word->setFillColor(new ImagickPixel('#FFFFFF'));
        $image->annotateImage($word, 96, 300, 0, Brand::WORD_START);
        $width = $image->queryFontMetrics($word, Brand::WORD_START)['textWidth'];
        $word->setFillColor(new ImagickPixel(Brand::SECONDARY_400));
        $image->annotateImage($word, 96 + $width, 300, 0, Brand::WORD_END);

        $line = new ImagickDraw;
        $line->setFont(str_replace(' Bold', '', $font));
        $line->setFontSize(44);
        $line->setFillColor(new ImagickPixel('#FFFFFF'));
        $image->annotateImage($line, 96, 400, 0, 'Le point de rencontre des devs africains.');
        $line->setFontSize(30);
        $line->setFillColor(new ImagickPixel('rgba(255,255,255,0.8)'));
        $image->annotateImage($line, 96, 460, 0, 'Trouve des missions. Partage tes connaissances. Construis avec les tiens.');

        $image->setImageFormat('png');

        return $image->getImageBlob();
    }
}
