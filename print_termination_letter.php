<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

if (!isset($_GET['id'])) {
    die("ID Rekaman tidak ditemukan.");
}

$id = intval($_GET['id']);

$stmt = $pdo->prepare("
    SELECT t.*, 
           e.name as emp_name, 
           e.nik as emp_nik, 
           e.position as emp_position, 
           e.department as emp_department 
    FROM termination_records t 
    JOIN employees e ON t.employee_id = e.id 
    WHERE t.id = ?
");
$stmt->execute([$id]);
$record = $stmt->fetch();

if (!$record) {
    die("Rekaman tidak ditemukan.");
}

// Generate letter number
$letter_no = sprintf("%03d/HRD-PHK/RY/%s/%s", $record['id'], date('m', strtotime($record['created_at'])), date('Y', strtotime($record['created_at'])));

$type_labels = [
    'resign' => 'Pengunduran Diri (Resign)',
    'layoff' => 'Pemutusan Hubungan Kerja (PHK)',
    'contract_end' => 'Berakhirnya Masa Kontrak (PKWT)',
    'disciplinary' => 'Pelanggaran Disiplin / Tata Tertib',
    'mutual_agreement' => 'Kesepakatan Bersama',
];

$termination_type_str = $type_labels[$record['termination_type']] ?? 'Pemberhentian';

// Formatting dates
setlocale(LC_TIME, 'id_ID.utf8', 'id_ID', 'Indonesian');
$effective_date_fmt = strftime('%d %B %Y', strtotime($record['effective_date']));
$issue_date_fmt = strftime('%d %B %Y');
if ($record['effective_date'] === '0000-00-00') {
     $effective_date_fmt = date('d F Y', time());
} else {
     // fallback if strftime fails
     $effective_date_fmt = date('d F Y', strtotime($record['effective_date']));
}
$issue_date_fmt = date('d F Y');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Keputusan Pemberhentian - <?= htmlspecialchars($record['emp_name']) ?></title>
    <style>
        body {
            font-family: "Times New Roman", Times, serif;
            line-height: 1.5;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 0;
            font-size: 12pt;
        }
        .page {
            width: 210mm;
            min-height: 297mm;
            padding: 10mm;
            margin: 5mm auto;
            border: 1px #D3D3D3 solid;
            border-radius: 5px;
            background: white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            position: relative;
            font-size: 11pt; /* Sedikit dikecilkan agar teks tidak terlalu memakan tempat */
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            margin-bottom: 3mm;
            padding-bottom: 3mm;
            position: relative;
        }
        .header img {
            position: absolute;
            left: 10px;
            top: 0;
            width: 60px;
        }
        .header h1 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header h2 {
            margin: 0;
            font-size: 11pt;
        }
        .header p {
            margin: 0;
            font-size: 9pt;
        }
        .title {
            text-align: center;
            margin-bottom: 3mm;
        }
        .title h3 {
            margin: 0;
            font-size: 12pt;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .title p {
            margin: 0;
            font-size: 10pt;
        }
        .content {
            margin-bottom: 3mm;
            text-align: justify;
        }
        .content p {
            margin: 5px 0; /* Rapihkan margin paragraf */
        }
        .emp-details {
            margin-left: 5mm;
            margin-bottom: 3mm;
            margin-top: 3mm;
        }
        .emp-details table {
            width: 100%;
        }
        .emp-details td {
            padding: 1px 0;
            vertical-align: top;
        }
        .emp-details td:first-child {
            width: 130px;
        }
        .emp-details td:nth-child(2) {
            width: 20px;
            text-align: center;
        }
        .signatures {
            margin-top: 5mm;
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
        }
        .signatures td {
            text-align: center;
            vertical-align: bottom;
            height: 15mm; /* Dikurangi agar tidak terpotong */
        }
        .sign-area {
            display: inline-block;
            border-bottom: 1px solid #000;
            width: 180px;
            margin-bottom: 3px;
            margin-top: 15mm; /* Pengganti tag <br> yang tidak stabil */
        }
        @media print {
            body {
                background: none;
            }
            .page {
                margin: 0;
                border: initial;
                border-radius: initial;
                width: initial;
                min-height: initial;
                box-shadow: initial;
                background: initial;
                page-break-after: avoid; 
                page-break-inside: avoid;
                padding: 10mm; /* Minimalisir padding bawaan print */
            }
            .no-print {
                display: none !important;
            }
        }
        .btn-print {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-family: sans-serif;
            font-size: 14px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .btn-print:hover {
            background: #1d4ed8;
        }
    </style>
</head>
<body>

    <button class="btn-print no-print" onclick="window.print()">🖨️ Cetak / Simpan PDF</button>

    <div class="page">
        <!-- Letter Head -->
        <div class="header">
            <!-- Ensure logo path is correct relative to this file -->
            <img src="assets/img/logo_ry.png" alt="Reegioella Logo" style="filter: grayscale(100%);">
            <h1>REEGIOELLA THE SHOES</h1>
            <h2>HOME INDUSTRI SEPATU</h2>
            <p>Jl. Cimaung - Gn Puntang, Kabupaten Bandung</p>
        </div>

        <!-- Document Title -->
        <div class="title">
            <h3>SURAT KEPUTUSAN PEMBERHENTIAN</h3>
            <p>Nomor: <?= $letter_no ?></p>
        </div>

        <!-- Body -->
        <div class="content">
            <p>Yang bertanda tangan di bawah ini, mewakili Pimpinan Reegioella The Shoes, menerangkan dengan sesungguhnya bahwa:</p>
            
            <div class="emp-details">
                <table>
                    <tr>
                        <td>Nama Lengkap</td>
                        <td>:</td>
                        <td><strong><?= htmlspecialchars($record['emp_name']) ?></strong></td>
                    </tr>
                    <tr>
                        <td>Posisi / Pekerjaan</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($record['emp_position']) ?></td>
                    </tr>
                    <tr>
                        <td>Bagian</td>
                        <td>:</td>
                        <td><?= htmlspecialchars($record['emp_department']) ?></td>
                    </tr>
                </table>
            </div>

            <p>Berdasarkan hasil evaluasi manajemen serta kebijakan usaha, Pimpinan memutuskan untuk melakukan <strong><?= $termination_type_str ?></strong> terhadap Saudara/i terhitung efektif sejak tanggal <strong><?= $effective_date_fmt ?></strong>.</p>
            
            <p>Keputusan ini diambil berdasarkan pertimbangan dan alasan sebagai berikut:</p>
            <p style="margin-left: 5mm; font-style: italic;">"<?= nl2br(htmlspecialchars($record['reason'])) ?>"</p>

            <?php if ($record['severance_months'] > 0): ?>
            <p>Sebagai bentuk penyelesaian hubungan kerja ini, pihak Reegioella The Shoes akan memberikan hak-hak Saudara/i berupa uang kebijaksanaan/pesangon setara dengan <strong><?= $record['severance_months'] ?> bulan gaji</strong> yang proses penyelesaiannya akan dipandu oleh bagian Administrasi/Keuangan.</p>
            <?php else: ?>
            <p>Proses penyelesaian administrasi dan perhitungan hak (jika ada) akan diselesaikan oleh bagian Administrasi/Keuangan berdasarkan kesepakatan.</p>
            <?php endif; ?>

            <p>Selanjutnya, Saudara/i diwajibkan untuk mengembalikan seluruh fasilitas/aset kepunyaan tempat kerja serta menyelesaikan tanggungan maupun serah terima tugas paling lambat pada hari terakhir bekerja.</p>

            <p>Pimpinan mengucapkan terima kasih atas kontribusi serta tenaga yang telah Saudara/i berikan selama bekerja di Reegioella The Shoes.</p>

            <p>Demikian Surat Keputusan ini dibuat untuk dapat diketahui dan dipergunakan sebagaimana mestinya.</p>
        </div>

        <!-- Signatures -->
        <table class="signatures">
            <tr>
                <td>
                    Dibuat dan Disetujui Oleh,<br>
                    <strong>Pimpinan Reegioella The Shoes</strong>
                    
                    <div class="sign-area"></div><br>
                    <strong><?= htmlspecialchars($record['approved_by'] ?: 'Pimpinan / Owner') ?></strong><br>
                    <small>Tanggal: <?= $issue_date_fmt ?></small>
                </td>
                <td>
                    Menerima dan Mengetahui,<br>
                    <strong>Karyawan Bersangkutan</strong>
                    
                    <div class="sign-area"></div><br>
                    <strong><?= htmlspecialchars($record['emp_name']) ?></strong><br>
                    <small>Tanggal: .......................................</small>
                </td>
            </tr>
        </table>
    </div>

    <!-- Auto-print on load for convenience, but wrapped in a timeout -->
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
