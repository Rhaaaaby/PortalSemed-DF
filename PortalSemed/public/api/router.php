<?php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Controllers\UserController;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

ini_set('display_errors', 1);
error_reporting(E_ALL);

// ================== HELPERS ==================
// 1. Função para enviar resposta JSON
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Função para obter dados JSON da requisição
function get_json_input() {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

//3. Função para autenticar o usuário via token JWT

// ================== AUTH ==================
function auth(): int {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';

    if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
        json_response(['erro' => 'Token obrigatório'], 401);
    }

    // Decodifica o token JWT (criado em UserController::login)
    try {
        $decoded = JWT::decode($matches[1], new Key($_ENV['JWT_SECRET'], 'HS256'));
        return (int) $decoded->sub;
    } catch (\Exception $e) {
        json_response(['erro' => 'Token inválido'], 401);
    }
}

//3. Função para verificar se o usuário tem a role permitida

function authUser(): object
{
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';

    if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
        json_response(['erro' => 'Token obrigatório'], 401);
    }

    try {
        return JWT::decode(
            $matches[1],
            new Key($_ENV['JWT_SECRET'], 'HS256')
        );
    } catch (\Exception $e) {
        json_response(['erro' => 'Token inválido'], 401);
    }
}

//4. Função para verificar se o usuário é admin
function requireAdmin(): object
{
    $user = authUser();

    if (!isset($user->role) || $user->role !== 'admin') {
        json_response([
            'erro' => 'Acesso negado'
        ], 403);
    }

    return $user;
}

// 5. Função para verificar se o usuário tem a role permitida
// ================== REQUEST ==================
$method = $_SERVER['REQUEST_METHOD'];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/api/', $uri, 2)[1] ?? '';
$uri = strtolower(trim($uri, '/'));

// ================== CONTROLLERS ==================
$userCtrl = new UserController();


// As rotas são definidas abaixo, 
// cada rota corresponde a uma função no UserController

// ================== ROTAS ==================


// 1. -------- ADMIN --------
// Rota para registrar um novo usuário (somente admin)
//Outros usuários não podem registrar novos usuários,
//  apenas o admin pode fazer isso.
if ($uri === 'admin/usuarios' && $method === 'POST') {
    requireAdmin();
    $userCtrl->registrar(get_json_input());
}


// -------- PÚBLICAS --------
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