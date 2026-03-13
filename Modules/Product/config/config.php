<?php

declare(strict_types=1);

return [
    'name' => 'Product',
    'pagination' => [
        'per_page' => 15,
        'max_per_page' => 100,
    ],
    'stock' => [
        'low_threshold' => 5,
        'critical_threshold' => 0,
    ],
];
