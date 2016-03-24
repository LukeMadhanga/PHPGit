<?php

/**
 * Autoload the Git library
 */
spl_autoload_register(function ($class) {
    $nonamespace = explode("\\", $class);
    $c = end($nonamespace);
    $dir = __DIR__;
    if (is_readable("{$dir}/{$c}.php")) {
        require_once("{$dir}/{$c}.php");
        return;
    }
    return;
});