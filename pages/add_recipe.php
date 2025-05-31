<?php
// Verificar se o usuário está logado
if (!isLoggedIn()) {
    redirect(SITE_URL . '?route=login');
}

// Buscar categorias para o select
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        setMessage('danger', 'Erro de segurança. Tente novamente.');
        redirect(SITE_URL . '?route=add_recipe');
    }

    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $ingredients = sanitize($_POST['ingredients']);
    $instructions = sanitize($_POST['instructions']);
    $category_id = (int)$_POST['category_id'];
    
    $errors = [];

    // Validações
    if (empty($title)) {
        $errors[] = 'O título é obrigatório.';
    }
    if (empty($description)) {
        $errors[] = 'A descrição é obrigatória.';
    }
    if (empty($ingredients)) {
        $errors[] = 'Os ingredientes são obrigatórios.';
    }
    if (empty($instructions)) {
        $errors[] = 'O modo de preparo é obrigatório.';
    }
    if (empty($category_id)) {
        $errors[] = 'A categoria é obrigatória.';
    }

    // Processar upload da imagem
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['image'];
        
        // Validar tipo do arquivo
        if (!in_array($file['type'], ALLOWED_IMAGE_TYPES)) {
            $errors[] = 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.';
        }
        
        // Validar tamanho
        if ($file['size'] > MAX_FILE_SIZE) {
            $errors[] = 'A imagem deve ter no máximo 5MB.';
        }

        if (empty($errors)) {
            // Criar diretório de uploads se não existir
            if (!file_exists(UPLOAD_DIR)) {
                mkdir(UPLOAD_DIR, 0777, true);
            }

            // Gerar nome único para o arquivo
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $extension;
            $image_path = 'uploads/' . $filename;

            // Mover arquivo
            if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
                $errors[] = 'Erro ao fazer upload da imagem.';
            }
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO recipes (user_id, category_id, title, description, ingredients, instructions, image, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            if ($stmt->execute([
                $_SESSION['user_id'],
                $category_id,
                $title,
                $description,
                $ingredients,
                $instructions,
                $image_path
            ])) {
                setMessage('success', 'Receita cadastrada com sucesso! Aguarde a aprovação do administrador.');
                redirect(SITE_URL . '?route=home');
            } else {
                $errors[] = 'Erro ao cadastrar receita. Tente novamente.';
            }
        } catch (PDOException $e) {
            $errors[] = 'Erro ao cadastrar receita: ' . $e->getMessage();
        }
    }

    if (!empty($errors)) {
        foreach ($errors as $error) {
            setMessage('danger', $error);
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h2 class="mb-0">Nova Receita</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label for="title" class="form-label">Título da Receita</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" 
                               required>
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoria</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Selecione uma categoria</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>"
                                    <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Descrição</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="ingredients" class="form-label">Ingredientes</label>
                        <textarea class="form-control" id="ingredients" name="ingredients" rows="5" required><?php echo isset($_POST['ingredients']) ? htmlspecialchars($_POST['ingredients']) : ''; ?></textarea>
                        <div class="form-text">Liste os ingredientes, um por linha.</div>
                    </div>

                    <div class="mb-3">
                        <label for="instructions" class="form-label">Modo de Preparo</label>
                        <textarea class="form-control" id="instructions" name="instructions" rows="8" required><?php echo isset($_POST['instructions']) ? htmlspecialchars($_POST['instructions']) : ''; ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="image" class="form-label">Imagem da Receita</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Formatos aceitos: JPG, PNG, GIF. Máximo: 5MB</div>
                        <div id="imagePreview" class="mt-2" style="display: none;">
                            <img src="" alt="Preview" class="img-thumbnail" style="max-height: 200px;">
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Cadastrar Receita</button>
                        <a href="<?php echo SITE_URL; ?>?route=home" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Preview da imagem
document.getElementById('image').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    const file = e.target.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.querySelector('img').src = e.target.result;
            preview.style.display = 'block';
        }
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
    }
});
</script> 