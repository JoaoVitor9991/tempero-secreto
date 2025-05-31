<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validações
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'O nome é obrigatório.';
    }
    
    if (empty($email)) {
        $errors[] = 'O email é obrigatório.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email inválido.';
    }
    
    if (empty($password)) {
        $errors[] = 'A senha é obrigatória.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'A senha deve ter pelo menos 6 caracteres.';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'As senhas não conferem.';
    }
    
    // Verificar se o email já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'Este email já está cadastrado.';
    }
    
    if (empty($errors)) {
        // Criar usuário
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role) 
            VALUES (?, ?, ?, 'user')
        ");
        
        if ($stmt->execute([$name, $email, $hashed_password])) {
            setMessage('success', 'Cadastro realizado com sucesso! Faça login para continuar.');
            redirect(SITE_URL . '?route=login');
        } else {
            setMessage('danger', 'Erro ao criar conta. Tente novamente.');
        }
    } else {
        foreach ($errors as $error) {
            setMessage('danger', $error);
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white text-center">
                <h2 class="mb-0">Criar Conta</h2>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Nome completo</label>
                        <input type="text" class="form-control" id="name" name="name"
                               value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Senha</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="form-text">A senha deve ter pelo menos 6 caracteres.</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmar senha</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Registrar</button>
                    </div>
                </form>
                <div class="text-center mt-3">
                    <p>Já tem uma conta? <a href="<?php echo SITE_URL; ?>?route=login">Entrar</a></p>
                </div>
            </div>
        </div>
    </div>
</div> 