<?php
namespace Pyncer\I18n\Exception;

use Pyncer\Exception\RuntimeException;
use Throwable;

class LocaleNotFoundException extends RuntimeException
{
    protected string $localeCode;

    public function __construct(
        string $localeCode,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct(
            'The specified locale, ' . $localeCode . ', was not found.',
            $code,
            $previous
        );

        $this->localeCode = $localeCode;
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }
}
