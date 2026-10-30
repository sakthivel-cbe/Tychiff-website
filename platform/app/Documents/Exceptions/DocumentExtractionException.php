<?php

namespace App\Documents\Exceptions;

use RuntimeException;

class DocumentExtractionException extends RuntimeException
{
    public function __construct(public readonly string $failureCode)
    {
        parent::__construct('The document text could not be extracted.');
    }
}
