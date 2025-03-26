<?php
/**
 * PSR-4 compliant class autoloader
 *
 * @see https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader.md
 * @link https://www.php.net/manual/en/function.spl-autoload-register.php
 * @param string $class fully-qualified class name
 * @return void
 */
spl_autoload_register(function ($class) {

    // base directory where all class files are stored
    $base_dir = __DIR__ . '/../src/';

    // convert the fully qualified class name into a file path
    $file = $base_dir . str_replace('\\', '/', $class) . '.php';

    // require the file if it exists
    if (file_exists($file)) {
        require $file;
    }
});

