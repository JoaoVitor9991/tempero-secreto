<?php
// Obter o ID da receita da URL
$recipe_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verificar se o ID é válido
if ($recipe_id <= 0) {
    setMessage('danger', 'ID de receita inválido.');
    redirect(SITE_URL . '?route=feed'); // Redirecionar para o feed ou home
}

// Buscar a receita
try {
    $stmt = $pdo->prepare("
        SELECT r.*, u.name as username, c.name as category_name
        FROM recipes r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN categories c ON r.category_id = c.id
        WHERE r.id = ?
        LIMIT 1
    ");
    $stmt->execute([$recipe_id]);
    $recipe = $stmt->fetch();

    // Se a receita não for encontrada ou não estiver aprovada (a menos que seja admin)
    if (!$recipe || ($recipe['status'] !== 'approved' && !isAdmin())) {
        setMessage('danger', 'Receita não encontrada ou não disponível.');
        redirect(SITE_URL . '?route=feed');
    }

    // Registrar visualização se o usuário estiver logado e não for o autor
    if (isLoggedIn() && $_SESSION['user_id'] != $recipe['user_id']) {
        $user_id = $_SESSION['user_id'];
        // Verificar se o usuário já visualizou recentemente (opcional, para evitar contagens excessivas)
        $stmt_view = $pdo->prepare("SELECT COUNT(*) FROM views WHERE user_id = ? AND recipe_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt_view->execute([$user_id, $recipe_id]);
        $already_viewed = $stmt_view->fetchColumn();

        if ($already_viewed == 0) {
             try {
                $stmt_insert_view = $pdo->prepare("INSERT INTO views (user_id, recipe_id) VALUES (?, ?)");
                $stmt_insert_view->execute([$user_id, $recipe_id]);
            } catch (PDOException $e) {
                // Ignorar erro de visualização duplicada, se a chave UNIQUE já existir
                 if ($e->getCode() != '23000') { // 23000 é o código para violação de integridade (unique constraint)
                    // Logar ou tratar outros erros de visualização, se necessário
                 }
            }
        }
    }

    // Buscar comentários da receita
    $stmt_comments = $pdo->prepare("
        SELECT c.*, u.name as username
        FROM comments c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.recipe_id = ? AND c.status = 'approved' -- Exibir apenas comentários aprovados
        ORDER BY c.created_at DESC
    ");
    $stmt_comments->execute([$recipe_id]);
    $comments = $stmt_comments->fetchAll();

} catch (PDOException $e) {
    setMessage('danger', 'Erro ao carregar a receita: ' . $e->getMessage());
    redirect(SITE_URL . '?route=feed');
}

// Processar envio de comentário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_comment') {
    if (!isLoggedIn()) {
        setMessage('danger', 'Você precisa estar logado para comentar.');
        redirect(SITE_URL . '?route=view_recipe&id=' . $recipe_id);
    }

    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setMessage('danger', 'Erro de segurança. Tente novamente.');
        redirect(SITE_URL . '?route=view_recipe&id=' . $recipe_id);
    }

    $content = filter_input(INPUT_POST, 'content', FILTER_SANITIZE_STRING);

    if (empty($content)) {
        setMessage('danger', 'O conteúdo do comentário não pode ser vazio.');
        redirect(SITE_URL . '?route=view_recipe&id=' . $recipe_id);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO comments (user_id, recipe_id, content, status) VALUES (?, ?, ?, 'pending')"); // Comentários precisam ser aprovados
        $stmt->execute([$_SESSION['user_id'], $recipe_id, $content]);
        setMessage('success', 'Comentário enviado para aprovação.');
    } catch (PDOException $e) {
        setMessage('danger', 'Erro ao adicionar comentário: ' . $e->getMessage());
    }

    redirect(SITE_URL . '?route=view_recipe&id=' . $recipe_id);
}

?>

<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <?php if ($recipe): ?>
                <h2><?php echo htmlspecialchars($recipe['title']); ?></h2>
                <p class="text-muted">Por <?php echo htmlspecialchars($recipe['username']); ?> em <?php echo htmlspecialchars($recipe['category_name']); ?></p>

                <?php if ($recipe['image']): ?>
                    <img src="<?php echo SITE_URL . '/uploads/' . htmlspecialchars($recipe['image']); ?>" class="img-fluid mb-3" alt="Imagem da Receita">
                <?php else: ?>
                    <img src="<?php echo SITE_URL; ?>/assets/images/default-recipe.png" class="img-fluid mb-3" alt="Imagem Padrão">
                <?php endif; ?>

                <h4>Descrição</h4>
                <p><?php echo nl2br(htmlspecialchars($recipe['description'])); ?></p>

                <h4>Ingredientes</h4>
                <p><?php echo nl2br(htmlspecialchars($recipe['ingredients'])); ?></p>

                <h4>Instruções</h4>
                <p><?php echo nl2br(htmlspecialchars($recipe['instructions'])); ?></p>

                <hr>

                <h4>Comentários (<?php echo count($comments); ?>)</h4>

                <?php if (isLoggedIn()): ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Deixar um Comentário</h5>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="add_comment">
                                <div class="mb-3">
                                    <textarea name="content" class="form-control" rows="3" placeholder="Seu comentário..."></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Enviar Comentário</button>
                            </form>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info mb-4">
                        Faça login para deixar um comentário.
                    </div>
                <?php endif; ?>

                <?php if (empty($comments)): ?>
                    <p class="text-muted">Nenhum comentário aprovado ainda.</p>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($comments as $comment): ?>
                            <div class="list-group-item list-group-item-action flex-column align-items-start">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($comment['username'] ?? 'Usuário Desconhecido'); ?></h6>
                                    <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></small>
                                </div>
                                <p class="mb-1"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            <?php endif; // Fim do if ($recipe) ?>
        </div>
    </div>
</div> 