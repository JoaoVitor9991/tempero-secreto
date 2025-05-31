<?php
// Verificar se é admin
if (!isAdmin()) {
    redirect(SITE_URL . '?route=home');
}

// Buscar estatísticas
try {
    // Total de receitas por status
    $stmt = $pdo->query("
        SELECT status, COUNT(*) as total 
        FROM recipes 
        GROUP BY status
    ");
    $recipes_by_status = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // Total de usuários
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();

    // Total de comentários pendentes
    $stmt = $pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'");
    $pending_comments = $stmt->fetchColumn();

    // Total de categorias
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    $total_categories = $stmt->fetchColumn();

    // Receitas mais recentes
    $stmt = $pdo->query("
        SELECT r.*, u.name as username, c.name as category_name
        FROM recipes r
        LEFT JOIN users u ON r.user_id = u.id
        LEFT JOIN categories c ON r.category_id = c.id
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    $recent_recipes = $stmt->fetchAll();

} catch (PDOException $e) {
    setMessage('danger', 'Erro ao carregar estatísticas: ' . $e->getMessage());
}
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Painel Administrativo</h2>
        <div>
            <a href="<?php echo SITE_URL; ?>?route=home" class="btn btn-secondary">
                <i class="fas fa-home"></i> Voltar ao Site
            </a>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Receitas Pendentes</h5>
                    <p class="card-text display-6">
                        <?php echo $recipes_by_status['pending'] ?? 0; ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>?route=manage_recipes&status=pending" class="text-white">
                        Ver todas <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Receitas Aprovadas</h5>
                    <p class="card-text display-6">
                        <?php echo $recipes_by_status['approved'] ?? 0; ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>?route=manage_recipes&status=approved" class="text-white">
                        Ver todas <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Comentários Pendentes</h5>
                    <p class="card-text display-6">
                        <?php echo $pending_comments; ?>
                    </p>
                    <a href="<?php echo SITE_URL; ?>?route=manage_comments" class="text-white">
                        Ver todos <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total de Usuários</h5>
                    <p class="card-text display-6">
                        <?php echo $total_users; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Links Rápidos -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Gerenciamento</h5>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        <a href="<?php echo SITE_URL; ?>?route=manage_recipes" class="list-group-item list-group-item-action">
                            <i class="fas fa-utensils"></i> Gerenciar Receitas
                        </a>
                        <a href="<?php echo SITE_URL; ?>?route=manage_comments" class="list-group-item list-group-item-action">
                            <i class="fas fa-comments"></i> Gerenciar Comentários
                        </a>
                        <a href="<?php echo SITE_URL; ?>?route=manage_categories" class="list-group-item list-group-item-action">
                            <i class="fas fa-tags"></i> Gerenciar Categorias
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Receitas Recentes</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_recipes)): ?>
                        <p class="text-muted">Nenhuma receita cadastrada.</p>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($recent_recipes as $recipe): ?>
                                <a href="<?php echo SITE_URL; ?>?route=view_recipe&id=<?php echo $recipe['id']; ?>" 
                                   class="list-group-item list-group-item-action" target="_blank">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($recipe['title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('d/m/Y', strtotime($recipe['created_at'])); ?>
                                        </small>
                                    </div>
                                    <small class="text-muted">
                                        Por <?php echo htmlspecialchars($recipe['username']); ?> em 
                                        <?php echo htmlspecialchars($recipe['category_name']); ?>
                                    </small>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div> 