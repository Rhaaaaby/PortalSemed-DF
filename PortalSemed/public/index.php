<?php
echo "o sistema acordou";

ini_set('display_errors', 1);
error_reporting(E_ALL);

//require_once __DIR__ . '/../app/bootstrap.php';

$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($request, '/api/') !== false) {
    require_once __DIR__ . '/api/router.php';
    exit;
}

//fallback para identificar rotas de api e páginas html

//API
if (strpos($request, '/api') === 0) {
    require_once __DIR__ . '/api/router.php';
    exit;
}

// Se for arquivo real (css, js, imagem)
$file = __DIR__ . $request;
if ($request !== '/' && file_exists($file)) {
    return false; // deixa o servidor servir direto
}

// Front (SPA ou páginas)
//require_once __DIR__ . '/pages/index.php';

?>