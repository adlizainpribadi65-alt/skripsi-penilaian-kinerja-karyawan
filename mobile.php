<?php
require_once 'includes/db.php';

// Forced Wi-Fi IP for Mobile Connectivity (Prevents 'localhost' issues)
$server_ip = '192.168.110.197'; 
$mobile_url = "http://$server_ip:8080/dss-saw/mobile.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instal Aplikasi Mobile - Reegioella Hub</title>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern Design System -->
    <link rel="stylesheet" href="assets/css/modern.css">
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }

        .download-card {
            width: 100%;
            max-width: 500px;
            padding: 60px 40px;
            text-align: center;
            animation: entrance-fade 0.8s cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .qr-wrapper {
            width: 240px;
            height: 240px;
            background: white;
            border-radius: 24px;
            margin: 40px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 40px var(--primary-glow);
            padding: 12px;
            border: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .qr-wrapper:hover {
            transform: scale(1.05);
            box-shadow: 0 0 60px var(--primary-glow);
        }

        .url-box {
            background: rgba(255,255,255,0.03);
            border-radius: 16px;
            padding: 20px;
            margin-top: 35px;
            border: 1px dashed var(--primary-glow);
        }

        .nav-back-link {
            position: fixed;
            top: 30px;
            left: 30px;
            z-index: 10;
        }
    </style>
</head>
<body>

    <div class="nav-back-link d-print-none">
        <a href="dashboard.php" class="text-muted small fw-bold text-uppercase tracking-widest">
            <i class="fas fa-arrow-left me-2"></i> Dashboard
        </a>
    </div>

    <!-- Background Orbs -->
    <div class="orb-container">
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
    </div>

    <div class="glass download-card">
        <div class="mb-5">
            <div class="glass-item shadow-glow ms-auto me-auto mb-4 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; border-radius: 20px; border-color: var(--primary);">
                <i class="fas fa-mobile-screen text-primary fs-1 animate-pulse"></i>
            </div>
            <h1 class="text-white fw-bold display-6 brand-font">MOBILE ACCESS</h1>
            <p class="text-muted small text-uppercase tracking-widest mt-2" style="letter-spacing: 0.25em;">Instalasi Kios Terminal</p>
        </div>

        <!-- Offline-Compatible QR Code Generator -->
        <div class="qr-wrapper" id="qrcode">
            <div class="text-dark small"><i class="fas fa-circle-notch fa-spin me-2"></i>Inisialisasi QR...</div>
        </div>

        <div class="url-box">
            <div class="text-muted tiny fw-bold text-uppercase mb-2 tracking-widest opacity-50">Manual Network URL</div>
            <div class="text-primary fw-bold fs-5 font-monospace"><?= $mobile_url ?></div>
        </div>

        <button id="btnInstall" class="btn-premium w-100 py-3 mt-4 fs-6 justify-content-center">
            <i class="fas fa-cloud-arrow-down me-2"></i> PASANG APLIKASI SEKARANG
        </button>

        <div id="install-instruction" class="mt-4" style="display: none;">
            <div class="glass-pane p-4 text-start animate-fadeIn" style="border-left: 4px solid var(--secondary);">
                <div class="text-white fw-bold mb-2"><i class="fas fa-circle-info me-2 text-secondary"></i> Panduan Instalasi Manual</div>
                <div class="text-muted small mb-3">Jika tombol integrasi otomatis tidak merespon, harap ikuti langkah berikut pada browser ponsel Anda:</div>
                <div class="d-grid gap-2">
                    <div class="glass-item p-2 px-3 small"><span class="badge-glass badge-indigo me-2">Android</span> Klik <i class="fas fa-ellipsis-v mx-1 opacity-50"></i> lalu <strong>"Instal Aplikasi"</strong></div>
                    <div class="glass-item p-2 px-3 small"><span class="badge-glass badge-indigo me-2">iPhone</span> Klik <i class="fas fa-share-nodes mx-1 opacity-50"></i> lalu <strong>"Add to Home Screen"</strong></div>
                </div>
            </div>
        </div>

        <div id="installed-msg" class="badge-glass badge-emerald w-100 py-3 mt-4 d-flex justify-content-center align-items-center gap-2" style="display: none !important;">
            <i class="fas fa-check-circle fs-5"></i> APLIKASI TELAH TERINSTAL
        </div>

        <div class="mt-5 opacity-50 text-muted tiny tracking-widest uppercase">
            <i class="fas fa-shield-halved me-2"></i> Reegioella Secure Tunnel
        </div>
    </div>

    <!-- QR Code Library for OFFLINE generation -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        // Generate QR Code locally
        setTimeout(() => {
            const qrcodeContainer = document.getElementById("qrcode");
            qrcodeContainer.innerHTML = ""; 
            new QRCode(qrcodeContainer, {
                text: "<?= $mobile_url ?>",
                width: 210,
                height: 210,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
        }, 500);

        let deferredPrompt;
        const btnInstall = document.getElementById('btnInstall');
        const instruction = document.getElementById('install-instruction');

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
        });

        btnInstall.addEventListener('click', async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                deferredPrompt = null;
            } else {
                instruction.style.display = 'block';
                btnInstall.style.opacity = '0.5';
                btnInstall.innerHTML = '<i class="fas fa-info-circle me-2"></i> LIHAT PANDUAN DI BAWAH';
            }
        });

        window.addEventListener('appinstalled', () => {
            document.getElementById('installed-msg').setAttribute('style', 'display: flex !important');
            btnInstall.style.display = 'none';
        });
    </script>
</body>
</html>
