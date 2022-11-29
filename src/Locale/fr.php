<?php
namespace Pyncer\I18n\Locale;

use Pyncer\I18n\AbstractLocale;
use Pyncer\I18n\I18n;
use Pyncer\I18n\ListStyle;

class fr extends AbstractLocale
{

    public function __construct(
        I18n $i18n,
        string $code = 'fr',
        string $name = 'Français',
        ?string $codeShort = null,
        ?string $nameShort = null,
    ) {
        parent::__construct($i18n, $code, $name, $codeShort, $nameShort);
    }

    public function formatList(
        array $items,
        ListStyle $style = ListStyle::AND
    ): string
    {
        $count = count($items);

        if ($count === 1) {
            return $items[0];
        }

        $styleValue = match ($style) {
            ListStyle::AND => 'et',
            ListStyle::OR => 'ou',
        };

        if ($count === 2) {
            return $items[0] . ' ' . $styleValue . ' ' . $items[1];
        }

        $list = '';

        --$count;

        foreach ($items as $key => $value) {
            if ($key === 0) {
                $list .= $value;
            } elseif ($key === $count) {
                $list .= ' ' . $styleValue . ' ' . $value;
            } else {
                $list .= ', ' . $value;
            }
        }

        return $list;
    }

    public function getCardinalRule(int|float $value, bool $none = false)
    {
        if ($none && ($value === 0 || $value === 0.0)) {
            return 'none';
        }

        $value = abs(intval($value));

        if ($value === 0 || $value === 1) {
            return 'one';
        }

        return 'other';
    }

    public function getRangeRule(int|float $startValue, int|float $endValue)
    {
        return $this->getNumberRule($endValue);
    }

    protected static $sizeMessages = [
        'byte' => [
            'one' => '{0} octet',
            'other' => '{0} octets',
        ],

        'kilobyte' => [
            'one' => '{0} kilooctet',
            'other' => '{0} kilooctets',
        ],
        'megabyte' => [
            'one' => '{0} mégaoctet',
            'other' => '{0} mégaoctets',
        ],
        'gigabyte' => [
            'one' => '{0} gigaboctet',
            'other' => '{0} gigaoctets',
        ],
        'terabyte' => [
            'one' => '{0} téraoctet',
            'other' => '{0} téraoctets',
        ],
        'petabyte' => [
            'one' => '{0} pétaoctet',
            'other' => '{0} pétaoctets',
        ],
        'exabyte' => [
            'one' => '{0} exaoctet',
            'other' => '{0} exaoctets',
        ],
        'zettabyte' => [
            'one' => '{0} zettaoctet',
            'other' => '{0} zettaoctets',
        ],
        'yottabyte' => [
            'one' => '{0} yottaoctet',
            'other' => '{0} yottaoctets',
        ],

        'kibibyte' => [
            'one' => '{0} kibioctet',
            'other' => '{0} kibioctets',
        ],
        'mebibyte' => [
            'one' => '{0} mébioctet',
            'other' => '{0} mébioctets',
        ],
        'gibibyte' => [
            'one' => '{0} gibioctet',
            'other' => '{0} gibioctets',
        ],
        'tebibyte' => [
            'one' => '{0} tébioctet',
            'other' => '{0} tébioctets',
        ],
        'pebibyte' => [
            'one' => '{0} pébioctet',
            'other' => '{0} pébioctets',
        ],
        'exbibyte' => [
            'one' => '{0} exbioctet',
            'other' => '{0} exbioctets',
        ],
        'zebibyte' => [
            'one' => '{0} zébioctet',
            'other' => '{0} zébioctets',
        ],
        'yobibyte' => [
            'one' => '{0} yobioctet',
            'other' => '{0} yobioctets',
        ],
    ];

    protected static $sizeMessagesShort = [
        'byte' => [
            'other' => '{0} o',
        ],

        'kilobyte' => [
            'other' => '{0} ko',
        ],
        'megabyte' => [
            'other' => '{0} Mo',
        ],
        'gigabyte' => [
            'other' => '{0} Go',
        ],
        'terabyte' => [
            'other' => '{0} To',
        ],
        'petabyte' => [
            'other' => '{0} Po',
        ],
        'exabyte' => [
            'other' => '{0} Eo',
        ],
        'zettabyte' => [
            'other' => '{0} Zo',
        ],
        'yottabyte' => [
            'other' => '{0} Yo',
        ],

        'kibibyte' => [
            'other' => '{0} Kio',
        ],
        'mebibyte' => [
            'other' => '{0} Mio',
        ],
        'gibibyte' => [
            'other' => '{0} Gio',
        ],
        'tebibyte' => [
            'other' => '{0} Tio',
        ],
        'pebibyte' => [
            'other' => '{0} Pio',
        ],
        'exbibyte' => [
            'other' => '{0} Eio',
        ],
        'zebibyte' => [
            'other' => '{0} Zio',
        ],
        'yobibyte' => [
            'other' => '{0} Yio',
        ],
    ];
}
