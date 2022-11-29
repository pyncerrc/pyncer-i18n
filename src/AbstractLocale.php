<?php
namespace Pyncer\I18n;

use DateTimeInterface;
use IntlDateFormatter;
use IntlDatePatternGenerator;
use NumberFormatter;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\I18n\I18n;
use Pyncer\I18n\DateStyle;
use Pyncer\I18n\ListStyle;
use Pyncer\I18n\TimeStyle;
use Pyncer\Unit\LengthUnit;
use Pyncer\Unit\MassUnit;
use Pyncer\Unit\SizeUnit;
use Pyncer\Unit\UnitInterface;
use ResourceBundle;

use function array_key_exists;
use function count;
use function explode;
use function floatval;
use function intval;
use function is_string;
use function Pycner\Array\ensure_array as pyncer_ensure_array;
use function Pyncer\date_time as pyncer_date_time;
use function rsort;
use function str_replace;

use const Pyncer\DATE_FORMAT as PYNCER_DATE_FORMAT;
use const Pyncer\TIME_FORMAT as PYNCER_TIME_FORMAT;
use const Pyncer\DATE_TIME_FORMAT as PYNCER_DATE_TIME_FORMAT;

abstract class AbstractLocale implements LocaleInterface
{
    private ?ResourceBundle $resourceBundle = null;
    private ?ResourceBundle $resourceBundleShort = null;
    private array $messages = [];

    public function __construct(
        protected I18n $i18n,
        protected string $code,
        protected string $name,
        protected ?string $codeShort = null,
        protected ?string $nameShort = null,
    )
    {}

    public function getCode(): string
    {
        return $this->code;
    }
    public function getCodeShort(): string
    {
        return $this->codeShort ?? $this->code;
    }
    public function getName(): string
    {
        return $this->name();
    }
    public function getNameShort(): string
    {
        return $this->nameShort ?? $this->name;
    }

    public function has(
        string $key,
        ?iterable $sourceNames = null,
    ): bool
    {
        return $this->i18n->has(
            key: $key,
            localeCodes: $this->getLocaleCodes(),
            sourceNames: $sourceNames
        );
    }
    public function get(
        string $key,
        Rule $rule = Rule::OTHER,
        iterable $args = [],
        ?iterable $sourceNames = null,
    ): ?string
    {
        return $this->i18n->get(
            key: $key,
            args: $args,
            rule: $rule,
            localeCodes: $this->getLocaleCodes(),
            sourceNames: $sourceNames
        );
    }
    private function getLocaleCodes()
    {
        if ($this->getCode() === $this->getCodeShort()) {
            return [$this->getCode()];
        }

        return [$this->getCode(), $this->getCodeShort()];
    }

    public function pluralize(string|array $options, Rule $rule): string
    {
        if (is_string($options)) {
            return $options;
        }

        $name = $rule->getName();
        if (array_key_exists($name, $options)) {
            return $options[$name];
        } elseif (array_key_exists('other', $options)) {
            return $options['other'];
        }

        throw new UnexpectedValueException(
            'Rule does not coincide with any options.'
        );
    }

    public function transform(string $value, string $method): string
    {
        $method = explode(':', $method);

        return match ($method[0]) {
            'integer' => $this->formatInteger(intval($value)),
            'decimal' => $this->formatDecimal(floatval($value), $method[1] ?? 2),
            'percent' => $this->formatPercent(floatval($value), $method[1] ?? 0),
            'ordinal' => $this->formatOrdinal(intval($value)),
            'spellout' => $this->formatSpellout(floatval($value)),
            'duration' => $this->formatDuration(intval($value)),
            default => $value,
        };
    }

    public function formatList(
        array $items,
        ListStyle $style = ListStyle::AND
    ): string
    {
        $itemCount = count($itmes);

        if ($itemCount === 1) {
            return $items[0];
        }

        if ($itemCount === 2) {
            if ($count === 1) {
                return $items[0];
            }

            return $items[1];
        }

        if ($count === 0) {
            return $items[0];
        }

        if ($count === 1) {
            return $items[1];
        }

        return $items[2];
    }

    public function formatDate(
        string|DateTimeInterface $value,
        DateStyle $dateStyle = DateStyle::SHORT
    ): string
    {
        if (is_string($value)) {
            $value = pyncer_date_time($value);
        }

        $fmt = new IntlDateFormatter(
            $this->getCode(),
            $dateStyle->value,
            IntlDateFormatter::NONE,
            $value->getTimezone(),
            IntlDateFormatter::GREGORIAN,
            $pattern
        );

        return $fmt->format($value->getTimestamp());
    }

    public function formatTime(
        string|DateTimeInterface $value,
        TimeStyle $timeStyle = TimeStyle::SHORT
    ): string
    {
        if (is_string($value)) {
            $value = pyncer_date_time($value);
        }

        $fmt = new IntlDateFormatter(
            $this->getCode(),
            IntlDateFormatter::NONE,
            $timeStyle->value,
            $value->getTimezone(),
            IntlDateFormatter::GREGORIAN
        );

        return $fmt->format($value->getTimestamp());
    }

    public function formatDateTime(
        string|DateTimeInterface $value,
        DateStyle $dateStyle = DateStyle::SHORT,
        TimeStyle $timeStyle = TimeStyle::SHORT,
        ?string $patternSkeleton = null
    ): string
    {
        if (is_string($value)) {
            $value = pyncer_date_time($value);
        }

        $pattern = null;
        if ($patternSkeleton !== null) {
            $patternGenerator = new IntlDatePatternGenerator($this->getCode());
            $pattern = $patternGenerator->getBestPattern($patternSkeleton);
        }

        $fmt = new IntlDateFormatter(
            $this->getCode(),
            $dateStyle->value,
            $timeStyle->value,
            $value->getTimezone(),
            IntlDateFormatter::GREGORIAN,
            $pattern
        );

        return $fmt->format($value->getTimestamp());
    }

    public function formatInteger(int $value): string
    {
        $formatter = new NumberFormatter(
            $this->getCode(),
            NumberFormatter::DECIMAL
        );

        $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);

        return $formatter->format($value);
    }
    public function formatDecimal(int|float $value, ?int $decimals = null): string
    {
        if ($decimals !== null && $decimals < 0) {
            throw new InvalidArgumentException(
                'Decimals must be greater than or equal to zero.'
            );
        }

        $formatter = new NumberFormatter(
            $this->getCode(),
            NumberFormatter::DECIMAL
        );

        if ($decimals !== null) {
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        }

        return $formatter->format($value);
    }
    public function formatPercent(int|float $value, ?int $decimals = null): string
    {
        if ($decimals !== null && $decimals < 0) {
            throw new InvalidArgumentException(
                'Decimals must be greater than or equal to zero.'
            );
        }

        $formatter = new NumberFormatter(
            $this->getCode(),
            NumberFormatter::PERCENT
        );

        if ($decimals !== null) {
            $formatter->setAttribute(NumberFormatter::MIN_FRACTION_DIGITS, $decimals);
            $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, $decimals);
        }

        return $formatter->format($value);
    }

    public function formatOrdinal(int $value): string
    {
        $formatter = new NumberFormatter(
            $this->getCode(),
            NumberFormatter::ORDINAL
        );

        return $formatter->format($value);
    }
    public function formatSpellout(int|float $value, ?int $decimals = null): string
    {
        if ($decimals !== null) {
            if ($decimals < 0) {
                throw new InvalidArgumentException(
                    'Decimals must be greater than or equal to zero.'
                );
            }

            $value = round($value, $decimals);
        }

        $formatter = new NumberFormatter(
            $this->getCode(),
            NumberFormatter::SPELLOUT
        );

        return $formatter->format($value);
    }
    public function formatDuration(int $value): string
    {
        $formatter = new NumberFormatter(
            $this->getCode(),
            NumberFormatter::DURATION
        );

        return $formatter->format($value);
    }

    public function formatCurrency(
        int|float $value,
        ?string $currencyCode = null,
        bool $trimDecimals = false,
        bool $negativeZero = false,
        bool $accounting = false,
    ): string
    {
        $formatter = new NumberFormatter(
            $this->getCode(),
            (
                $accounting ?
                NumberFormatter::CURRENCY:
                NumberFormatter::CURRENCY_ACCOUNTING
            )
        );

        if ($value === 0 || $value === 0.0) {
            if ($negativeZero) {
                $value = -1;
            } else {
                $negativeZero = false;
            }

            if ($trimDecimals) {
                $formatter->setAttribute(NumberFormatter::MAX_FRACTION_DIGITS, 0);
            }
        }

        if ($currencyCode === null) {
            $currency = $formatter->format($value);
        } else {
            $currency = $formatter->formatCurrency($value, $currencyCode);
        }

        if ($negativeZero) {
            $currency = str_replace('1', '0', $currency);
        }

        return $currency;
    }

    public function formatLength(
        int|float $value,
        int $decimals,
        LengthUnit $unit,
        bool $short = false,
    ): string
    {
        $rule = $this->getCardinalRule($value);
        $value = $this->formatDecimal($value, $decimals);

        $key = 'unit.length.' . $unit->getName();
        if ($short) {
            $key .= '.short';
        }

        if ($this->has($key)) {
            return $this->get($key, ['value' => $value], $rule);
        }

        $messages = $this->getResourceMessages($unit, $short);
        $message = $messages[$rule] ?? $messages['other'] ?? '{0}';
        return str_replace('{0}', $value, $message);
    }

    public function formatMass(
        int|float $value,
        int $decimals,
        MassUnit $unit,
        bool $short = false,
    ): string
    {
        $rule = $this->getCardinalRule($value);
        $value = $this->formatDecimal($value, $decimals);

        $key = 'unit.mass.' . $unit->getName();
        if ($short) {
            $key .= '.short';
        }

        if ($this->has($key)) {
            return $this->get($key, ['value' => $value], $rule);
        }

        $messages = $this->getResourceMessages($unit, $short);
        $message = $messages[$rule] ?? $messages['other'] ?? '{0}';
        return str_replace('{0}', $value, $message);
    }

    public function formatSize(
        int $value,
        SizeUnit $unit,
        bool $short = false,
    ): string
    {
        $rule = $this->getCardinalRule($value);
        $value = $this->formatInteger($value);

        $key = 'unit.size.' . $unit->getName();
        if ($short) {
            $key .= '.short';
        }

        if ($this->has($key)) {
            return $this->get($key, ['value' => $value], $rule);
        }

        $messages = $this->getSizeMessages($unit, $short);
        $message = $messages[$rule] ?? $messages['other'] ?? '{0}';
        return str_replace('{0}', $value, $message);
    }

    protected function getSizeMessages(
        SizeUnit $unit,
        bool $short = false
    ) {
        if ($short) {
            return static::$sizeMessagesShort[$unit->getName()];
        }

        return static::$sizeMessages[$unit->getName()];
    }

    private function getResourceMessages(
        UnitInterface $unit,
        bool $short = false
    ) {
        $bundleKey = 'units';
        $messageKey = $unit->getType();
        if ($short) {
            $bundleKey .= 'Short';
            $messageKey .= 'Short';
        }

        $unitName = $unit->getName();

        // Resource bundle doesn't have separate for long ton
        if ($unitName === 'long-ton') {
            $unitName = 'ton';
        } elseif ($unitName === 'troy_ounce') {
            $unitName = 'ounce-troy';
        }

        if (isset($this->messages[$messageKey][$unitName])) {
            return $this->messages[$messageKey][$unitName];
        }

        if ($this->resourceBundle === null) {
            $this->resourceBundle = new ResourceBundle(
                $this->getCode(),
                'ICUDATA-unit'
            );
        }

        if ($this->resourceBundleShort === null &&
            $this->getCode() !== $this->getCodeShort()
        ) {
            $this->resourceBundleShort = new ResourceBundle(
                $this->getCodeShort(),
                'ICUDATA-unit'
            );
        }

        $messages = [];

        $bundle = $this->resourceBundle[$bundleKey]
            [$unit->getType()]
            [$unitName] ?? null;

        // TODO: Support 'case' and 'per' (Maybe with tranforms and an enum)
        if ($bundle !== null) {
            foreach ($bundle as $key => $value) {
                switch ($key) {
                    case 'zero':
                    case 'one':
                    case 'two':
                    case 'few':
                    case 'many':
                    case 'other':
                        $messages[$key] = $value;
                        break;
                    default:
                        continue 2;
                }
            }
        }

        if ($this->resourceBundleShort) {
            $bundle = $this->resourceBundleShort[$bundleKey]
                [$unit->getType()]
                [$unitName] ?? null;

            foreach ($bundle as $key => $value) {
                switch ($key) {
                    case 'zero':
                    case 'one':
                    case 'two':
                    case 'few':
                    case 'many':
                    case 'other':
                        $messages[$key] = $value;
                        break;
                    default:
                        continue 2;
                }
            }
        }

        $this->messages[$messageKey][$unitName] = $messages;

        return $messages;
    }

    protected static $sizeMessages = [
        'byte' => [
            'one' => '{0} byte',
            'other' => '{0} bytes',
        ],

        'kilobyte' => [
            'one' => '{0} kilobyte',
            'other' => '{0} kilobytes',
        ],
        'megabyte' => [
            'one' => '{0} megabyte',
            'other' => '{0} megabytes',
        ],
        'gigabyte' => [
            'one' => '{0} gigabyte',
            'other' => '{0} gigabytes',
        ],
        'terabyte' => [
            'one' => '{0} terabyte',
            'other' => '{0} terabytes',
        ],
        'petabyte' => [
            'one' => '{0} petabyte',
            'other' => '{0} petabytes',
        ],
        'exabyte' => [
            'one' => '{0} exabyte',
            'other' => '{0} exabytes',
        ],
        'zettabyte' => [
            'one' => '{0} zettabyte',
            'other' => '{0} zettabytes',
        ],
        'yottabyte' => [
            'one' => '{0} yottabyte',
            'other' => '{0} yottabytes',
        ],

        'kibibyte' => [
            'one' => '{0} kibibyte',
            'other' => '{0} kibibytes',
        ],
        'mebibyte' => [
            'one' => '{0} mebibyte',
            'other' => '{0} mebibytes',
        ],
        'gibibyte' => [
            'one' => '{0} gibibyte',
            'other' => '{0} gibibytes',
        ],
        'tebibyte' => [
            'one' => '{0} tebibyte',
            'other' => '{0} tebibytes',
        ],
        'pebibyte' => [
            'one' => '{0} pebibyte',
            'other' => '{0} pebibytes',
        ],
        'exbibyte' => [
            'one' => '{0} exbibyte',
            'other' => '{0} exbibytes',
        ],
        'zebibyte' => [
            'one' => '{0} zebibyte',
            'other' => '{0} zebibytes',
        ],
        'yobibyte' => [
            'one' => '{0} yobibyte',
            'other' => '{0} yobibytes',
        ],
    ];

    protected static $sizeMessagesShort = [
        'byte' => [
            'one' => '{0} B',
            'other' => '{0} B',
        ],

        'kilobyte' => [
            'one' => '{0} kB',
            'other' => '{0} kB',
        ],
        'megabyte' => [
            'one' => '{0} MB',
            'other' => '{0} MB',
        ],
        'gigabyte' => [
            'one' => '{0} GB',
            'other' => '{0} GB',
        ],
        'terabyte' => [
            'one' => '{0} TB',
            'other' => '{0} TB',
        ],
        'petabyte' => [
            'one' => '{0} PB',
            'other' => '{0} PB',
        ],
        'exabyte' => [
            'one' => '{0} EB',
            'other' => '{0} EB',
        ],
        'zettabyte' => [
            'one' => '{0} ZB',
            'other' => '{0} ZB',
        ],
        'yottabyte' => [
            'one' => '{0} YB',
            'other' => '{0} YB',
        ],

        'kibibyte' => [
            'one' => '{0} KiB',
            'other' => '{0} KiB',
        ],
        'mebibyte' => [
            'one' => '{0} MiB',
            'other' => '{0} MiB',
        ],
        'gibibyte' => [
            'one' => '{0} GiB',
            'other' => '{0} GiB',
        ],
        'tebibyte' => [
            'one' => '{0} TiB',
            'other' => '{0} TiB',
        ],
        'pebibyte' => [
            'one' => '{0} PiB',
            'other' => '{0} PiB',
        ],
        'exbibyte' => [
            'one' => '{0} EiB',
            'other' => '{0} EiB',
        ],
        'zebibyte' => [
            'one' => '{0} ZiB',
            'other' => '{0} ZiB',
        ],
        'yobibyte' => [
            'one' => '{0} YiB',
            'other' => '{0} YiB',
        ],
    ];
}
