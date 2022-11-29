<?php
namespace Pyncer\I18n;

use DateTimeInterface;
use Pyncer\Exception\InvalidArgumentException;
use Pyncer\Exception\UnexpectedValueException;
use Pyncer\I18n\LocaleInterface;
use Pyncer\I18n\LocaleSourcer;
use Pyncer\I18n\Rule;
use Pyncer\I18n\DateStyle;
use Pyncer\I18n\ListStyle;
use Pyncer\I18n\TimeStyle;
use Pyncer\Source\SourceDirector;
use Pyncer\Source\SourceMap;
use Pyncer\Unit\LengthUnit;
use Pyncer\Unit\MassUnit;
use Pyncer\Unit\SizeUnit;

use function array_key_exists;
use function array_search;
use function array_values;
use function in_array;
use function strval;

use const DIRECTORY_SEPARATOR as DS;

class I18n
{
    protected SourceMap $sourceMap;
    protected SourceDirector $sourceDirector;
    protected array $locales = [];
    protected array $defaultLocaleCodes = [];

    public function __construct(SourceMap $sourceMap) {
        $this->sourceMap = $sourceMap;
        $this->sourceDirector = new SourceDirector();
    }

    public function hasLocale(string $localeCode): bool
    {
        $localeCode = str_replace('_', '-', strtolower($localeCode));
        return array_key_exists($localeCode, $this->locales);
    }

    public function getLocale(string $localeCode): ?LocaleInterface
    {
        return $this->locales[$localeCode] ?? null;
    }

    public function addLocale(
        string $localeCode,
        bool $isDefault = false
    ): static
    {
        $locale = $this->initializeLocale($localeCode);

        $localeCode = str_replace('_', '-', strtolower($locale->getCode()));
        $this->locales[$localeCode] = $locale;

        if (!$this->sourceDirector->hasSource($localeCode)) {
            $sourcer = new LocaleSourcer($localeCode, $this->sourceMap);
            $this->sourceDirector->addSourcer($sourcer);
        }

        if ($isDefault) {
            // If already in list, move to last position
            $index = array_search(
                $locale->getCode(),
                $this->defaultLocaleCodes
            );

            if ($index !== false) {
                unset($this->defaultLocaleCodes[$index]);
                $this->defaultLocaleCodes = array_values(
                    $this->defaultLocaleCodes
                );
            }

            $this->defaultLocaleCodes[] = $locale->getCode();
        }

        if ($locale->getCodeShort() !== $locale->getCode()) {
            return $this->addLocale($locale->getCodeShort(), $isDefault);
        }

        return $this;
    }

    protected function initializeLocale(string $localeCode): LocaleInterface
    {
        if (!preg_match('/\A[a-zA-Z_-]+\z/', $localeCode)) {
            throw new InvalidArgumentException(
                 'The specified locale code, ' . $localeCode . ', is invalid.'
            );
        }

        $localeCode = str_replace('-', '_', $localeCode);
        $localeCode = explode('_', $localeCode);
        if (count($localeCode) > 1) {
            $localeCode[1] = strtoupper($localeCode[1]);
        }
        $localeCode = implode('_', $localeCode);

        $file = __DIR__ . DS . 'Locale' . DS . $localeCode . '.php';
        if (!file_exists($file)) {
            throw new LocaleNotFoundException($localeCode);
        }

        $class = '\Pyncer\I18n\Locale\\' . $localeCode;

        return new $class($this);
    }

    public function getDefaultLocale(): ?LocaleInterface
    {
        if (!$this->defaultLocaleCodes) {
            throw new UnexpectedValueException(
                'No default locale codes have been specified.'
            );
        }

        return $this->getLocale($this->defaultLocaleCodes[0]);
    }

    public function getDefaultLocaleCodes(): iterable
    {
        return $this->defaultLocaleCodes;
    }
    public function getLocaleCodes(): iterable
    {
        return array_keys($this->locales);
    }

    public function has(
        string $key,
        ?iterable $localeCodes = null,
        ?iterable $sourceNames = null,
    ): bool
    {
        $localeCodes ??= $this->getDefaultLocaleCodes();
        $localeCodes = array_map(function($value) {
            return str_replace('_', '-', strtolower($localeCode));
        }, $localeCodes);
        return $this->sourceDirector->has($key, $localeCodes, $sourceNames);
    }

    public function get(
        string $key,
        Rule $rule = Rule::OTHER,
        iterable $args = [],
        ?iterable $localeCodes = null,
        ?iterable $sourceNames = null,
    ): ?string
    {
        $localeCodes ??= $this->getDefaultLocaleCodes();
        $localeCodes = array_map(function($value) {
            return str_replace('_', '-', strtolower($localeCode));
        }, $localeCodes);
        $result = $this->sourceDirector->get($key, $localeCodes, $sourceNames);

        if ($result === null) {
            return $result;
        }

        $locale = $this->getLocale($result->getSourcerName());

        $value = $locale->pluralize($result->getValue(), $rule);

        if (!$args) {
            return $value;
        }

        $keys = array_keys(pyncer_ensure_array($args));
        rsort($keys); // Reverse sort keys so longest is first

        foreach ($keys as $key) {
            $value = str_replace(
                '{' . $key . '}',
                $args[$key],
                $value
            );

            // Parse out transforms (ie {item:possesive} => John's or Days')
            $pos = 0;
            while (true) {
                $pos = strpos($value, '{' . $key . ':', $pos);
                if ($pos === false) {
                    break;
                }

                // If the '{' is escaped, than continue
                if ($pos > 0 && substr($value, $pos -1, 1) === '\\') {
                    // Check if not escaping the escape
                    if ($pos === 1 || substr($value, $pos -2, 1) !== '\\') {
                        ++$pos;
                        continue;
                    }
                }

                $pos2 = strpos($value, '}', $pos);
                if ($pos2 === false) {
                    break;
                }

                $offset = $pos + 2 + strlen($key);
                $method = substr($value, $offset, $pos2 - $offset - 1);

                $value = $locale->transform($value, $method);

                $value = substr($value, 0, $pos) .
                    $value .
                    substr($value, $pos2 + 1);

                ++$pos;
            }
        }

        return $text;
    }

    public function getCode(): string
    {
        return $this->getDefaultLocale()->getCode();
    }
    public function getCodeShort(): string
    {
        return $this->getDefaultLocale()->getCodeShort();
    }
    public function getName(): string
    {
        return $this->getDefaultLocale()->getName();
    }
    public function getNameShort(): string
    {
        return $this->getDefaultLocale()->getNameShort();
    }

    public function pluralize(array $options, Rule $rule): string
    {
        return $this->getDefaultLocale()->pluralize($options, $rule);
    }

    public function getCardinalRule(int|float $value, bool $none = false)
    {
        return $this->getDefaultLocale()->getCardinalRule($value, $none);
    }

    public function getRangeRule(int|float $startValue, int|float $endValue)
    {
        return $this->getDefaultLocale()->getRangeRule($startValue, $endValue);
    }

    public function formatList(
        array $items,
        ListStyle $style = ListStyle::AND
    ): string
    {
        return $this->getDefaultLocale()->formatList($items, $style);
    }

    public function formatDate(
        string|DateTimeInterface $value,
        DateStyle $dateStyle = DateStyle::SHORT
    ): string
    {
        return $this->getDefaultLocale()->formatDate(
            $value,
            $dateStyle
        );
    }

    public function formatTime(
        string|DateTimeInterface $value,
        TimeStyle $timeStyle = TimeStyle::SHORT
    ): string
    {
        return $this->getDefaultLocale()->formatTime(
            $value,
            $timeStyle
        );
    }

    public function formatDateTime(
        string|DateTimeInterface $value,
        DateStyle $dateStyle = DateStyle::SHORT,
        TimeStyle $timeStyle = TimeStyle::SHORT,
        ?string $patternSkeleton = null
    ): string
    {
        return $this->getDefaultLocale()->formatDateTime(
            $value,
            $dateStyle,
            $timeStyle,
            $patternSkeleton
        );
    }

    public function formatInteger(int $value): string
    {
        return $this->getDefaultLocale()->formatInteger($value);
    }

    public function formatDecimal(int|float $value, ?int $decimals = null): string
    {
        return $this->getDefaultLocale()->formatDecimal($value, $decimals);
    }

    public function formatPercent(int|float $value, ?int $decimals = null): string
    {
        return $this->getDefaultLocale()->formatPercent($value, $decimals);
    }

    public function formatOrdinal(int $value): string
    {
        return $this->getDefaultLocale()->formatOrdinal($value);
    }
    public function formatSpellout(int|float $value, ?int $decimals = null): string
    {
        return $this->getDefaultLocale()->formatSpellout($value, $decimals);
    }
    public function formatDuration(int $value): string
    {
        return $this->getDefaultLocale()->formatDuration($value);
    }

    public function formatCurrency(
        int|float $value,
        ?string $currencyCode = null,
        bool $trimDecimals = false,
        bool $negativeZero = false,
        bool $accounting = false,
    ): string
    {
        return $this->getDefaultLocale()->formatCurrency(
            $value,
            $currencyCode,
            $trimDecimals,
            $negativeZero,
            $accounting
        );
    }

    public function formatLength(
        float $value,
        int $decimals,
        LengthUnit $unit,
        bool $longForm = false,
    ): string
    {
        return $this->getDefaultLocale()->formatLength(
            $value,
            $decimals,
            $unit,
            $longForm
        );
    }

    public function formatMass(
        float $value,
        int $decimals,
        MassUnit $unit,
        bool $longForm = false,
    ): string
    {
        return $this->getDefaultLocale()->formatMass(
            $value,
            $decimals,
            $unit,
            $longForm
        );
    }

    public function formatSize(
        int $value,
        SizeUnit $unit,
        bool $longForm = false,
    ): string
    {
        return $this->getDefaultLocale()->formatSize(
            $value,
            $unit,
            $longForm
        );
    }
}
