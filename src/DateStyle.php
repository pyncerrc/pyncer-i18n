<?php
namespace Pyncer\I18n;

enum DateStyle: int {
    case FULL = 0; // IntlDateFormatter::FULL
    case LONG = 1; // IntlDateFormatter::LONG
    case MEDIUM = 2; // IntlDateFormatter::MEDIUM
    case SHORT = 3; // IntlDateFormatter::SHORT
}
