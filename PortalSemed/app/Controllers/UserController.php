<?php

namespace App\Controllers;

use App\Models\Users;
use App\Services\Mailer;
use App\Security\Auth;

class UserController

{
    private Users $model;
    
    public function __construct()
    {
        $this->model = new Users();
    }

    //1. Função para registrar um novo usuário
    public function registrar(array $data, bool $adminCreation = false): void
    {
        $name = $data['name'] ?? null;
        $cpfInput = $data['cpf'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!is_string($name) || !is_string($cpfInput) || !is_string($email) ||
            !is_string($password) || trim($name) === '' || $cpfInput === '' ||
            trim($email) === '' || $password === '') {
            json_response(['erro' => 'Nome, CPF, e-mail e senha são obrigatórios.'], 400);
        }

        $name = trim($name);
        $cpf = preg_replace('/\D/', '', $cpfInput);
        $email = trim($email);
        if (strlen($name) > 100 || !preg_match('/^\d{11}$/', $cpf)) {
            json_response(['erro' => 'Informe um nome de até 100 caracteres e um CPF válido.'], 400);
        }

        if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['erro' => 'Informe um e-mail válido de até 100 caracteres.'], 400);
        }

        if (strlen($password) < 12) {
            json_response(['erro' => 'A senha deve ter no mínimo 12 caracteres.'], 400);
        }

        if ($this->model->buscarPorCpf($cpf)) {
            json_response(['erro' => 'Este CPF já está cadastrado.'], 409);
        }

        if ($this->model->emailEmUso($email)) {
            json_response(['erro' => 'Este e-mail já está cadastrado.'], 409);
        }

        $role = $adminCreation ? ($data['role'] ?? null) : 'funcionario';
        if (!in_array($role, ['admin', 'funcionario'], true)) {
            json_response(['erro' => 'Perfil de usuário inválido.'], 400);
        }

        $sucesso = $this->model->criar([
            'name' => $name,
            'cpf' => $cpf,
            'email' => $email,
            'password' => $password,
            'role' => $role,
            'status' => true,
        ]);

        if ($sucesso) {
            json_response(['mensagem' => 'Usuário criado com sucesso.'], 201);
        } else {
            json_response(['erro' => 'Erro ao criar usuário.'], 500);
        }
    }

    //2. Função para fazer login
    public function login(array $data)
    {
        // Validação de entrada. obrigatórios: cpf, senha
        if (!isset($data['cpf'], $data['password']) ||
            !is_string($data['cpf']) || !is_string($data['password']) ||
            $data['cpf'] === '' || $data['password'] === '') {
            json_response([
                'erro' => 'CPF e senha são obrigatórios'
            ], 400);
        }

        // Remove caracteres não numéricos do CPF e valida se tem 11 dígitos
        $cpf = preg_replace('/\D/', '', $data['cpf']);

        if (!preg_match('/^\d{11}$/', $cpf)) {
            json_response([
                'erro' => 'CPF inválido'
            ], 400);
        }

        $accounts = $this->model->buscarCredenciaisPorCpf($cpf);
        $matchingAccounts = array_values(array_filter(
            $accounts,
            static fn(array $account): bool => isset($account['password']) &&
                password_verify($data['password'], $account['password'])
        ));

        if (count($matchingAccounts) > 1) {
            json_response([
                'erro' => 'Há mais de uma conta com este CPF e senha. Contate um administrador para corrigir o cadastro.'
            ], 409);
        }

        $usuario = $matchingAccounts[0] ?? null;
        if (!$usuario) {
            json_response([
                'erro' => 'Credenciais inválidas'
            ], 401);
        }

        if (!Auth::isActive($usuario['status'])) {
            json_response([
                'erro' => 'Usuário bloqueado'
            ], 403);
        }

        // Verifica se o usuário tem a role permitida (admin ou funcionario) 
        $rolesPermitidas = [
            'admin',
            'funcionario'
        ];

        //não é permitido que usuários comuns façam login na API
        if (!in_array($usuario['role'], $rolesPermitidas, true)) {
            json_response([
            'erro' => 'Perfil de usuário inválido'
            ], 403);
        }

        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $usuario['id'];
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['session_started'] = time();
        $_SESSION['last_activity'] = time();

        json_response([
            'mensagem' => 'Login realizado com sucesso',
            'user' => [
                'id'     => $usuario['id'],
                'name'   => $usuario['name'],
                'cpf'    => $usuario['cpf'],
                'role'   => $usuario['role'],
                'status' => $usuario['status']
            ],
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    } 

    public function sessao(): void
    {
        $user = Auth::currentUser();
        if (!$user) {
            json_response(['erro' => 'Sessão não autenticada.'], 401);
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        json_response([
            'user' => $user,
            'csrf_token' => $_SESSION['csrf_token'],
        ]);
    }

    public function logout(): void
    {
        Auth::clearSession();
        json_response(['mensagem' => 'Sessão encerrada.']);
    }

    //3. Função para visualizar o perfil do usuário
    public function perfil(int $user_id)
    {
        //pesquisa o Id
        $user = $this->model->buscarPorId($user_id);
        if (!$user) {
            json_response(['erro' => 'Usuário não encontrado'], 404);
        }
        json_response(['user' => $user]);
    }

    //4. Função para atualizar o perfil do usuário 
    public function atualizar(int $user_id, array $data)
    {
        $name = $data['name'] ?? null;
        $cpfInput = $data['cpf'] ?? null;
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? '';
        $currentPassword = $data['current_password'] ?? '';

        if (!is_string($name) || !is_string($cpfInput) || !is_string($email) ||
            !is_string($password) || !is_string($currentPassword) ||
            trim($name) === '' || $cpfInput === '' || trim($email) === '') {
            json_response(['erro' => 'Nome, CPF e e-mail são obrigatórios.'], 400);
        }

        $name = trim($name);
        $cpf = preg_replace('/\D/', '', $cpfInput);
        $email = trim($email);
        if (strlen($name) > 100 || !preg_match('/^\d{11}$/', $cpf)) {
            json_response(['erro' => 'Informe um nome de até 100 caracteres e um CPF válido.'], 400);
        }

        if (strlen($email) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_response(['erro' => 'Informe um e-mail válido de até 100 caracteres.'], 400);
        }

        $currentUser = $this->model->buscarPorId($user_id);
        if (!$currentUser) {
            json_response(['erro' => 'Usuário não encontrado.'], 404);
        }

        $currentCpf = preg_replace('/\D/', '', (string) $currentUser['cpf']);
        if ($cpf !== $currentCpf && $this->model->cpfEmUso($cpf, $user_id)) {
            json_response(['erro' => 'Este CPF já está cadastrado.'], 409);
        }

        if ($this->model->emailEmUso($email, $user_id)) {
            json_response(['erro' => 'Este e-mail já está cadastrado.'], 409);
        }

        $updateData = [
            'name' => $name,
            'email' => $email,
        ];
        if ($cpf !== $currentCpf) {
            $updateData['cpf'] = $cpf;
        }
        if ($password !== '') {
            if (strlen($password) < 12) {
                json_response(['erro' => 'A nova senha deve ter no mínimo 12 caracteres.'], 400);
            }

            $storedPassword = $this->model->buscarHashSenhaPorId($user_id);
            if (!$storedPassword || $currentPassword === '' ||
                !password_verify($currentPassword, $storedPassword)) {
                json_response(['erro' => 'Informe a senha atual correta para alterá-la.'], 400);
            }
            $updateData['password'] = $password;
        }

        $sucesso = $this->model->atualizar($user_id, $updateData);
        if ($sucesso) {
            json_response(['mensagem' => 'Perfil atualizado com sucesso.']);
        } else {
            json_response(['erro' => 'Não foi possível atualizar o perfil.'], 500);
        }
    }

    //5. Função para deletar o perfil do usuário
    public function deletar(int $user_id)
    {
        $user = $this->model->buscarPorId($user_id);
        if ($user && $user['role'] === 'admin' &&
            Auth::isActive($user['status']) && $this->model->contarAdministradoresAtivos() <= 1) {
            json_response(['erro' => 'Não é possível desativar o último administrador ativo.'], 409);
        }

        $sucesso = $this->model->deletar($user_id);
        if ($sucesso) {
            json_response(['mensagem' => 'Conta deletada com sucesso']);
        } else {
            json_response(['erro' => 'Erro ao deletar conta'], 500);
        }
    }

    public function listarFuncionarios()
    {
        json_response(['usuarios' => $this->model->listarFuncionarios()]);
    }

    public function atualizarStatus(int $userId, array $data, int $actorId): void
    {
        if (!isset($data['status']) || !is_bool($data['status'])) {
            json_response(['erro' => 'Informe se a conta deve ficar ativa.'], 400);
        }

        if ($userId === $actorId && !$data['status']) {
            json_response(['erro' => 'Você não pode desativar sua própria conta.'], 400);
        }

        $target = $this->model->buscarPorId($userId);
        if (!$target || !in_array($target['role'], ['admin', 'funcionario'], true)) {
            json_response(['erro' => 'Usuário não encontrado.'], 404);
        }

        if ($target['role'] === 'admin' && Auth::isActive($target['status']) &&
            !$data['status'] && $this->model->contarAdministradoresAtivos() <= 1) {
            json_response(['erro' => 'Não é possível desativar o último administrador ativo.'], 409);
        }

        if (!$this->model->atualizarStatus($userId, $data['status'])) {
            json_response(['erro' => 'Não foi possível atualizar o usuário.'], 500);
        }

        json_response(['mensagem' => 'Status do usuário atualizado.']);
    }

    public function alterarSenha(int $userId, array $data): void
    {
        $currentPassword = $data['current_password'] ?? null;
        $newPassword = $data['password'] ?? null;
        if (!is_string($currentPassword) || !is_string($newPassword) ||
            $currentPassword === '' || strlen($newPassword) < 12) {
            json_response(['erro' => 'Informe a senha atual e uma nova senha com no mínimo 12 caracteres.'], 400);
        }

        $storedPassword = $this->model->buscarHashSenhaPorId($userId);
        if (!$storedPassword || !password_verify($currentPassword, $storedPassword)) {
            json_response(['erro' => 'A senha atual está incorreta.'], 400);
        }

        if (!$this->model->atualizar($userId, ['password' => $newPassword])) {
            json_response(['erro' => 'Não foi possível atualizar a senha.'], 500);
        }

        json_response(['mensagem' => 'Senha alterada com sucesso.']);
    }

    public function requestPasswordReset(array $data): void
    {
        $email = $data['email'] ?? null;
        if (!is_string($email) || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            json_response(['erro' => 'Informe um e-mail válido.'], 400);
        }
        $mailConfigurationError = Mailer::configurationError();
        if ($mailConfigurationError !== null) {
            json_response(['erro' => $mailConfigurationError], 503);
        }

        $token = bin2hex(random_bytes(32));
        $user = $this->model->createPasswordResetToken(trim($email), hash('sha256', $token));
        if ($user) {
            try {
                Mailer::sendPasswordReset($user['email'], $user['name'], $token);
            } catch (\Throwable $error) {
                $this->model->removePasswordResetTokens((int) $user['id']);
                error_log('Falha ao enviar e-mail de recuperação de senha: ' . $error->getMessage());
            }
        }

        json_response([
            'mensagem' => 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.',
        ]);
    }

    public function resetPassword(array $data): void
    {
        $token = $data['token'] ?? null;
        $password = $data['password'] ?? null;
        if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/', $token) ||
            !is_string($password) || strlen($password) < 12) {
            json_response(['erro' => 'O link é inválido ou expirou. Informe uma nova senha com no mínimo 12 caracteres.'], 400);
        }

        if (!$this->model->resetPassword(hash('sha256', $token), $password)) {
            json_response(['erro' => 'O link de recuperação é inválido ou expirou. Solicite um novo link.'], 400);
        }

        json_response(['mensagem' => 'Senha redefinida com sucesso.']);
    }
}