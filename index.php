<?php
require_once 'config/config.php';

// Roteamento básico
$route = $_GET['route'] ?? 'home';

// Verificar se o usuário está logado para rotas protegidas
$public_routes = ['home', 'login', 'register', 'view_recipe', 'feed'];
if (!in_array($route, $public_routes) && !isLoggedIn()) {
    redirect(SITE_URL . '?route=login');
}

// Verificar se o usuário é admin para rotas administrativas
$admin_routes = ['admin', 'manage_recipes', 'manage_comments', 'manage_categories'];
if (in_array($route, $admin_routes) && !isAdmin()) {
    redirect(SITE_URL . '?route=home');
}

// Incluir o header
include 'includes/header.php';

// Roteamento
switch ($route) {
    case 'home':
        include 'pages/home.php';
        break;
    case 'login':
        include 'pages/login.php';
        break;
    case 'register':
        include 'pages/register.php';
        break;
    case 'view_recipe':
        include 'pages/view_recipe.php';
        break;
    case 'add_recipe':
        include 'pages/add_recipe.php';
        break;
    case 'edit_recipe':
        include 'pages/edit_recipe.php';
        break;
    case 'feed':
        include 'pages/feed.php';
        break;
    case 'admin':
        include 'pages/admin/dashboard.php';
        break;
    case 'manage_recipes':
        include 'pages/admin/manage_recipes.php';
        break;
    case 'manage_comments':
        include 'pages/admin/manage_comments.php';
        break;
    case 'manage_categories':
        include 'pages/admin/manage_categories.php';
        break;
    default:
        include 'pages/404.php';
        break;
}

// Incluir o footer
include 'includes/footer.php';