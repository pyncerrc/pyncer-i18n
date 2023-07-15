<?php
namespace Pyncer\I18n\Exception;

use Pyncer\Exception\InvalidArgumentException;
use Throwable;

class InvalidLocaleCodeException extends InvalidArgumentException
{
    protected string $localeCode;

    public function __construct(
        string $localeCode,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->localeCode = $localeCode;

        parent::__construct(
            'The specified locale code, ' . $localeCode . ', is invalid.',
            $code,
            $previous
        );
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }
}
