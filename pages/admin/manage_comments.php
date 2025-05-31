<?php
// Verificar se é admin
if (!isAdmin()) {
    redirect(SITE_URL . '?route=home');
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        setMessage('danger', 'Erro de segurança. Tente novamente.');
        redirect(SITE_URL . '?route=manage_comments');
    }

    $comment_id = (int)$_POST['comment_id'];
    $action = $_POST['action'];

    try {
        switch ($action) {
            case 'approve':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'approved' WHERE id = ?");
                $stmt->execute([$comment_id]);
                setMessage('success', 'Comentário aprovado com sucesso!');
                break;

            case 'reject':
                $stmt = $pdo->prepare("UPDATE comments SET status = 'rejected' WHERE id = ?");
                $stmt->execute([$comment_id]);
                setMessage('success', 'Comentário rejeitado com sucesso!');
                break;

            case 'delete':
                $stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
                $stmt->execute([$comment_id]);
                setMessage('success', 'Comentário excluído com sucesso!');
                break;
        }
    } catch (PDOException $e) {
        setMessage('danger', 'Erro ao processar a ação: ' . $e->getMessage());
    }

    // Manter filtros e paginação após a ação
    $query_params = '';
    if (isset($_GET['status'])) $query_params .= '&status=' . urlencode($_GET['status']);
    if (isset($_GET['page'])) $query_params .= '&page=' . (int)$_GET['page'];

    redirect(SITE_URL . '?route=manage_comments' . $query_params);
}

// Parâmetros de filtro e paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = isset($_GET['status']) ? sanitize($_GET['status']) : 'pending'; // Padrão: comentários pendentes

// Construir a query base
$query = "SELECT c.*, u.name as username, r.title as recipe_title
          FROM comments c
          LEFT JOIN users u ON c.user_id = u.id
          LEFT JOIN recipes r ON c.recipe_id = r.id
          WHERE 1=1";

$params = [];

// Adicionar filtros
if ($status !== 'all') {
    $query .= " AND c.status = ?";
    $params[] = $status;
}

// Contar total de registros para paginação
$countStmt = $pdo->prepare(str_replace("c.*, u.name as username, r.title as recipe_title", "COUNT(*)", $query));
$countStmt->execute($params);
$total_comments = $countStmt->fetchColumn();

// Calcular total de páginas
$total_pages = ceil($total_comments / ITEMS_PER_PAGE);
$page = max(1, min($page, $total_pages));
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Adicionar ordenação e paginação
$query .= " ORDER BY c.created_at DESC LIMIT " . (int)ITEMS_PER_PAGE . " OFFSET " . (int)$offset;

// Buscar comentários
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$comments = $stmt->fetchAll();

?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Gerenciar Comentários</h2>
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
                <input type="hidden" name="route" value="manage_comments">
                
                <div class="col-md-4">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pendentes</option>
                        <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Aprovados</option>
                        <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejeitados</option>
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Todos os status</option>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Lista de Comentários -->
    <?php if (empty($comments)): ?>
        <div class="alert alert-info">
            Nenhum comentário encontrado com o status selecionado.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Comentário</th>
                        <th>Autor</th>
                        <th>Receita</th>
                        <th>Status</th>
                        <th>Data</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comments as $comment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($comment['content']); ?></td>
                            <td><?php echo htmlspecialchars($comment['username'] ?? 'Usuário Desconhecido'); ?></td>
                            <td>
                                <a href="<?php echo SITE_URL; ?>?route=view_recipe&id=<?php echo $comment['recipe_id']; ?>" 
                                   target="_blank">
                                    <?php echo htmlspecialchars($comment['recipe_title'] ?? 'Receita Desconhecida'); ?>
                                </a>
                            </td>
                             <td>
                                <span class="badge bg-<?php
                                    echo $comment['status'] === 'approved' ? 'success' :
                                         ($comment['status'] === 'rejected' ? 'danger' : 'warning');
                                ?>">
                                    <?php echo ucfirst($comment['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?></td>
                            <td>
                                <div class="btn-group">
                                     <?php if ($comment['status'] === 'pending'): ?>
                                        <form method="POST" action="" class="d-inline"
                                              onsubmit="return confirm('Tem certeza que deseja aprovar este comentário?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm" title="Aprovar comentário">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="" class="d-inline"
                                              onsubmit="return confirm('Tem certeza que deseja rejeitar este comentário?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm" title="Rejeitar comentário">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" action="" class="d-inline"
                                          onsubmit="return confirm('Tem certeza que deseja excluir este comentário permanentemente?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-danger btn-sm" title="Excluir comentário">
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
                            <a class="page-link" href="?route=manage_comments&page=<?php echo $page-1; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
                                Anterior
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?route=manage_comments&page=<?php echo $i; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?route=manage_comments&page=<?php echo $page+1; ?><?php echo $status ? '&status='.urlencode($status) : ''; ?>">
                                Próxima
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div> 