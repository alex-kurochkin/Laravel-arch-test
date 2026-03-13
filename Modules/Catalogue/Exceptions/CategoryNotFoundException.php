<?php

declare(strict_types=1);

namespace Modules\Catalogue\Exceptions;

use Symfony\Component\HttpFoundation\Response;

final class CategoryNotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Product not found')
    {
        parent::__construct($message, Response::HTTP_NOT_FOUND);
    }
}
