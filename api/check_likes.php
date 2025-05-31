<?php
require_once '../config/config.php';

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Verificar se o usuário está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Obter dados do POST
$data = json_decode(file_get_contents('php://input'), true);

// Validar dados
if (!isset($data['recipe_ids']) || !isset($data['csrf_token']) || !is_array($data['recipe_ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Verificar CSRF token
if (!verifyCSRFToken($data['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Token inválido']);
    exit;
}

$recipe_ids = array_map('intval', $data['recipe_ids']);
$user_id = $_SESSION['user_id'];

try {
    // Buscar receitas curtidas pelo usuário
    $placeholders = str_repeat('?,', count($recipe_ids) - 1) . '?';
    $stmt = $pdo->prepare("SELECT recipe_id FROM likes WHERE user_id = ? AND recipe_id IN ($placeholders)");
    $params = [$user_id];
    $params = array_merge($params, $recipe_ids);
    $stmt->execute($params);
    
    $liked_recipes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode([
        'success' => true,
        'liked_recipes' => $liked_recipes
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao verificar curtidas']);
} 