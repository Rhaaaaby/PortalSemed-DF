<?php

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controllers\UserController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ================== HELPERS ==================
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_json_input() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

// ================== AUTH ==================
function auth(): int {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';

    if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
        json_response(['erro' => 'Token obrigatório'], 401);
    }

    try {
        $decoded = JWT::decode($matches[1], new Key($_ENV['JWT_SECRET'], 'HS256'));
        return (int) $decoded->sub;
    } catch (\Exception $e) {
        json_response(['erro' => 'Token inválido'], 401);
    }
}

// ================== REQUEST ==================
$method = $_SERVER['REQUEST_METHOD'];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/api/', $uri, 2)[1] ?? '';
$uri = strtolower(trim($uri, '/'));

// ================== CONTROLLERS ==================
$userCtrl = new UserController();

// ================== ROTAS ==================

// -------- PÚBLICAS --------
if ($uri === 'cadastrar' && $method === 'POST') {
    $userCtrl->registrar(get_json_input());
}

if ($uri === 'login' && $method === 'POST') {
    $userCtrl->login(get_json_input());
}

if ($uri === 'noticias' && $method === 'GET') {
    require_once __DIR__ . '/../../app/Models/Noticia.php';
    json_response(Noticia::all());
}

if (preg_match('/^noticias\/(\d+)$/', $uri, $matches) && $method === 'GET') {
    require_once __DIR__ . '/../../app/Models/Noticia.php';
    $post = Noticia::find((int) $matches[1]);
    if ($post) {
        json_response($post);
    } else {
        json_response(['erro' => 'Notícia não encontrada'], 404);
    }
}

// -------- USUÁRIO --------
if ($uri === 'perfil' && $method === 'GET') {
    $user_id = auth();
    $userCtrl->perfil($user_id);
}

if ($uri === 'atualizar' && $method === 'PUT') {
    $user_id = auth();
    $userCtrl->atualizar($user_id, get_json_input());
}

if ($uri === 'deletar' && $method === 'DELETE') {
    $user_id = auth();
    $userCtrl->deletar($user_id);
}

// -------- 404 --------
json_response(['erro' => 'Rota não encontrada'], 404);