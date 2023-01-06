<?php
namespace Pyncer\I18n;

use DateTimeInterface;
use Pyncer\I18n\DateStyle;
use Pyncer\I18n\InvalidLocaleCodeException;
use Pyncer\I18n\LocaleNotFoundException;
use Pyncer\I18n\ListStyle;
use Pyncer\I18n\LocaleInterface;
use Pyncer\I18n\LocaleSourcer;
use Pyncer\I18n\Rule;
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
use function Pyncer\Array\ensure_array as pyncer_ensure_array;
use function strval;

use const DIRECTORY_SEPARATOR as DS;

class I18n
{
    protected SourceMap $sourceMap;
    protected SourceDirector $sourceDirector;
    protected array $locales = [];
    protected ?array $defaultLocaleCodes = null;
    protected ?array $fallbackLocaleCodes = null;

    public function __construct(SourceMap $sourceMap) {
        $this->sourceMap = $sourceMap;
        $this->sourceDirector = new SourceDirector();
    }

    public function hasLocale(string $localeCode): bool
    {
        $localeCode = $this->cleanLocaleCode($localeCode);
        return array_key_exists($localeCode, $this->locales);
    }

    public function getLocale(string $localeCode): LocaleInterface
    {
        $localeCode = $this->cleanLocaleCode($localeCode);

        if (!$this->hasLocale($localeCode)) {
            throw new LocaleNotFoundException($localeCode);
        }

        return $this->locales[$localeCode];
    }

    public function addLocale(string $localeCode): static
    {
        $locale = $this->initializeLocale($localeCode);

        $localeCode = $this->cleanLocaleCode($locale->getCode());
        $this->locales[$localeCode] = $locale;

        if (!$this->sourceDirector->hasSource($localeCode)) {
            $sourcer = new LocaleSourcer($localeCode, $this->sourceMap);
            $this->sourceDirector->addSourcer($sourcer);
        }

        if ($locale->getShortCode() !== $locale->getCode()) {
            $this->addLocale($locale->getShortCode());
        }

        return $this;
    }

    public function getLocales(): array
    {
        return $this->locales;
    }
    public function getLocaleCodes(): array
    {
        return array_keys($this->locales);
    }

    protected function initializeLocale(string $localeCode): LocaleInterface
    {
        if (!preg_match('/\A[a-zA-Z_-]+\z/', $localeCode)) {
            throw new InvalidLocaleCodeException($localeCode);
        }

        $localeCode = $this->cleanLocaleCode($localeCode);
        $localeCode = explode('-', $localeCode);
        $count = count($localeCode);
        if ($count > 1) {
            $localeCode[$count - 1] = strtoupper($localeCode[$count - 1]);
        }
        $localeCode = implode('_', $localeCode);

        $file = __DIR__ . DS . 'Locale' . DS . $localeCode . '.php';
        if (!file_exists($file)) {
            throw new LocaleNotFoundException($localeCode);
        }

        $class = '\Pyncer\I18n\Locale\\' . $localeCode;

        return new $class($this);
    }

    private function cleanLocaleCode(string $localeCode): string
    {
        return str_replace('_', '-', strtolower($localeCode));
    }

    public function getDefaultLocale(): ?LocaleInterface
    {
        $localeCode = $this->getDefaultLocaleCode();

        if ($localeCode !== null) {
            return $this->getLocale($localeCode);
        }

        return null;
    }

    public function getDefaultLocaleCode(): ?string
    {
        if ($this->defaultLocaleCodes !== null) {
            return $this->defaultLocaleCodes[0];
        }

        return null;
    }
    public function setDefaultLocaleCode(?string $value): static
    {
        if ($value === null) {
            $this->defaultLocaleCodes = null;
            return $this;
        }

        $locale = $this->getLocale($value);

        $this->defaultLocaleCodes = [$locale->getCode()];

        if ($locale->getShortCode() !== $locale->getCode()) {
            $this->defaultLocaleCodes[] = $locale->getShortCode();
        }

        return $this;
    }

    public function getFallbackLocale(): ?LocaleInterface
    {
        $localeCode = $this->getFallbackLocaleCode();

        if ($localeCode !== null) {
            return $this->getLocale($localeCode);
        }

        return null;
    }
    public function getFallbackLocaleCode(): ?string
    {
        if ($this->fallbackLocaleCodes !== null) {
            return $this->fallbackLocaleCodes[0];
        }

        return null;
    }
    public function setFallbackLocaleCode(?string $value): static
    {
        if ($value === null) {
            $this->fallbackLocaleCodes = null;
            return $this;
        }

        $locale = $this->getLocale($value);

        $this->fallbackLocaleCodes = [$locale->getCode()];

        if ($locale->getShortCode() !== $locale->getCode()) {
            $this->fallbackLocaleCodes[] = $locale->getShortCode();
        }

        return $this;
    }

    public function has(
        string $key,
        ?iterable $localeCodes = null,
        ?iterable $sourceNames = null,
    ): bool
    {
        $localeCodes = $this->getRankedLocaleCodes($localeCodes);

        return $this->sourceDirector->has($key, $localeCodes, $sourceNames);
    }

    public function get(
        string $key,
        iterable $args = [],
        Rule $rule = Rule::OTHER,
        ?iterable $localeCodes = null,
        ?iterable $sourceNames = null,
    ): string
    {
        $localeCodes = $this->getRankedLocaleCodes($localeCodes);

        $result = $this->sourceDirector->get($key, $localeCodes, $sourceNames);

        if ($result === null) {
            $value = $key;
        } else {
            $locale = $this->getLocale($result->getSourcerName());

            $value = $locale->pluralize($result->getValue(), $rule);
        }

        if (!$args) {
            return $value;
        }

        $args = pyncer_ensure_array($args);
        $keys = array_keys($args);
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

        return $value;
    }

    public function getRankedLocaleCodes(?array $localeCodes = null): array
    {
        if ($localeCodes === null) {
            if ($this->defaultLocaleCodes) {
                $localeCodes = $this->defaultLocaleCodes;

                if ($this->fallbackLocaleCodes) {
                    $localeCodes = [
                        ...$localeCodes,
                        ...$this->fallbackLocaleCodes
                    ];
                }
            } else {
                $localeCodes = $this->getLocaleCodes();
            }
        } else {
            $localeCodes = array_map(
                function($value) {
                    return $this->cleanLocaleCode($value);
                },
                $localeCodes
            );

            if ($this->fallbackLocaleCodes) {
                $localeCodes = [
                    ...$localeCodes,
                    ...$this->fallbackLocaleCodes
                ];
            }
        }

        $localeCodes = array_unique($localeCodes);

        return $localeCodes;
    }

    public function getCode(): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->getCode();
    }
    public function getShortCode(): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->getShortCode();
    }
    public function getName(): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->getName();
    }
    public function getShortName(): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->getShortName();
    }

    public function pluralize(string|array $options, Rule $rule): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->pluralize($options, $rule);
    }

    public function getCardinalRule(
        int|float $value,
        bool $none = false
    ): Rule
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->getCardinalRule($value, $none);
    }

    public function getRangeRule(
        int|float $startValue,
        int|float $endValue
    ): Rule
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->getRangeRule($startValue, $endValue);
    }

    public function formatList(
        array $items,
        ListStyle $style = ListStyle::AND
    ): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatList($items, $style);
    }

    public function formatDate(
        string|DateTimeInterface $value,
        DateStyle $dateStyle = DateStyle::SHORT
    ): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatDate(
            $value,
            $dateStyle
        );
    }

    public function formatTime(
        string|DateTimeInterface $value,
        TimeStyle $timeStyle = TimeStyle::SHORT
    ): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatTime(
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
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatDateTime(
            $value,
            $dateStyle,
            $timeStyle,
            $patternSkeleton
        );
    }

    public function formatInteger(int $value): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatInteger($value);
    }

    public function formatDecimal(int|float $value, ?int $decimals = null): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatDecimal($value, $decimals);
    }

    public function formatPercent(int|float $value, ?int $decimals = null): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatPercent($value, $decimals);
    }

    public function formatOrdinal(int $value): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatOrdinal($value);
    }
    public function formatSpellout(int|float $value, ?int $decimals = null): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatSpellout($value, $decimals);
    }
    public function formatDuration(int $value): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatDuration($value);
    }

    public function formatCurrency(
        int|float $value,
        ?string $currencyCode = null,
        bool $trimDecimals = false,
        bool $negativeZero = false,
        bool $accounting = false,
    ): string
    {
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatCurrency(
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
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatLength(
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
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatMass(
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
        $locale = $this->getDefaultLocale();

        if ($locale === null) {
            throw new UnexpectedValueException(
                'A default locale has not been set.'
            );
        }

        return $locale->formatSize(
            $value,
            $unit,
            $longForm
        );
    }
}
