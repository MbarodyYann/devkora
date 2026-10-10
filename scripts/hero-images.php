<?php

/*
| Génère les images WebP du hero depuis les sources PNG de documentation/assets/ (hors dépôt).
| Usage : php scripts/hero-images.php   (outil de développement, extension PHP Imagick)
|
| Portrait 4:5 pour l'ordinateur, paysage 16:9 pour le mobile, deux tailles chacun (08, « Hero »).
| La qualité descend de 80 à 60 jusqu'à tenir le poids maximal.
*/

$root = dirname(__DIR__);
$variants = [
    // [source, sortie, largeur, hauteur, poids maximal en octets]
    ['hero-portrait-source.png', 'portrait-480.webp', 480, 600, 40_000],
    ['hero-portrait-source.png', 'portrait-800.webp', 800, 1000, 80_000],
    ['hero-paysage-source.png', 'paysage-480.webp', 480, 270, 40_000],
    ['hero-paysage-source.png', 'paysage-800.webp', 800, 450, 80_000],
];

@mkdir("$root/public/images/hero", 0755, true);

foreach ($variants as [$source, $output, $width, $height, $maxBytes]) {
    foreach ([80, 76, 72, 68, 64, 60] as $quality) {
        $image = new Imagick("$root/documentation/assets/$source");
        $image->cropThumbnailImage($width, $height); // recadrage centré au ratio exact
        $image->stripImage();
        $image->setImageFormat('webp');
        $image->setImageCompressionQuality($quality);
        $blob = $image->getImageBlob();

        if (strlen($blob) <= $maxBytes) {
            break;
        }
    }

    file_put_contents("$root/public/images/hero/$output", $blob);
    printf("public/images/hero/%-18s %4d × %-4d qualité %d  %6d octets (max %d)\n", $output, $width, $height, $quality, strlen($blob), $maxBytes);
}
