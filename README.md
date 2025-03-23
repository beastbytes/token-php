# BeastBytes Token PHP
PHP file storage for the [BeastBytes Token](https://github.com/beastbytes/token.git) package.

Do not use this package directly;
use TokenManager in [BeastBytes Token](https://github.com/beastbytes/token.git) package.

## Requirements
* PHP 8.1 or higher.

## Installation
Installed the package with Composer:
```php
composer require beastbytes/token-php
```
or add the following to the 'require' section composer.json:
```json
"beastbytes/token-php": "^1.0"
```

## Usage
If using directly:
```php
$tokenManager = new BeastBytes\Token\TokenManager(
    new BeastBytes\Token\Factory\Uuid4\TokenFactory(),
    new BeastBytes\Token\Storage\Php\TokenStorage() // or any other TokenStorageInterface implementation
);
```
or define in the "di" section of Yii3 configuration:

```php
return [
    TokenFactoryInterface::class => \BeastBytes\Token\Factory\Uuid4\TokenFactory::class,
    TokenStorageInterface::class => [
        'class' => TokenStorage::class,
        '__construct()' => [
            // constructor arguments for the TokenStorage class
        ],
    ],
    ManagerInterface::class => [
        'class' => Manager::class,
        '__construct()' => [
            'factory' => Reference::to(TokenFactoryInterface::class),
            'storage' => Reference::to(TokenStorageInterface::class),
        ],
    ],
];
```