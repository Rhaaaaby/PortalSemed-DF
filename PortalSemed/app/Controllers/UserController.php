<?php

namespace App\Controllers;

use App\Models\Users;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController

{
    private Users $model;
    
    public function __construct()
    {
        $this->model = new Users();
    }

    //1. Função para registrar um novo usuário
    public function registrar(array $data)
    {
        // 1. Valida os dados de entrada. Obrigatórios: name, cpf, senha
        if (empty($data['name']) || 
            empty($data['cpf']) || 
            empty($data['password'])
            ) {
            json_response([
                'erro' => 'Nome, CPF e senha são obrigatórios'], 400);
        }

        // 2. Verifica se o CPF é válido (apenas números, 11 dígitos)
        if (!filter_var($data['cpf'], 
            FILTER_VALIDATE_REGEXP, 
            ['options' => [
                'regexp' => '/^\d{11}$/']
            ])

            ) {
            json_response([
                'erro' => 'CPF inválido'], 400);
        }

        // 3. Verifica se a senha tem pelo menos 6 caracteres
        if (
            strlen($data['password']) < 6
            ) {
            json_response([
                'erro' => 'A senha deve ter no mínimo 6 caracteres'], 400);
        }

        // 4.  Verifica se CPF já existe
        if ($this->model->buscarPorCpf(
            $data['cpf'])
            ) {
            json_response([
                'erro' => 'Este CPF já está cadastrado'], 409);
            }

        // Usuários criados por esta função serão funcionários.
        // 5. A criação de administradores será uma operação separada.
        $data['role'] = 'funcionario';

        // 6. Cria o usuário caso o CPF seja válido e não exista no banco de dados
        $sucesso = $this->model->criar($data);

        //mensagem de sucesso ou erro ao criar usuário
        if ($sucesso) {
            json_response([
                'mensagem' => 'Usuário criado com sucesso'], 201);
        } else {
            json_response([
                'erro' => 'Erro ao criar usuário'], 500);
        }
    }

    //2. Função para fazer login
    public function login(array $data)
    {
        // Validação de entrada. obrigatórios: cpf, senha
        if (
            empty($data['cpf']) ||
            empty($data['password'])
        ) {
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

        $usuario = $this->model->buscarCredenciaisPorCpf($data['cpf']);

        // Verifica se o usuário existe, se a senha está correta e se o usuário está ativo
        if (
            !$usuario ||
            !isset($usuario['password']) ||
            !password_verify(
                $data['password'],
                $usuario['password']
            )
        ) {
            json_response([
                'erro' => 'Credenciais inválidas'
            ], 401);
        }

        if (!$usuario['status']) {
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

        // Gera o token JWT
        $payload = [
            'sub'  => $usuario['id'],
            'name' => $usuario['name'],
            'role' => $usuario['role'],
            'iat'  => time(),
            'exp'  => time() + (60 * 60 * 24) // 1 dia (24horas)
        ];

        $jwt = JWT::encode(
            $payload,
            $_ENV['JWT_SECRET'],
            'HS256'
        );

        json_response([
            'mensagem' => 'Login realizado com sucesso',

            'token' => $jwt,

            'user' => [
                'id'     => $usuario['id'],
                'name'   => $usuario['name'],
                'cpf'    => $usuario['cpf'],
                'role'   => $usuario['role'],
                'status' => $usuario['status']
            ]
        ]);
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
        $sucesso = $this->model->deletar($user_id);
        if ($sucesso) {
            json_response(['mensagem' => 'Conta deletada com sucesso']);
        } else {
            json_response(['erro' => 'Erro ao deletar conta'], 500);
        }
    }

    public function listarFuncionarios()
    {
        $funcionarios = $this->model->listarFuncionarios();
        json_response(['funcionarios' => $funcionarios]);    
    }
}