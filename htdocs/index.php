<?php
/**
 * 
 */

try {
    $filename = \filter_input(\INPUT_SERVER, 'SITE_CONFIG', \FILTER_SANITIZE_STRING);
    if (empty($filename) || !\file_exists($filename)) {
        throw new \ErrorException('Wrong path to settings', 500);
    }
    $json = \file_get_contents($filename);
    if (empty($json)) {
        throw new \ErrorException('Config is empty', 500);
    }
    $settings = \json_decode($json);
    if (empty($settings)) {
        throw new \ErrorException('Error in config', 500);
    }
    // find package
    $search = \ini_get('include_path');
    if (empty($search)) {
        throw new \ErrorException('Server is not properly configured', 500);
    }
    foreach (\explode(':', $search) as $p) {
        $s = \sprintf('%s%s%s.phar', $p, \DIRECTORY_SEPARATOR, $settings->app->package);
        if (\file_exists($s)) {
            break;
        }
    }
    if (empty($s) || !\file_exists($s)) {
        throw new \ErrorException('package not found', 500);
    }
    // autoload
    $flag = \spl_autoload_register(function ($class) use ($s) {
        $swp = \explode('\\', $class);
        $filename = \sprintf(
            'phar://%s%s%s.php',
            $s,
            \DIRECTORY_SEPARATOR,
            \implode(\DIRECTORY_SEPARATOR, $swp)
            );
        if (\file_exists($filename)) {
            require_once $filename;
        } else {
            // echo '<h1>Cannot find ' , $filename , '</h1>';
        }
    });
    if (! $flag) {
        throw new \ErrorException('Cannot create __autoload', 500);
    }
    (new $settings->app->entry($settings))->run();
} catch (\ErrorException $e) {
    \http_response_code($e->getCode());
} catch (\Exception $e) {
    \http_response_code(500);
}

exit(0);
