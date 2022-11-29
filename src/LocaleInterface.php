<?php
namespace Pyncer\I18n;

use DateTimeInterface;
use Pyncer\I18n\DateStyle;
use Pyncer\I18n\ListStyle;
use Pyncer\I18n\TimeStyle;
use Pyncer\I18n\Rule;
use Pyncer\Unit\LengthUnit;
use Pyncer\Unit\MassUnit;
use Pyncer\Unit\SizeUnit;

interface LocaleInterface
{
    public function getCode(): string;
    public function getCodeShort(): string;
    public function getName(): string;
    public function getNameShort(): string;

    public function has(
        string $key,
        ?iterable $sourceNames = null,
    ): bool;
    public function get(
        string $key,
        Rule $rule = Rule::OTHER,
        iterable $args = [],
        ?iterable $sourceNames = null,
    ): ?string;

    public function pluralize(string|array $values, Rule $rule): string;
    public function transform(string $value, string $method): string;

    //https://www.unicode.org/cldr/cldr-aux/charts/33/supplemental/language_plural_rules.html
    public function getCardinalRule(int|float $value, bool $none = false);
    public function getRangeRule(int|float $startValue, int|float $endValue);

    public function formatList(
        array $items,
        ListStyle $style = ListStyle::AND
    ): string;

    public function formatDate(
        string|DateTimeInterface $value,
        DateStyle $dateStyle = DateStyle::SHORT
    ): string;
    public function formatTime(
        string|DateTimeInterface $value,
        TimeStyle $timeStyle = TimeStyle::SHORT
    ): string;
    public function formatDateTime(
        string|DateTimeInterface $value,
        DateStyle $dateStyle = DateStyle::SHORT,
        TimeStyle $TimeStyle = TimeStyle::SHORT,
        ?string $patternSkeleton = null
    ): string;

    public function formatInteger(int $value): string;
    public function formatDecimal(int|float $value, ?int $decimals = null): string;
    public function formatPercent(int|float $value, ?int $decimals = null): string;
    public function formatOrdinal(int $value): string;
    public function formatSpellout(int|float $value, ?int $decimals = null): string;
    public function formatDuration(int $value): string;

    public function formatCurrency(
        int|float $value,
        ?string $currencyCode = null,
        bool $trimDecimals = false,
        bool $negativeZero = false,
        bool $accounting = false,
    ): string;

    public function formatLength(
        int|float $value,
        int $decimals,
        LengthUnit $unit,
        bool $short = false,
    ): string;

    public function formatMass(
        int|float $value,
        int $decimals,
        MassUnit $unit,
        bool $short = false,
    ): string;

    public function formatSize(
        int $value,
        SizeUnit $unit,
        bool $short = false,
    ): string;
}
