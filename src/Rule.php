<?php
namespace Pyncer\I18n;

enum Rule: string {
    case NONE = 'none';
    case ZERO = 'zero';
    case ONE = 'one';
    case TWO = 'two';
    case FEW = 'few';
    case MANY = 'many';
    case OTHER = 'other';

    public function getName(): string
    {
        return $this->value;
    }
}
