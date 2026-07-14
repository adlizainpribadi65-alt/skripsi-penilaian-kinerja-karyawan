<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

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

$kiosk_url = $protocol . "://" . $host . "/dss-saw/industrial/kiosk.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak QR Code Kiosk</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #333;
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden; /* Prevent scrolling */
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .print-container {
            width: 100%;
            max-width: 210mm;
            height: 95vh;
            padding: 30px;
            background: #fff;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            position: relative;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
        }

        .header-section {
            text-align: center;
            border-bottom: 2px solid #2c3e50;
            padding-bottom: 15px;
            margin-bottom: 15px;
            flex-shrink: 0;
        }

        .header-section h1 {
            font-size: 22pt;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 5px;
            letter-spacing: 1px;
        }

        .header-section p {
            font-size: 11pt;
            color: #7f8c8d;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .qr-section {
            text-align: center;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-box {
            display: inline-block;
            padding: 15px;
            border: 4px solid #2c3e50;
            border-radius: 15px;
            background: #fff;
        }

        .qr-instruction {
            margin-top: 15px;
            font-size: 14pt;
            font-weight: 600;
            color: #2c3e50;
        }

        .qr-url {
            margin-top: 10px;
            font-family: monospace;
            font-size: 10pt;
            color: #34495e;
            background: #ecf0f1;
            padding: 8px 15px;
            border-radius: 8px;
            display: inline-block;
        }

        .instructions-text {
            text-align: center;
            margin-top: 15px;
            flex-shrink: 0;
        }

        .instructions-text p {
            font-size: 10pt;
            color: #555;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.5;
        }

        .footer-section {
            margin-top: auto;
            text-align: center;
            border-top: 1px solid #bdc3c7;
            padding-top: 15px;
            font-size: 10pt;
            color: #7f8c8d;
            flex-shrink: 0;
        }

        .print-controls {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
            display: flex;
            gap: 15px;
        }

        .btn-action {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 6px rgba(79, 70, 229, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-action:hover {
            background: #4338ca;
            transform: translateY(-2px);
            color: white;
        }

        @media print {
            body {
                background: none;
                height: auto;
                display: block;
            }
            .print-container {
                margin: 0;
                box-shadow: none;
                width: 100%;
                height: 100%;
                padding: 10mm;
                page-break-inside: avoid;
            }
            .print-controls {
                display: none !important;
            }
            @page {
                size: A4 portrait;
                margin: 10mm;
            }
        }
    </style>
</head>
<body>

    <div class="print-controls">
        <button onclick="window.close()" class="btn-action" style="background: #e74c3c; box-shadow: 0 4px 6px rgba(231, 76, 60, 0.3);">
            <i class="fas fa-times"></i> Tutup
        </button>
        <button onclick="window.print()" class="btn-action">
            <i class="fas fa-print"></i> Cetak Dokumen
        </button>
    </div>

    <div class="print-container">
        <div class="header-section">
            <h1>REEGIOELLA ECOSYSTEM HUB</h1>
            <p>Sistem Pemantauan Industrial Kiosk</p>
        </div>

        <div class="qr-section">
            <div class="qr-box">
                <div id="qrcode"></div>
            </div>
            <div class="qr-instruction">
                Pindai kode QR ini untuk mengakses Kiosk Terminal
            </div>
            <div class="qr-url">
                <?= $kiosk_url ?>
            </div>
        </div>

        <div class="instructions-text">
            <p>
                Harap tempatkan kode QR ini di area yang mudah diakses oleh operator kiosk. Pastikan perangkat terhubung ke jaringan internal sebelum melakukan pemindaian.
            </p>
        </div>

        <div class="footer-section">
            Dokumen ini dicetak pada <?= date('d M Y H:i') ?> &bull; Reegioella Industrial System
        </div>
    </div>

    <!-- QR Code Library -->
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const qrcodeContainer = document.getElementById("qrcode");
            new QRCode(qrcodeContainer, {
                text: "<?= $kiosk_url ?>",
                width: 320,
                height: 320,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
            
            // Auto print prompt after a slight delay to ensure QR is rendered
            setTimeout(() => {
                // window.print();
            }, 1000);
        });
    </script>
</body>
</html>
