<?php
/**
 * Administrator Authentication Page
 * Smart Egg Incubator IoT System
 */

declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Redirect to dashboard if already logged in
if (isAuthenticated()) {
    header('Location: dashboard.php');
    exit;
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $errorMessage = 'Silakan masukkan username dan password.';
    } else {
        $pdo = getDbConnection();
        $stmt = $pdo->prepare('SELECT id, username, password_hash, full_name, role FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, (string)$user['password_hash'])) {
            // Update last_login timestamp
            $upd = $pdo->prepare('UPDATE users SET last_login = NOW() WHERE id = :id');
            $upd->execute([':id' => $user['id']]);

            // Set session variables
            $_SESSION['user_id']   = (int)$user['id'];
            $_SESSION['username']  = (string)$user['username'];
            $_SESSION['full_name'] = (string)$user['full_name'];
            $_SESSION['role']      = (string)$user['role'];

            header('Location: dashboard.php');
            exit;
        } else {
            $errorMessage = 'Username atau password yang Anda masukkan salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Administrator | Smart Egg Incubator</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-surface d-flex align-items-center justify-content-center min-vh-100 py-4">

<div class="auth-card p-4 p-sm-5 shadow-sm">
    <div class="text-center mb-4">
        <div class="brand-icon d-inline-flex align-items-center justify-content-center bg-amber-600 text-white rounded mb-2" style="width: 48px; height: 48px; font-size: 1.5rem;">
            <i class="bi bi-egg-fill"></i>
        </div>
        <h1 class="h4 fw-bold mb-1">Masuk Petugas</h1>
        <p class="text-slate-500 small mb-0">Smart Egg Incubator IoT System</p>
    </div>

    <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger py-2 px-3 small d-flex align-items-center gap-2 mb-3" role="alert">
            <i class="bi bi-exclamation-triangle-fill flex-shrink-0"></i>
            <div><?= htmlspecialchars($errorMessage) ?></div>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>
        <div class="mb-3">
            <label for="username" class="form-label small fw-semibold text-slate-700">Username</label>
            <div class="input-group">
                <span class="input-group-text bg-white text-slate-400 border-end-0"><i class="bi bi-person"></i></span>
                <input type="text" class="form-control border-start-0" id="username" name="username" placeholder="Masukkan username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            </div>
        </div>

        <div class="mb-4">
            <label for="password" class="form-label small fw-semibold text-slate-700">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-white text-slate-400 border-end-0"><i class="bi bi-lock"></i></span>
                <input type="password" class="form-control border-start-0" id="password" name="password" placeholder="Masukkan password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-warning w-100 fw-semibold text-white d-flex align-items-center justify-content-center gap-2 py-2" style="background-color: #d97706; border-color: #b45309;">
            <i class="bi bi-box-arrow-in-right"></i>
            <span>Masuk ke Dashboard</span>
        </button>
    </form>

    <div class="mt-4 pt-3 border-top text-center text-slate-400 small">
        Kredensial Default Praktikum:<br>
        <code>username: admin | password: admin123</code>
    </div>
</div>

</body>
</html>

