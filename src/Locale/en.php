<?php
namespace Pyncer\I18n\Locale;

use Pyncer\I18n\AbstractLocale;
use Pyncer\I18n\I18n;
use Pyncer\I18n\ListStyle;

class en extends AbstractLocale
{
    public function __construct(
        I18n $i18n,
        string $code = 'en',
        string $name = 'English',
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
            ListStyle::AND => 'and',
            ListStyle::OR => 'or',
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
                $list .= ', ' . $styleValue . ' ' . $value;
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

        $value = abs($value);

        if ($value === 1) {
            return 'one';
        }

        return 'other';
    }

    public function getRangeRule(int|float $startValue, int|float $endValue)
    {
        return 'other';
    }

    public function transform(string $value, string $method): string
    {
        switch ($method) {
            case 'possesive':
                if (strtolower(substr($value, -1)) === 's') {
                    return $value . '\'';
                }

                return $value . '\'s';
            default:
                return parent::transform($value, $method);
        }
    }
}
