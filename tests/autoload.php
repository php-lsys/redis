<?php
if (is_file(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} elseif (is_file(__DIR__ . '/../../../autoload.php')) {
    require __DIR__ . '/../../../autoload.php';
} else {
    echo 'Cannot find the vendor directory, have you executed composer install?' . PHP_EOL;
    echo 'See https://getcomposer.org to get Composer.' . PHP_EOL;
    exit(1);
}

\LSYS\Config\File::dirs(array(
    __DIR__."/config",
));
