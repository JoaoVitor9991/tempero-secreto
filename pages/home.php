<?php
// Buscar receitas aprovadas
$limit = (int)ITEMS_PER_PAGE;
$stmt = $pdo->prepare("
    SELECT r.*, u.name as author_name, c.name as category_name 
    FROM recipes r 
    JOIN users u ON r.user_id = u.id 
    JOIN categories c ON r.category_id = c.id 
    WHERE r.status = 'approved' 
    ORDER BY r.created_at DESC 
    LIMIT $limit
");
$stmt->execute();
$recipes = $stmt->fetchAll();

// Buscar categorias para o filtro
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<div class="row mb-4">
    <div class="col-md-8">
        <h1>Receitas Aprovadas</h1>
    </div>
    <div class="col-md-4">
        <form action="" method="GET" class="d-flex">
            <select name="category" class="form-select me-2">
                <option value="">Todas as categorias</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </form>
    </div>
</div>

<div class="row">
    <?php if (empty($recipes)): ?>
        <div class="col-12">
            <div class="alert alert-info">
                Nenhuma receita encontrada.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($recipes as $recipe): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if ($recipe['image']): ?>
                        <img src="<?php echo htmlspecialchars($recipe['image']); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlspecialchars($recipe['title']); ?>">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                        <p class="card-text">
                            <?php echo htmlspecialchars(substr($recipe['description'], 0, 100)) . '...'; ?>
                        </p>
                        <p class="card-text">
                            <small class="text-muted">
                                Por <?php echo htmlspecialchars($recipe['author_name']); ?> em 
                                <?php echo htmlspecialchars($recipe['category_name']); ?>
                            </small>
                        </p>
                        <a href="<?php echo SITE_URL; ?>?route=view_recipe&id=<?php echo $recipe['id']; ?>" 
                           class="btn btn-primary">
                            Ver Receita
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?> 