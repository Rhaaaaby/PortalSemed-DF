<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../app/bootstrap.php';

use App\Models\Users;

$name = trim((string) ($_ENV['FIRST_ADMIN_NAME'] ?? getenv('FIRST_ADMIN_NAME') ?: ''));
$cpf = preg_replace('/\D/', '', (string) ($_ENV['FIRST_ADMIN_CPF'] ?? getenv('FIRST_ADMIN_CPF') ?: ''));
$password = (string) ($_ENV['FIRST_ADMIN_PASSWORD'] ?? getenv('FIRST_ADMIN_PASSWORD') ?: '');

if ($name === '' || strlen($name) > 100 || !preg_match('/^\d{11}$/', $cpf) ||
    strlen($password) < 12) {
    fwrite(STDERR, "Configure FIRST_ADMIN_NAME, FIRST_ADMIN_CPF (11 dígitos) e FIRST_ADMIN_PASSWORD (mínimo de 12 caracteres) no ambiente.\n");
    exit(1);
}

$users = new Users();
if (!$users->criarPrimeiroAdministrador([
    'name' => $name,
    'cpf' => $cpf,
    'password' => $password,
])) {
    fwrite(STDERR, "Não foi criado: já existe um administrador ou o CPF informado já está cadastrado.\n");
    exit(1);
}

fwrite(STDOUT, "Primeiro administrador criado. Remova imediatamente as variáveis FIRST_ADMIN_* do ambiente.\n");
