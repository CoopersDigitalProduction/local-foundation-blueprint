<?php

// scoper-autoload.php @generated by PhpScoper

$loader = require_once __DIR__.'/autoload.php';

// Aliases for the whitelisted classes. For more information see:
// https://github.com/humbug/php-scoper/blob/master/README.md#class-whitelisting
if (!class_exists('ComposerAutoloaderInit57132daffde041b2ea4ab33d3c1ce42f', false) && !interface_exists('ComposerAutoloaderInit57132daffde041b2ea4ab33d3c1ce42f', false) && !trait_exists('ComposerAutoloaderInit57132daffde041b2ea4ab33d3c1ce42f', false)) {
    spl_autoload_call('DeliciousBrains\WPMDB\Container\ComposerAutoloaderInit57132daffde041b2ea4ab33d3c1ce42f');
}

// Functions whitelisting. For more information see:
// https://github.com/humbug/php-scoper/blob/master/README.md#functions-whitelisting
if (!function_exists('composerRequire57132daffde041b2ea4ab33d3c1ce42f')) {
    function composerRequire57132daffde041b2ea4ab33d3c1ce42f() {
        return \DeliciousBrains\WPMDB\Container\composerRequire57132daffde041b2ea4ab33d3c1ce42f(...func_get_args());
    }
}

return $loader;
