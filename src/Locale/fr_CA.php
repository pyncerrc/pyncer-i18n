<?php
namespace Pyncer\I18n\Locale;

use Pyncer\I18n\I18n;
use Pyncer\I18n\Locale\fr;

class fr_CA extends fr
{
    public function __construct(
        I18n $i18n,
        string $code = 'fr-CA',
        string $name = 'Français Canada',
        ?string $shortCode = 'fr',
        ?string $shortName = 'Français',
    ) {
        parent::__construct($i18n, $code, $name, $shortCode, $shortName);
    }
}
