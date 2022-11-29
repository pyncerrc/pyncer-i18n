<?php
namespace Pyncer\I18n;

use Pyncer\I18n\Exception\InvalidLocaleFileException;
use Pyncer\Source\AbstractSourcer;
use Pyncer\Source\Exception\SourceNotFoundException;

use function array_key_exists;
use function array_merge;
use function file_exists;
use function file_get_contents;
use function in_array;
use function json_decode;

use const DIRECTORY_SEPARATOR as DS;

class LocaleSourcer extends AbstractSourcer
{
    private $values = [];

    protected function getSourceValue(
        string $sourceName,
        string $key
    ): ?string
    {
        $this->loadLocaleValues($sourceName);

        return $this->values[$sourceName][$key] ?? null;
    }

    private function loadLocaleValues(string $sourceName): void
    {
        if ($this->hasLoaded($sourceName)) {
            return;
        }

        $localeValues = [];

        foreach ($this->getSourceMap()[$sourceName] as $dir) {
            $file = $dir . DS . $this->getName() . '.json';

            if (!file_exists($file)) {
                continue;
            }

            $values = file_get_contents($file);
            $values = json_decode($values, true);

            if (!is_array($values)) {
                throw new InvalidLocaleFileException($file);
            }

            $localeValues = array_merge($localeValues, $values);
        }

        $this->addValues($sourceName, $localeValues);
    }

    private function hasLoaded(string $sourceName): bool
    {
        if (!array_key_exists($sourceName, $this->values)) {
            return false;
        }

        return true;
    }

    private function addValues(string $sourceName, array $values): void
    {
        if (!isset($this->values[$sourceName])) {
            $this->values[$sourceName] = [];
        }

        $this->values[$sourceName] = array_merge(
            $this->values[$sourceName],
            $values
        );
    }
}
