<?php
namespace Pyncer\I18n\Locale;

use Pyncer\I18n\AbstractLocale;
use Pyncer\I18n\I18n;
use Pyncer\I18n\ListStyle;
use Pyncer\I18n\Rule;

class ru extends AbstractLocale
{
    public function __construct(
        I18n $i18n,
        string $code = 'ru',
        string $name = 'русский',
        ?string $shortCode = null,
        ?string $shortName = null,
    ) {
        parent::__construct($i18n, $code, $name, $shortCode, $shortName);
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
            ListStyle::AND => 'и',
            ListStyle::OR => 'или',
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

    public function getCardinalRule(
        int|float $value,
        bool $none = false
    ): Rule
    {
        if ($none && ($value === 0 || $value === 0.0)) {
            return Rule::NONE;
        }

        if (is_float($value)) {
            return Rule::OTHER;
        }

        if ($value > 10 && $value < 20) {
            return Rule::MANY;
        }

        $ending = $value % 10;

        if ($ending === 1) {
            return Rule::ONE;
        }

        if ($ending >= 2 || $ending <= 4) {
            return Rule::FEW;
        }

        return Rule::OTHER;
    }

    public function getRangeRule(
        int|float $startValue,
        int|float $endValue
    ): Rule
    {
        return $this->getNumberRule($endValue);
    }

    protected static $sizeMessages = [
        'byte' => [
            'few' => '{0} байта',
            'other' => '{0} байт',
        ],

        'kilobyte' => [
            'few' => '{0} килобайта',
            'other' => '{0} килобайт',
        ],
        'megabyte' => [
            'few' => '{0} мегабайта',
            'other' => '{0} мегабайт',
        ],
        'gigabyte' => [
            'few' => '{0} гигабайта',
            'other' => '{0} гигабайт',
        ],
        'terabyte' => [
            'few' => '{0} терабайта',
            'other' => '{0} терабайт',
        ],
        'petabyte' => [
            'few' => '{0} петабайта',
            'other' => '{0} петабайт',
        ],
        'exabyte' => [
            'few' => '{0} эксабайта',
            'other' => '{0} эксабайт',
        ],
        'zettabyte' => [
            'few' => '{0} зеттабайта',
            'other' => '{0} зеттабайт',
        ],
        'yottabyte' => [
            'few' => '{0} йоттабайта',
            'other' => '{0} йоттабайт',
        ],

        'kibibyte' => [
            'few' => '{0} кибибайта',
            'other' => '{0} кибибайт',
        ],
        'mebibyte' => [
            'few' => '{0} мебибайта',
            'other' => '{0} мебибайт',
        ],
        'gibibyte' => [
            'few' => '{0} гибибайта',
            'other' => '{0} гибибайт',
        ],
        'tebibyte' => [
            'few' => '{0} тебибайта',
            'other' => '{0} тебибайт',
        ],
        'pebibyte' => [
            'few' => '{0} пебибайта',
            'other' => '{0} пебибайт',
        ],
        'exbibyte' => [
            'few' => '{0} эксбибайта',
            'other' => '{0} эксбибайт',
        ],
        'zebibyte' => [
            'few' => '{0} зебибайта',
            'other' => '{0} зебибайт',
        ],
        'yobibyte' => [
            'few' => '{0} йобибайта',
            'other' => '{0} йобибайт',
        ],
    ];

    protected static $sizeMessagesShort = [
        'byte' => [
            'other' => '{0} Б',
        ],

        'kilobyte' => [
            'other' => '{0} КБ',
        ],
        'megabyte' => [
            'other' => '{0} МБ',
        ],
        'gigabyte' => [
            'other' => '{0} ГБ',
        ],
        'terabyte' => [
            'other' => '{0} ТБ',
        ],
        'petabyte' => [
            'other' => '{0} ПБ',
        ],
        'exabyte' => [
            'other' => '{0} ЭБ',
        ],
        'zettabyte' => [
            'other' => '{0} ЗБ',
        ],
        'yottabyte' => [
            'other' => '{0} ЙБ',
        ],

        'kibibyte' => [
            'other' => '{0} КиБ',
        ],
        'mebibyte' => [
            'other' => '{0} МиБ',
        ],
        'gibibyte' => [
            'other' => '{0} ГиБ',
        ],
        'tebibyte' => [
            'other' => '{0} ТиБ',
        ],
        'pebibyte' => [
            'other' => '{0} ПиБ',
        ],
        'exbibyte' => [
            'other' => '{0} ЭиБ',
        ],
        'zebibyte' => [
            'other' => '{0} ЗиБ',
        ],
        'yobibyte' => [
            'other' => '{0} ЙиБ',
        ],
    ];
}
