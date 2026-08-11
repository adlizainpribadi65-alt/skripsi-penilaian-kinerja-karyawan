<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
require_once 'includes/db.php';
include 'includes/header.php';

// Generate dynamic Kiosk URL accessible from phones
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];

// If accessed via localhost, replace it with the actual network IP
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    $ip = getHostByName(getHostName());
    // Fallback if getHostByName returns local loopback
    if ($ip === '127.0.0.1' || $ip === '::1' || $ip === 'localhost') {
        $ip = '192.168.1.52'; 
    }
    
    // Preserve the port if there is one
    if (strpos($host, ':') !== false) {
        $parts = explode(':', $host);
        $host = $ip . ':' . $parts[1];
    } else {
        $host = $ip;
    }
}

$base_dir = str_replace('\\', '/', dirname($_SERVER['PHP_SELF']));
$base_dir = rtrim($base_dir, '/');
$kiosk_url = $protocol . "://" . $host . $base_dir . "/industrial/kiosk.php";
?>

<div class="app-container" style="height: 100vh; overflow: hidden;">
    <?php include 'includes/sidebar.php'; ?>

    <main class="main-content d-flex flex-column" style="height: 100vh; overflow: hidden; padding-bottom: 0;">
        <div class="content-header mb-3 flex-shrink-0">
            <div class="d-flex align-items-center gap-3">
                <div class="header-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-qrcode"></i>
                </div>
                <div>
                    <h2 class="h3 mb-1 brand-font tracking-tight">QR Code Kiosk Terminal</h2>
                    <p class="text-muted small mb-0">Pindai kode QR untuk mengakses Kiosk Terminal</p>
                </div>
            </div>
        </div>

        <div class="w-100 flex-grow-1 d-flex align-items-center justify-content-center mb-4" style="min-height: 0; overflow-y: auto;">
            <div style="width: 100%; max-width: 480px; padding: 15px;">
                <div class="glass-card p-4 p-md-5 text-center">
                    <div class="mb-4">
                        <i class="fas fa-display fs-1 text-primary mb-3"></i>
                        <h4 class="text-white mb-1">Akses Kiosk Terminal</h4>
                        <p class="text-muted small">Arahkan kamera ponsel Anda ke kode QR di bawah ini</p>
                    </div>

                    <div class="bg-white p-3 rounded-4 d-inline-block mx-auto mb-4" style="border: 4px solid var(--primary);">
                        <div id="qrcode"></div>
                    </div>

                    <div class="glass-pane p-3 rounded-3 mb-4 text-start">
                        <div class="text-muted tiny text-uppercase tracking-widest mb-1 opacity-75">URL Langsung</div>
                        <div class="text-primary font-monospace fw-bold" style="word-break: break-all; font-size: 0.85rem;">
                            <?= $kiosk_url ?>
                        </div>
                    </div>

                    <a href="kiosk_qr_pdf.php" target="_blank" class="btn-premium w-100 justify-content-center">
                        <i class="fas fa-file-pdf me-2"></i> Cetak PDF QR Kiosk
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- QR Code Library -->
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const qrcodeContainer = document.getElementById("qrcode");
        new QRCode(qrcodeContainer, {
            text: "<?= $kiosk_url ?>",
            width: 250,
            height: 250,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
