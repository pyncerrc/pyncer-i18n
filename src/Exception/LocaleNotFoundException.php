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
        $this->localeCode = $localeCode;

        parent::__construct(
            'The specified locale, ' . $localeCode . ', was not found.',
            $code,
            $previous
        );

    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }
}
