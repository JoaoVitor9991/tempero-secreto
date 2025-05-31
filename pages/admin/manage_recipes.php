<?php
// Verificar se é admin
if (!isAdmin()) {
    redirect(SITE_URL . '?route=home');
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setMessage('danger', 'Erro de segurança. Tente novamente.');
        redirect(SITE_URL . '?route=manage_recipes');
    }

    $recipe_id = (int)$_POST['recipe_id'];
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE recipes SET status = 'approved' WHERE id = ?");
                $stmt->execute([$recipe_id]);
                setMessage('success', 'Receita aprovada com sucesso!');
                break;

            case 'reject':
                $stmt = $pdo->prepare("UPDATE recipes SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$recipe_id]);
                setMessage('success', 'Receita rejeitada com sucesso!');
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM recipes WHERE id = ?");
                $stmt->execute([$recipe_id]);
                setMessage('success', 'Receita excluída com sucesso!');
                break;
        }
    } catch (PDOException $e) {
        setMessage('danger', 'Erro ao processar a ação: ' . $e->getMessage());
    }

    redirect(SITE_URL . '?route=manage_recipes');
}

// Parâmetros de filtro e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

// Construir a query base
$query = "SELECT r.*, c.name as category_name, u.name as username 
          FROM recipes r 
          LEFT JOIN categories c ON r.category_id = c.id 
          LEFT JOIN users u ON r.user_id = u.id 
          WHERE 1=1";

$params = [];

// Adicionar filtros
if ($status !== 'all') {
    $query .= " AND r.status = ?";
    $params[] = $status;
}

if ($search) {
    $query .= " AND (r.title LIKE ? OR r.description LIKE ? OR u.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Contar total de registros para paginação
$countStmt = $pdo->prepare(str_replace("r.*, c.name as category_name, u.name as username", "COUNT(*)", $query));
$countStmt->execute($params);
$total_recipes = $countStmt->fetchColumn();

// Calcular total de páginas
$total_pages = ceil($total_recipes / ITEMS_PER_PAGE);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Adicionar ordenação e paginação
$query .= " ORDER BY r.created_at DESC LIMIT " . (int)ITEMS_PER_PAGE . " OFFSET " . (int)$offset;

// Buscar receitas
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$recipes = $stmt->fetchAll();
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gerenciar Receitas</h2>
        <div>
            <a href="<?php echo SITE_URL; ?>?route=admin" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar ao Painel
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="route" value="manage_recipes">
                
                <div class="col-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Todos os status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Aprovadas</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejeitadas</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar por título, descrição ou autor..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Receitas -->
    <?php if (empty($recipes)): ?>
        <div class="alert alert-info">
            Nenhuma receita encontrada.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Título</th>
                        <th>Autor</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recipes as $recipe): ?>
                        <tr>
                            <td>
                                <a href="<?php echo SITE_URL; ?>?route=view_recipe&id=<?php echo $recipe['id']; ?>" 
                                   target="_blank">
                                    <?php echo htmlspecialchars($recipe['title']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($recipe['username']); ?></td>
                            <td><?php echo htmlspecialchars($recipe['category_name']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $recipe['status'] === 'approved' ? 'success' : 
                                        ($recipe['status'] === 'rejected' ? 'danger' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($recipe['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($recipe['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                    <?php if ($recipe['status'] === 'pending'): ?>
                                        <form method="POST" action="" class="d-inline" 
                                              onsubmit="return confirm('Tem certeza que deseja aprovar esta receita?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm" title="Aprovar receita">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="" class="d-inline" 
                                              onsubmit="return confirm('Tem certeza que deseja rejeitar esta receita?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Rejeitar receita">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="" class="d-inline" 
                                          onsubmit="return confirm('Tem certeza que deseja excluir esta receita?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="recipe_id" value="<?php echo $recipe['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Excluir receita">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?route=manage_recipes&page=<?php echo $page-1; ?><?php echo $status ? '&status='.$status : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                Anterior
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?route=manage_recipes&page=<?php echo $i; ?><?php echo $status ? '&status='.$status : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?route=manage_recipes&page=<?php echo $page+1; ?><?php echo $status ? '&status='.$status : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>">
                                Próxima
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div> 