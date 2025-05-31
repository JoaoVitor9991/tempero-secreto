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
if (!isset($data['recipe_id']) || !isset($data['csrf_token'])) {
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

$recipe_id = (int)$data['recipe_id'];
$user_id = $_SESSION['user_id'];

try {
    // Verificar se a receita existe
    $stmt = $pdo->prepare("SELECT id FROM recipes WHERE id = ?");
    $stmt->execute([$recipe_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Receita não encontrada']);
        exit;
    }

    // Verificar se já curtiu
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND recipe_id = ?");
    $stmt->execute([$user_id, $recipe_id]);
    $existing_like = $stmt->fetch();

    if ($existing_like) {
        // Descurtir
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND recipe_id = ?");
        $stmt->execute([$user_id, $recipe_id]);
    } else {
        // Curtir
        $stmt = $pdo->prepare("INSERT INTO likes (user_id, recipe_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $recipe_id]);
    }

    // Contar total de curtidas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM likes WHERE recipe_id = ?");
    $stmt->execute([$recipe_id]);
    $likes_count = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'likes_count' => $likes_count,
        'action' => $existing_like ? 'unliked' : 'liked'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao processar curtida']);
} 