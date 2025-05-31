<?php
// Parâmetros de paginação e filtro
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize($_GET['sort']) : 'newest';

// Buscar categorias para o filtro
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

// Construir a query base
$query = "SELECT r.*, c.name as category_name, u.username,
          (SELECT COUNT(*) FROM likes WHERE recipe_id = r.id) as likes_count,
          (SELECT COUNT(*) FROM views WHERE recipe_id = r.id) as views_count
          FROM recipes r 
          LEFT JOIN categories c ON r.category_id = c.id 
          LEFT JOIN users u ON r.user_id = u.id 
          WHERE r.status = 'approved'";

$params = [];

// Adicionar filtros
if ($category_id) {
    $query .= " AND r.category_id = ?";
    $params[] = $category_id;
}

if ($search) {
    $query .= " AND (r.title LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Contar total de registros para paginação
$countStmt = $pdo->prepare(str_replace("r.*, c.name as category_name, u.username, (SELECT COUNT(*) FROM likes WHERE recipe_id = r.id) as likes_count, (SELECT COUNT(*) FROM views WHERE recipe_id = r.id) as views_count", "COUNT(*)", $query));
$countStmt->execute($params);
$total_recipes = $countStmt->fetchColumn();

// Calcular total de páginas
$total_pages = ceil($total_recipes / ITEMS_PER_PAGE);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Adicionar ordenação
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY r.created_at ASC";
        break;
    case 'most_liked':
        $query .= " ORDER BY likes_count DESC";
        break;
    case 'most_viewed':
        $query .= " ORDER BY views_count DESC";
        break;
    default: // newest
        $query .= " ORDER BY r.created_at DESC";
}

// Adicionar paginação
$query .= " LIMIT ? OFFSET ?";
$params[] = ITEMS_PER_PAGE;
$params[] = $offset;

// Buscar receitas
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$recipes = $stmt->fetchAll();

// Registrar visualização se o usuário estiver logado
if (isLoggedIn() && !empty($recipes)) {
    $recipe_ids = array_column($recipes, 'id');
    $placeholders = str_repeat('?,', count($recipe_ids) - 1) . '?';
    $stmt = $pdo->prepare("INSERT IGNORE INTO views (user_id, recipe_id) SELECT ?, id FROM recipes WHERE id IN ($placeholders)");
    $view_params = [$_SESSION['user_id']];
    $view_params = array_merge($view_params, $recipe_ids);
    $stmt->execute($view_params);
}
?>

<div class="container py-4">
    <!-- Filtros -->
    <div class="row mb-4">
        <div class="col-md-8">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="route" value="feed">
                
                <div class="col-md-3">
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="">Todas as categorias</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                <?php echo $category_id == $category['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <select name="sort" class="form-select" onchange="this.form.submit()">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Mais recentes</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Mais antigas</option>
                        <option value="most_liked" <?php echo $sort === 'most_liked' ? 'selected' : ''; ?>>Mais curtidas</option>
                        <option value="most_viewed" <?php echo $sort === 'most_viewed' ? 'selected' : ''; ?>>Mais vistas</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar receitas..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <?php if (isLoggedIn()): ?>
        <div class="col-md-4 text-end">
            <a href="<?php echo SITE_URL; ?>?route=add_recipe" class="btn btn-success">
                <i class="fas fa-plus"></i> Nova Receita
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Lista de Receitas -->
    <?php if (empty($recipes)): ?>
        <div class="alert alert-info">
            Nenhuma receita encontrada.
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php foreach ($recipes as $recipe): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <?php if ($recipe['image']): ?>
                            <img src="<?php echo SITE_URL . '/' . $recipe['image']; ?>" 
                                 class="card-img-top" alt="<?php echo htmlspecialchars($recipe['title']); ?>"
                                 style="height: 200px; object-fit: cover;">
                        <?php else: ?>
                            <div class="card-img-top bg-light d-flex align-items-center justify-content-center" 
                                 style="height: 200px;">
                                <i class="fas fa-utensils fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($recipe['title']); ?></h5>
                            <p class="card-text text-muted small">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($recipe['username']); ?><br>
                                <i class="fas fa-folder"></i> <?php echo htmlspecialchars($recipe['category_name']); ?><br>
                                <i class="fas fa-clock"></i> <?php echo date('d/m/Y', strtotime($recipe['created_at'])); ?>
                            </p>
                            <p class="card-text">
                                <?php echo nl2br(htmlspecialchars(substr($recipe['description'], 0, 150))); ?>...
                            </p>
                        </div>
                        
                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="btn-group">
                                    <a href="<?php echo SITE_URL; ?>?route=view_recipe&id=<?php echo $recipe['id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        Ver Receita
                                    </a>
                                    <?php if (isLoggedIn()): ?>
                                        <button type="button" 
                                                class="btn btn-outline-danger btn-sm like-button" 
                                                data-recipe-id="<?php echo $recipe['id']; ?>"
                                                onclick="toggleLike(this)">
                                            <i class="fas fa-heart"></i>
                                            <span class="likes-count"><?php echo $recipe['likes_count']; ?></span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    <i class="fas fa-eye"></i> <?php echo $recipe['views_count']; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?route=feed&page=<?php echo $page-1; ?><?php echo $category_id ? '&category='.$category_id : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $sort ? '&sort='.$sort : ''; ?>">
                                Anterior
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?route=feed&page=<?php echo $i; ?><?php echo $category_id ? '&category='.$category_id : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $sort ? '&sort='.$sort : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?route=feed&page=<?php echo $page+1; ?><?php echo $category_id ? '&category='.$category_id : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $sort ? '&sort='.$sort : ''; ?>">
                                Próxima
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
// Função para curtir/descurtir receita
function toggleLike(button) {
    const recipeId = button.dataset.recipeId;
    const likesCount = button.querySelector('.likes-count');
    
    fetch('<?php echo SITE_URL; ?>/api/toggle_like.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            recipe_id: recipeId,
            csrf_token: '<?php echo generateCSRFToken(); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            button.classList.toggle('active');
            likesCount.textContent = data.likes_count;
        } else {
            alert(data.message || 'Erro ao processar curtida.');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao processar curtida.');
    });
}

// Verificar curtidas existentes ao carregar a página
document.addEventListener('DOMContentLoaded', function() {
    const likeButtons = document.querySelectorAll('.like-button');
    if (likeButtons.length > 0) {
        const recipeIds = Array.from(likeButtons).map(btn => btn.dataset.recipeId);
        
        fetch('<?php echo SITE_URL; ?>/api/check_likes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                recipe_ids: recipeIds,
                csrf_token: '<?php echo generateCSRFToken(); ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                data.liked_recipes.forEach(recipeId => {
                    const button = document.querySelector(`.like-button[data-recipe-id="${recipeId}"]`);
                    if (button) {
                        button.classList.add('active');
                    }
                });
            }
        })
        .catch(error => console.error('Erro:', error));
    }
});
</script>

<style>
.like-button {
    transition: all 0.3s ease;
}

.like-button.active {
    background-color: #dc3545;
    color: white;
}

.like-button:hover {
    transform: scale(1.1);
}
</style> 