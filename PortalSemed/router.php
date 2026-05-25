<?php

$publicPath = __DIR__ . '/public';
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

$file = $publicPath . $request;

if ($request !== '/' && file_exists($file) && !is_dir($file)) {
    return false;
}

// Caso contrário, encaminha para o index principal
chdir($publicPath);
require_once $publicPath . '/index.php';