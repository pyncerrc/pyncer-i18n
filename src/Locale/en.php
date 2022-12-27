<?php
namespace Pyncer\I18n\Locale;

use Pyncer\I18n\AbstractLocale;
use Pyncer\I18n\I18n;
use Pyncer\I18n\ListStyle;
use Pyncer\I18n\Rule;

class en extends AbstractLocale
{
    public function __construct(
        I18n $i18n,
        string $code = 'en',
        string $name = 'English',
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

    public function getCardinalRule(
        int|float $value,
        bool $none = false
    ): Rule
    {
        if ($none && ($value === 0 || $value === 0.0)) {
            return Rule::NONE;
        }

        $value = abs($value);

        if ($value === 1) {
            return Rule::ONE;
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
