<?php

return [
    'app' => [
        'name' => 'KOVCHEG CMS',
        'url' => 'https://example.com',
        // Установщик заменит значение на случайный ключ из 32 байт.
        'key' => 'base64:GENERATED_BY_INSTALLER',
        'debug' => false,
        'timezone' => 'Europe/Moscow',
        'version' => '3.0',
    ],
    'database' => [
        'driver' => 'mysql',
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'kovcheg',
        'user' => 'kovcheg',
        'password' => '',
        'charset' => 'utf8mb4',
    ],
];
