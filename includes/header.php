<?php
// Dynamic path for CSS
$base_url = (basename(dirname($_SERVER['PHP_SELF'])) == 'inventori') ? '../dss-saw/' : '';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reegioella Hub - Integrated DSS</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern Design System -->
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/modern.css">
    <!-- Sidebar Styles (edit file ini untuk ubah tampilan sidebar) -->
    <link rel="stylesheet" href="<?= $base_url ?>assets/css/sidebar.css">

    <!-- Theme Initializer - Prevents flash of unstyled theme -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', savedTheme);
        })();
    </script>

    <!-- Meta/Theming -->
    <meta name="theme-color" content="#4f46e5">
    <?= $extra_head ?? '' ?>
</head>

<body>
    <?php include $base_url . 'includes/mobile_nav.php'; ?>
    <!-- Floating background elements removed for stable UI -->
    <?php // Removed app-container here as it is handled by individual page layouts ?>