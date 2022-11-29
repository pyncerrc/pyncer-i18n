<?php
namespace Pyncer\I18n\Locale;

use Pyncer\I18n\I18n;
use Pyncer\I18n\Locale\en;

class en_US extends en
{
    public function __construct(I18n $i18n) {
        parent::__construct($i18n);
        $this->code = 'en-US';
        $this->name = 'English United States'
    }
}
