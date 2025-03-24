<?php

declare(strict_types=1);

use BeastBytes\Token\Php\TokenStorage;
use BeastBytes\Token\TokenStorageInterface;

/** @var array $params */

return [
    TokentorageInterface::class => [
        'class' => TokenStorage::class,
        '__construct()' => [
            'filePath' => $params['beastbytes/token']['filePath'], // or any other file path,
        ],
    ]
];
