<?php
namespace Pyncer\I18n\Locale;

use Pyncer\I18n\AbstractLocale;
use Pyncer\I18n\I18n;
use Pyncer\I18n\ListStyle;
use Pyncer\I18n\Rule;

class ja extends AbstractLocale
{
    public function __construct(
        I18n $i18n,
        string $code = 'ja',
        string $name = '日本語',
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
            ListStyle::AND => 'と',
            ListStyle::OR => 'または',
        };

        if ($count === 2) {
            return $items[0] . $styleValue . $items[1];
        }

        $list = '';

        --$count;

        foreach ($items as $key => $value) {
            if ($key === 0) {
                $list .= $value;
            } elseif ($key === $count) {
                if ($style === ListStyle::OR) {
                    $list .= '、' . $styleValue . $value;
                } else {
                    $list .= '、' . $value;
                }
            } else {
                $list .= '、' . $value;
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

        return Rule::OTHER;
    }

    public function getRangeRule(
        int|float $startValue,
        int|float $endValue
    ): Rule
    {
        return Rule::OTHER;
    }

    protected static $sizeMessages = [
        'byte' => [
            'other' => '{0} バイト',
        ],

        'kilobyte' => [
            'other' => '{0} キロバイト',
        ],
        'megabyte' => [
            'other' => '{0} メガバイト',
        ],
        'gigabyte' => [
            'other' => '{0} ギガバイト',
        ],
        'terabyte' => [
            'other' => '{0} テラバイト',
        ],
        'petabyte' => [
            'other' => '{0} ペタバイト',
        ],
        'exabyte' => [
            'other' => '{0} エクサバイト',
        ],
        'zettabyte' => [
            'other' => '{0} ゼタバイト',
        ],
        'yottabyte' => [
            'other' => '{0} ヨタバイト',
        ],

        'kibibyte' => [
            'other' => '{0} キビバイト',
        ],
        'mebibyte' => [
            'other' => '{0} メビバイト',
        ],
        'gibibyte' => [
            'other' => '{0} ギビバイト',
        ],
        'tebibyte' => [
            'other' => '{0} テビバイト',
        ],
        'pebibyte' => [
            'other' => '{0} ペビバイト',
        ],
        'exbibyte' => [
            'other' => '{0} エクスビバイト',
        ],
        'zebibyte' => [
            'other' => '{0} ゼビバイト',
        ],
        'yobibyte' => [
            'other' => '{0} ヨビバイト',
        ],
    ];
}
