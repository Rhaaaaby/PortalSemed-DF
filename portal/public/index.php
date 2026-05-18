<?php
require_once __DIR__ . '/../app/controllers/NoticiaController.php';

$controller = new NoticiaController();

$action = $_GET['action'] ?? 'index';

switch ($action) {
    case 'create': $controller->create(); break;
    case 'view': $controller->view(); break;
    case 'edit': $controller->edit(); break;
    case 'delete': $controller->delete(); break;
    default: $controller->index(); break;
}


