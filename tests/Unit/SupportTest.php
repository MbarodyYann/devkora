<?php

use App\Enums\Availability;
use App\Enums\Level;
use App\Models\Skill;
use App\Support\Brand;
use App\Support\Copy;
use App\Support\Countries;
use App\Support\French;

test('les 54 pays du continent, francophones en premier', function () {
    $codes = Countries::codes();

    expect($codes)->toHaveCount(54)
        ->and(array_unique($codes))->toHaveCount(54)
        ->and(array_slice($codes, 0, 26))->toContain('SN', 'CI', 'TG', 'CD', 'MA')
        ->and($codes)->toContain('NG', 'GH', 'KE', 'ZA', 'EG', 'ET')
        ->not->toContain('FR', 'US', 'BE')
        ->and(Countries::name('CI'))->toBe('Côte d\'Ivoire')
        ->and(Countries::flag('SN'))->toBe('🇸🇳')
        ->and(Countries::flag('sn'))->toBe('')
        ->and(Countries::flag(null))->toBe('');
});

test('les langues ont un code et un libellé en français', function () {
    expect(config('languages'))->toHaveKeys(['fr', 'en', 'wo', 'bm', 'ln', 'fon', 'ee', 'mos', 'dyu', 'ff', 'ar', 'sw'])
        ->and(config('languages.ff'))->toBe('peul');
});

test('les niveaux suivent les cauris', function (int $reputation, Level $level) {
    expect(Level::for($reputation))->toBe($level);
})->with([
    [0, Level::Newcomer],
    [49, Level::Newcomer],
    [50, Level::Contributor],
    [250, Level::Confirmed],
    [999, Level::Confirmed],
    [1000, Level::Referent],
    [-10, Level::Newcomer],
]);

test('les disponibilités ont les libellés de la charte', function () {
    expect(Availability::options())->toBe([
        'collab' => 'Ouvert aux collaborations',
        'missions' => 'Ouvert aux missions',
        'unavailable' => 'Indisponible',
    ])->and(Availability::Unavailable->tone())->toBe('muted');
});

test('élision : d\'Aïssatou, de Moussa, de Yaya', function () {
    expect(French::de('Aïssatou'))->toBe("d'Aïssatou")
        ->and(French::de('Edem'))->toBe("d'Edem")
        ->and(French::de('Moussa'))->toBe('de Moussa')
        ->and(French::de('Yaya'))->toBe('de Yaya')
        ->and(French::de('Hervé Koffi'))->toBe("d'Hervé Koffi");
});

test('le slug d\'une compétence garde ses symboles', function () {
    expect(Skill::slugFor('C#'))->toBe('c-sharp')
        ->and(Skill::slugFor('C++'))->toBe('c-plus-plus')
        ->and(Skill::slugFor('Orange Money'))->toBe('orange-money');
});

test('textes uniques : CTA et réassurance', function () {
    expect(Copy::SIGNUP_CTA)->toBe('Crée ton profil gratuit')
        ->and(Copy::SIGNUP_REASSURANCE)->toBe('Gratuit, sans carte bancaire · Ton profil en ligne en 2 minutes');
});

test('favicon provisoire : « DK » sur un aplat primary-600, sans dégradé', function () {
    $svg = Brand::faviconSvg();

    expect($svg)->toContain('fill="#314A82"')->toContain('>DK</text>')->not->toContain('Gradient')
        ->and(trim(file_get_contents(public_path('favicon.svg'))))->toBe($svg)
        ->and(file_exists(public_path('favicon.ico')))->toBeTrue()
        ->and(file_exists(public_path('apple-touch-icon.png')))->toBeTrue();
});
