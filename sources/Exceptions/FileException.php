<?php

namespace Arris\Entity\Exceptions;

class FileException extends \Exception
{
    public static function create($message = __CLASS__, $args = [], $code = 0): static
    {
        return new static(vsprintf($message, $args), $code);
    }

}