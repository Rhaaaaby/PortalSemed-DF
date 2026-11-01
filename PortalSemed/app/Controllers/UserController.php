<?php

namespace App\Controllers;

use App\Models\Users;
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
        $password = $data['password'] ?? null;

        if (!is_string($name) || !is_string($cpfInput) || !is_string($password) ||
            trim($name) === '' || $cpfInput === '' || $password === '') {
            json_response(['erro' => 'Nome, CPF e senha são obrigatórios.'], 400);
        }

        $name = trim($name);
        $cpf = preg_replace('/\D/', '', $cpfInput);
        if (strlen($name) > 100 || !preg_match('/^\d{11}$/', $cpf)) {
            json_response(['erro' => 'Informe um nome de até 100 caracteres e um CPF válido.'], 400);
        }

        if (strlen($password) < 12) {
            json_response(['erro' => 'A senha deve ter no mínimo 12 caracteres.'], 400);
        }

        if ($this->model->buscarPorCpf($cpf)) {
            json_response(['erro' => 'Este CPF já está cadastrado.'], 409);
        }

        $role = $adminCreation ? ($data['role'] ?? null) : 'funcionario';
        if (!in_array($role, ['admin', 'funcionario'], true)) {
            json_response(['erro' => 'Perfil de usuário inválido.'], 400);
        }

        $sucesso = $this->model->criar([
            'name' => $name,
            'cpf' => $cpf,
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
        $sucesso = $this->model->atualizar($user_id, $data);
        if ($sucesso) {
            json_response(['mensagem' => 'Perfil atualizado com sucesso']);
        } else {
            json_response(['erro' => 'Erro ao atualizar perfil'], 500);
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

    // Função para alterar a senha do usuário logado
    public function alterarSenha(array $data)
    {
        // 1. Capturar o token JWT enviado no cabeçalho HTTP Authorization
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!$authHeader || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            http_response_code(401);
            echo json_encode(['mensagem' => 'Acesso não autorizado. Token ausente.']);
            return;
        }

        $jwt = $matches[1];

        try {
            // 2. Decodificar e validar o token JWT
            $secretKey = $_ENV['JWT_SECRET'] ?? 'sua_chave_secreta_aqui';
            $decoded = \Firebase\JWT\JWT::decode($jwt, new \Firebase\JWT\Key($secretKey, 'HS256'));
            $userId = $decoded->sub ?? $decoded->id; // ID do usuário do token
        } catch (\Exception $e) {
            http_response_code(401);
            echo json_encode(['mensagem' => 'Token inválido ou expirado.']);
            return;
        }

        // 3. Validar se os campos do formulário foram preenchidos
        $senhaAtual = $data['senhaAtual'] ?? '';
        $novaSenha = $data['novaSenha'] ?? '';

        if (empty($senhaAtual) || empty($novaSenha)) {
            http_response_code(400);
            echo json_encode(['mensagem' => 'Preencha a senha atual e a nova senha.']);
            return;
        }

        // 4. Buscar a senha atual do usuário no banco de dados
        $usuario = $this->model->buscarPorId($userId); // Certifique-se que o Model possui essa busca

        if (!$usuario || !password_verify($senhaAtual, $usuario['password'])) {
            http_response_code(400);
            echo json_encode(['mensagem' => 'A senha atual está incorreta.']);
            return;
        }

    

        // 5. Gerar o hash seguro da nova senha com BCRYPT
        $novoHash = password_hash($novaSenha, PASSWORD_BCRYPT);

        // 6. Atualizar a senha no banco de dados via Model
        $atualizou = $this->model->atualizarSenha($userId, $novoHash);

        if ($atualizou) {
            echo json_encode(['mensagem' => 'Senha alterada com sucesso!']);
        } else {
            http_response_code(500);
            echo json_encode(['mensagem' => 'Erro ao atualizar a senha no banco de dados.']);
        }
    }
}