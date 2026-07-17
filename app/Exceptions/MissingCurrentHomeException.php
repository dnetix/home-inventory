<?php

namespace App\Exceptions;

use RuntimeException;

class MissingCurrentHomeException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct(
            'No current home is resolvable. Authenticate a user with a current home, '
            .'or use CurrentHome::override() / Model::forHome() in console and queue contexts.'
        );
    }
}
