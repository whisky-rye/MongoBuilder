# MongoBuilder

Simple package MongoDB driver for PHP

when you after composer install
1.add line in ./vendor/composer/autoload_classmap.php
    'mysticzhong\\mongobuilder' => $vendorDir . '/mysticzhong/mongobuilder/src/mongobuilder.php',

2.add line in ./vendor/composer/autoload_psr4.php
    'mysticzhong\\' => array($vendorDir . '/mysticzhong/mongobuilder/src'),

3.add line in ./vendor/composer/autoload_static.php
    'mysticzhong\\mongobuilder' => __DIR__ . '/..' . '/mysticzhong/mongobuilder/src/mongobuilder.php',




