<?php

declare(strict_types=1);

namespace Modules\Product\Exceptions;

use Symfony\Component\HttpFoundation\Response;

final class ProductNotFoundException extends \RuntimeException
{
    public function __construct(string $message = 'Product not found')
    {
        parent::__construct($message, Response::HTTP_NOT_FOUND);
    }
}
