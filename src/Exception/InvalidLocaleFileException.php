<?php
namespace Pyncer\I18n\Exception;

use Pyncer\Exception\UnexpectedValueException;
use Throwable;

class InvalidLocaleFileException extends UnexpectedValueException
{
    protected string $localeFile;

    public function __construct(
        string $localeFile,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->localeFile = $localeFile;

        parent::__construct(
            'The specified locale file, ' . $file . ', is invalid.',
            $code,
            $previous
        );
    }

    public function getLocaleFile(): string
    {
        return $this->localeFile;
    }
}
