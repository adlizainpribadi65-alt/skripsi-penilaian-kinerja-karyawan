<?php
session_start();
require_once 'includes/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (!empty($username) && !empty($password)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Akses ditolak. Verifikasi identitas gagal.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reegioella Hub - Integrated DSS</title>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern Design System -->
    <link rel="stylesheet" href="assets/css/modern.css">
    <!-- Theme Initializer - Prevents flash of unstyled theme -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-glass-card {
            width: 100%;
            max-width: 450px;
            padding: 60px 40px;
            text-align: center;
            animation: fadeIn 0.8s ease-out;
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--indigo) 0%, var(--purple) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 2rem;
            color: white;
            box-shadow: 0 0 30px var(--primary-glow);
        }

        .error-overlay {
            background: rgba(244, 63, 94, 0.1);
            border: 1px solid rgba(244, 63, 94, 0.2);
            color: #fb7185;
            padding: 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            margin-bottom: 24px;
        }
    </style>
</head>

<body>
    <!-- Floating Glass Orbs Background -->
    <div class="orb-container">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
        <div class="orb orb-4"></div>
    </div>

    <div class="glass login-glass-card">
        <div class="login-header">
            <div class="login-icon">
                <i class="fas fa-layer-group"></i>
            </div>
            <h1 class="text-white fw-bold display-6 brand-font">REEGIOELLA</h1>
            <p class="text-muted small text-uppercase tracking-widest" style="letter-spacing: 0.25em;">Integrated DSS
                SAW Hub</p>
        </div>

        <?php if ($error): ?>
            <div class="error-overlay">
                <i class="fas fa-circle-exclamation me-2"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-4 text-start">
                <label class="form-label text-muted small fw-bold text-uppercase ms-1">Nama Pengguna</label>
                <input type="text" name="username" class="form-control-glass w-100" placeholder="admin" required
                    autofocus>
            </div>
            <div class="mb-5 text-start">
                <label class="form-label text-muted small fw-bold text-uppercase ms-1">Kunci Keamanan</label>
                <input type="password" name="password" class="form-control-glass w-100" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn-premium w-100 justify-content-center py-3">
                <i class="fas fa-right-to-bracket me-2"></i> Inisialisasi Sesi
            </button>
        </form>

        <div class="mt-5 text-muted small opacity-50 tracking-widest uppercase">
            <i class="fas fa-shield-halved me-2"></i> Lingkungan Terenkripsi
        </div>
    </div>

</body>

</html>