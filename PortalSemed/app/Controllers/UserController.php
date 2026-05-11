<?php

namespace App\Controllers;

use App\Models\Users;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController

{
    private Users $model;

    public function index() {
        echo "controller funcionando";
    }
    
    public function __construct()
    {
        $this->model = new Users();
    }

    public function registrar(array $data)
    {
        if (empty($data['name']) || empty($data['cpf']) || empty($data['password'])) {
            json_response(['erro' => 'Nome, CPF e senha são obrigatórios'], 400);
        }

        if (!filter_var($data['cpf'], FILTER_VALIDATE_REGEXP, ['options' => ['regexp' => '/^\d{11}$/']])) {
            json_response(['erro' => 'CPF inválido'], 400);
        }

        if (strlen($data['password']) < 6) {
            json_response(['erro' => 'A senha deve ter no mínimo 6 caracteres'], 400);
        }

        // Verifica se CPF já existe
        if ($this->model->buscarPorCpf($data['cpf'])) {
            json_response(['erro' => 'Este CPF já está cadastrado'], 409);
        }

        $sucesso = $this->model->criar($data);

        if ($sucesso) {
            json_response(['mensagem' => 'Usuário criado com sucesso'], 201);
        } else {
            json_response(['erro' => 'Erro ao criar usuário'], 500);
        }
    }

    public function login(array $data)
    {
        if (empty($data['cpf']) || empty($data['password'])) {
            json_response(['erro' => 'CPF e senha são obrigatórios'], 400);
        }

        $usuario = $this->model->buscarPorCpf($data['cpf']);

        ///var_dump([
        ///'senha_digitada' => $data['password'],
        ///'senha_tipo' => gettype($data['password']),
        ///'hash' => $usuario['password'],
        ///'verify' => password_verify($data['password'], $usuario['password'])
        ///]);
        ///exit;

        if (
        !isset($data['cpf'], $data['password']) ||
        !$usuario ||
        !isset($usuario['password']) ||
        !password_verify($data['password'], $usuario['password'])
        ) {
        json_response(['erro' => 'Credenciais inválidas'], 401);
        }

        $payload = [
            'sub'          => $usuario['id'],
            'name' => $usuario['name'],
            'iat'          => time(),
            'exp'          => time() + (60 * 60 * 24)   // 24 horas
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        json_response([
            'mensagem' => 'Login realizado com sucesso',
            'token'    => $jwt,
            'user'  => [
                'id'           => $usuario['id'],
                'name' => $usuario['name']
            ]
        ]);
    }

    public function perfil(int $user_id)
    {
        $user = $this->model->buscarPorId($user_id);
        if (!$user) {
            json_response(['erro' => 'Usuário não encontrado'], 404);
        }
        json_response(['user' => $user]);
    }

    public function atualizar(int $user_id, array $data)
    {
        $sucesso = $this->model->atualizar($user_id, $data);
        if ($sucesso) {
            json_response(['mensagem' => 'Perfil atualizado com sucesso']);
        } else {
            json_response(['erro' => 'Erro ao atualizar perfil'], 500);
        }
    }

    public function deletar(int $user_id)
    {
        $sucesso = $this->model->deletar($user_id);
        if ($sucesso) {
            json_response(['mensagem' => 'Conta deletada com sucesso']);
        } else {
            json_response(['erro' => 'Erro ao deletar conta'], 500);
        }
    }
}