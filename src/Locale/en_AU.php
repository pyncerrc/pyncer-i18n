<?php
namespace Pyncer\I18n\Locale;

use Pyncer\I18n\I18n;
use Pyncer\I18n\Locale\en;

class en_AU extends en
{
    public function __construct(
        I18n $i18n,
        string $code = 'en-AU',
        string $name = 'English Australia',
        ?string $codeShort = 'en',
        ?string $nameShort = 'English',
    ) {
        parent::__construct($i18n, $code, $name, $codeShort, $nameShort);
    }
}
