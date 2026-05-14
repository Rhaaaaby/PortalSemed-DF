<?php
$data = json_encode(['name' => 'Test', 'cpf' => '12345678901', 'password' => 'test123']);
$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => $data
    ]
];
$context = stream_context_create($options);
echo file_get_contents('http://localhost:8000/api/cadastrar', false, $context);
