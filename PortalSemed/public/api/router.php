<?php

require_once __DIR__ . '/../../app/bootstrap.php';

use App\Models\Noticia;
use App\Controllers\NoticiaController;
use App\Controllers\PortalCustomizationController;
use App\Controllers\UserController;
use App\Security\Auth;

if (APP_ENV === 'production') {
    ini_set('display_errors', 0);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

set_exception_handler(static function (Throwable $exception): void {
    error_log((string) $exception);
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode(
        ['erro' => 'Erro interno ao processar a requisição. Consulte os logs do servidor.'],
        JSON_UNESCAPED_UNICODE
    );
});

// ================== HELPERS ==================
// 1. Função para enviar resposta JSON
function json_response($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. Função para obter dados JSON da requisição
function get_json_input() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        json_response(['erro' => 'Corpo JSON inválido.'], 400);
    }

    return $data;
}

function auth(): array
{
    $user = Auth::currentUser();
    if (!$user) {
        json_response(['erro' => 'Autenticação obrigatória.'], 401);
    }

    return $user;
}

function requireAdmin(): array
{
    $user = auth();
    if ($user['role'] !== 'admin') {
        json_response(['erro' => 'Acesso negado.'], 403);
    }

    return $user;
}

function requireCsrf(): void
{
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'HEAD' ||
        (in_array($path, ['/api/login', '/api/password/recovery', '/api/password/reset'], true) &&
            $_SERVER['REQUEST_METHOD'] === 'POST') ||
        empty($_SESSION['user_id'])) {
        return;
    }

    $expected = $_SESSION['csrf_token'] ?? '';
    $provided = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($expected) || $expected === '' || !is_string($provided) ||
        !hash_equals($expected, $provided)) {
        json_response(['erro' => 'Token de segurança inválido ou ausente. Atualize a página e tente novamente.'], 403);
    }
}

// ================== REQUEST ==================
$method = $_SERVER['REQUEST_METHOD'];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = explode('/api/', $uri, 2)[1] ?? '';
$uri = strtolower(trim($uri, '/'));

requireCsrf();

// ================== CONTROLLERS ==================
$userCtrl = new UserController();
$noticiaModel = new Noticia();
$noticiaCtrl = new NoticiaController();
$portalCtrl = new PortalCustomizationController();

// As rotas são definidas abaixo, 
// cada rota corresponde a uma função no UserController

// ================== ROTAS ==================


// 1. -------- ADMIN --------
// Rota para registrar um novo usuário (somente admin)
//Outros usuários não podem registrar novos usuários,

if($uri === 'admin/usuarios' && $method === 'GET') {
    requireAdmin();
    $userCtrl->listarFuncionarios();
}

if ($uri === 'admin/usuarios' && $method === 'POST') {
    requireAdmin();
    $userCtrl->registrar(get_json_input(), true);
}

if (preg_match('/^admin\/usuarios\/(\d+)$/', $uri, $matches) && $method === 'PATCH') {
    $admin = requireAdmin();
    $userCtrl->atualizarStatus((int) $matches[1], get_json_input(), (int) $admin['id']);
}

if ($uri === 'admin/portal' && $method === 'GET') {
    requireAdmin();
    $portalCtrl->adminContent();
}

if ($uri === 'admin/portal/settings' && $method === 'PUT') {
    requireAdmin();
    $portalCtrl->updateSettings(get_json_input());
}

if ($uri === 'admin/portal/social-links' && $method === 'PUT') {
    requireAdmin();
    $portalCtrl->updateSocialLinks(get_json_input());
}

if ($uri === 'admin/portal/contact' && $method === 'PUT') {
    requireAdmin();
    $portalCtrl->updateContactInfo(get_json_input());
}

if ($uri === 'admin/portal/logo' && $method === 'POST') {
    requireAdmin();
    $portalCtrl->uploadLogo();
}

if ($uri === 'admin/portal/carousel' && $method === 'POST') {
    requireAdmin();
    $portalCtrl->addCarouselImage();
}

if (preg_match('/^admin\/portal\/carousel\/(\d+)$/', $uri, $matches) && $method === 'DELETE') {
    requireAdmin();
    $portalCtrl->deleteCarouselImage((int) $matches[1]);
}

if (preg_match('/^admin\/portal\/services\/([a-z0-9-]+)$/', $uri, $matches) && $method === 'PATCH') {
    requireAdmin();
    $portalCtrl->updateService($matches[1], get_json_input());
}

if ($uri === 'portal/services' && $method === 'GET') {
    auth();
    $portalCtrl->manageServices();
}

if ($uri === 'portal/services' && $method === 'POST') {
    auth();
    $portalCtrl->createService(get_json_input());
}

if (preg_match('/^portal\/services\/([a-z0-9-]+)$/', $uri, $matches) && $method === 'PATCH') {
    auth();
    $portalCtrl->updateService($matches[1], get_json_input());
}

if ($uri === 'portal' && $method === 'GET') {
    $portalCtrl->publicContent();
}


// -------- PÚBLICAS --------
if ($uri === 'cadastrar' && $method === 'POST') {
    json_response(['erro' => 'Cadastro público desativado. Solicite acesso a um administrador.'], 410);
}

if ($uri === 'login' && $method === 'POST') {
    $userCtrl->login(get_json_input());
}

if ($uri === 'password/recovery' && $method === 'POST') {
    $userCtrl->requestPasswordReset(get_json_input());
}

if ($uri === 'password/reset' && $method === 'POST') {
    $userCtrl->resetPassword(get_json_input());
}

if ($uri === 'auth/me' && $method === 'GET') {
    $userCtrl->sessao();
}

if ($uri === 'auth/logout' && $method === 'POST') {
    $userCtrl->logout();
}

if ($uri === 'noticias' && $method === 'GET') {
    json_response($noticiaModel->all());
}

if (preg_match('/^noticias\/(\d+)$/', $uri, $matches) && $method === 'GET') {
    $post = $noticiaModel->find((int) $matches[1]);

    if ($post) {
        json_response($post);
    }

    json_response(['erro' => 'Notícia não encontrada'], 404);
}

// -------- USUÁRIO --------
if ($uri === 'perfil' && $method === 'GET') {
    $user = auth();
    $userCtrl->perfil((int) $user['id']);
}

if ($uri === 'atualizar' && $method === 'PUT') {
    $user = auth();
    $userCtrl->atualizar((int) $user['id'], get_json_input());
}

if ($uri === 'deletar' && $method === 'DELETE') {
    $user = auth();
    $userCtrl->deletar((int) $user['id']);
}

if ($uri === 'noticias' && $method === 'POST') {
    $user = auth();
    $noticiaCtrl->create((int) $user['id']);
}

if ($uri === 'alterar-senha' && $method === 'POST') {
    $user = auth();
    $userCtrl->alterarSenha((int) $user['id'], get_json_input());
}

// -------- 404 --------
json_response(['erro' => 'Rota não encontrada'], 404);