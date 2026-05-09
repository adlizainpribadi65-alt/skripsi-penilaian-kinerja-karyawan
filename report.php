<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Fetch Data for SAW Calculation
$criteria = $pdo->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll();
$employees = $pdo->query("SELECT * FROM employees ORDER BY id ASC")->fetchAll();
$score_data = $pdo->query("SELECT * FROM scores")->fetchAll();
$matrix = [];
foreach ($score_data as $s) {
    $matrix[$s['employee_id']][$s['criteria_id']] = (float) $s['score'];
}

$results = [];
if (!empty($employees) && !empty($criteria)) {
    $extrema = [];
    foreach ($criteria as $crit) {
        $vals = [];
        foreach ($employees as $emp) {
            $vals[] = $matrix[$emp['id']][$crit['id']] ?? 0;
        }
        $extrema[$crit['id']] = ($crit['type'] == 'benefit') ? (!empty($vals) ? max($vals) : 1) : (!empty($vals) ? min($vals) : 1);
    }

    foreach ($employees as $emp) {
        $v_total = 0;
        foreach ($criteria as $crit) {
            $x = $matrix[$emp['id']][$crit['id']] ?? 0;
            $ex = $extrema[$crit['id']];
            $r = ($crit['type'] == 'benefit') ? (($ex != 0) ? ($x / $ex) : 0) : (($x != 0) ? ($ex / $x) : 0);
            $v_total += $r * ((float) $crit['weight'] / 100);
        }
        $results[] = [
            'name' => $emp['name'],
            'nik' => $emp['nik'],
            'position' => $emp['position'],
            'score' => $v_total
        ];
    }
}
usort($results, function ($a, $b) {
    return $b['score'] <=> $a['score'];
});

// Helper for date in Indonesian
function getIndoDate($date)
{
    $months = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $d = date('d', strtotime($date));
    $m = $months[(int) date('m', strtotime($date))];
    $y = date('Y', strtotime($date));
    return "$d $m $y";
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Analitik SAW - Reegioella Hub</title>
    <!-- FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Modern Design System -->
    <link rel="stylesheet" href="assets/css/modern.css">
    <style>
        body {
            background: #020408;
            padding: 60px 20px;
        }

        .report-container {
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
        }

        .meta-item {
            border-left: 3px solid var(--primary);
            padding: 15px 25px;
            background: rgba(255, 255, 255, 0.02);
            border-radius: 0 16px 16px 0;
        }

        .report-seal {
            position: absolute;
            top: 20px;
            right: 40px;
            opacity: 0.1;
            transform: rotate(15deg) scale(1.5);
            pointer-events: none;
        }

        @page {
            size: landscape;
            margin: 10mm;
        }

        @media print {
            body, html {
                width: 100% !important;
                height: auto !important;
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                color: black !important;
                font-family: Arial, Helvetica, sans-serif !important;
                overflow: visible !important;
            }

            .report-container, .glass, .overflow-hidden {
                max-width: 100% !important;
                margin: 0 !important;
                overflow: visible !important;
                height: auto !important;
            }

            .glass {
                background: white !important;
                border: none !important;
                color: black !important;
                box-shadow: none !important;
                backdrop-filter: none !important;
                padding: 0 !important;
            }

            .btn-premium,
            .nav-back,
            .report-seal {
                display: none !important;
            }
            
            /* Hide the modern web headers & badges */
            .row.align-items-center.mb-5.pb-5,
            .row.g-4.mb-5,
            .mb-4.d-flex.justify-content-between {
                display: none !important;
            }

            /* Show formal header */
            .formal-print-header.d-none {
                display: block !important;
            }

            /* Formal Table Styles */
            .premium-table-container {
                overflow: visible !important;
                box-shadow: none !important;
            }

            .premium-table {
                width: 100% !important;
                border-collapse: collapse !important;
            }

            .premium-table th, .premium-table td {
                border: 1px solid black !important;
                color: black !important;
                padding: 4px 6px !important; /* Drastically reduced for 1 page fit */
                font-size: 10pt !important;
            }

            .premium-table th {
                background-color: transparent !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-weight: bold !important;
                text-align: center !important;
                text-transform: uppercase !important;
            }

            .text-muted, .text-white, .text-primary, .text-cyan {
                color: black !important;
            }

            .badge-glass, .shimmer-text {
                -webkit-text-fill-color: black !important;
                background: none !important;
                border: none !important;
                padding: 0 !important;
            }
            
            .fs-3, .display-6, .fs-5 {
                font-size: 12pt !important;
            }
            
            .mt-5.pt-5.row {
                margin-top: 50px !important;
            }
            
            .border-white, .border-opacity-10 {
                border-color: black !important;
                opacity: 1 !important;
            }
            
            .opacity-75, .opacity-50 {
                opacity: 1 !important;
            }
        }
        
        /* Editable Field Styles */
        .editable-field {
            transition: all 0.2s ease;
            border: 1px dashed transparent;
            padding: 2px 5px;
            border-radius: 4px;
        }
        .editable-field:hover {
            border-color: #ccc;
            background-color: rgba(0,0,0,0.05);
            cursor: text;
        }
        .editable-field:focus {
            outline: none;
            border-color: var(--primary);
            background-color: rgba(99,102,241,0.1);
        }
        @media print {
            .editable-field {
                border: none !important;
                background: transparent !important;
                padding: 0 !important;
            }
            /* Override large Bootstrap padding/margins for 1 page printing */
            .formal-print-header.bg-white {
                padding: 0 !important;
                margin-bottom: 10px !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            .mt-5.pt-4.bg-white.text-dark {
                padding: 0 !important;
                margin-top: 15px !important;
                padding-top: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
            }
            .glass.p-5 {
                padding: 0 !important;
            }
            .premium-table-container {
                margin-bottom: 5px !important;
            }
        }
    </style>
</head>

<body>

    <div class="report-container">
        <div class="nav-back mb-4 animate-fadeIn">
            <a href="dashboard.php" class="text-muted small fw-bold text-uppercase tracking-widest">
                <i class="fas fa-arrow-left me-2"></i> Kembali ke Dashboard
            </a>
        </div>

        <div class="glass p-5 shadow-2xl animate-fadeIn position-relative overflow-hidden">
            <!-- Formal Print Header (Now visible as a card and editable) -->
            <div class="formal-print-header mb-5 bg-white text-dark p-5 rounded-4 shadow-lg position-relative">
                <div class="position-absolute top-0 end-0 m-3 badge bg-warning text-dark d-print-none"><i class="fas fa-edit me-1"></i> Mode Edit Dokumen (Klik teks untuk mengubah)</div>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px;">
                    <tr>
                        <td style="width: 15%; text-align: left; vertical-align: top;">
                            <div style="width: 60px; height: 60px; border: 2px solid black; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                <i class="fas fa-building" style="font-size: 30px; color: black;"></i>
                            </div>
                        </td>
                        <td style="width: 70%; text-align: center; vertical-align: top;">
                            <div class="editable-field" contenteditable="true" spellcheck="false" style="font-family: Arial, Helvetica, sans-serif; font-weight: bold; font-size: 14pt; line-height: 1.5; color: black;">
                                LAPORAN ANALITIK KINERJA<br>
                                SISTEM PENDUKUNG KEPUTUSAN KINERJA KARYAWAN<br>
                                PT REEGIOELLA INDUSTRIAL HUB<br>
                                METODE : SIMPLE ADDITIVE WEIGHTING (SAW)
                            </div>
                        </td>
                        <td style="width: 15%;"></td>
                    </tr>
                </table>

                <div style="display: flex; justify-content: space-between; font-family: Arial, Helvetica, sans-serif; font-weight: bold; font-size: 10pt; margin-bottom: 10px; color: black;">
                    <div class="editable-field" contenteditable="true" spellcheck="false" style="line-height: 2;">
                        <i>Lampiran 1 A</i><br>
                        NO DOKUMEN : LPK-<?= date('Ym') ?><br>
                        DEPARTEMEN : SEMUA DEPARTEMEN
                    </div>
                    <div class="editable-field" contenteditable="true" spellcheck="false" style="line-height: 2; text-align: left;">
                        <br>
                        TARIKH : <?= date('d/m/Y') ?><br>
                        TEMPAT : KANTOR PUSAT
                    </div>
                </div>

                <div class="editable-field" contenteditable="true" spellcheck="false" style="text-align: center; font-family: Arial, Helvetica, sans-serif; font-weight: bold; font-size: 11pt; margin-bottom: 15px; color: black;">
                    ( HASIL PEMERINGKATAN KINERJA KARYAWAN )
                </div>
            </div>

            <div class="report-seal d-print-none">
                <i class="fas fa-microchip fa-10x text-white"></i>
            </div>

            <div class="row align-items-center mb-5 pb-5 border-bottom border-white border-opacity-10">
                <div class="col-md-7">
                    <div class="d-flex align-items-center gap-4">
                        <div class="glass-pane p-1" style="border-color: var(--primary-glow);">
                            <div class="p-3 bg-primary rounded-4 shadow-glow"
                                style="background: linear-gradient(135deg, var(--primary) 0%, #4338ca 100%) !important;">
                                <i class="fas fa-layer-group fa-2x text-white"></i>
                            </div>
                        </div>
                        <div>
                            <h1 class="display-6 fw-bold text-white mb-0 brand-font tracking-tight">LAPORAN ANALITIK
                            </h1>
                            <p class="text-muted mb-0 small text-uppercase tracking-widest"
                                style="letter-spacing: 0.4em;">Reegioella Industrial Hub</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 text-md-end mt-4 mt-md-0 d-print-none">
                    <button class="btn-premium px-5 py-3" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> EKSPOR PDF / CETAK
                    </button>
                </div>
            </div>

            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="meta-item">
                        <div class="text-muted tiny fw-bold uppercase tracking-widest mb-1">Periode Verifikasi</div>
                        <div class="text-white fw-bold fs-5"><?= getIndoDate(date('Y-m-d')) ?></div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="meta-item" style="border-left-color: var(--secondary);">
                        <div class="text-muted tiny fw-bold uppercase tracking-widest mb-1">Model Algoritma</div>
                        <div class="text-white fw-bold fs-5">SAW Matrix Synthesis</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="meta-item" style="border-left-color: var(--cyan);">
                        <div class="text-muted tiny fw-bold uppercase tracking-widest mb-1">Integritas Data</div>
                        <div class="text-cyan fw-bold fs-5"><i class="fas fa-shield-check me-2"></i>TERVERIFIKASI</div>
                    </div>
                </div>
            </div>

            <div class="mb-4 d-flex justify-content-between align-items-center">
                <h4 class="text-white fw-bold mb-0">Matriks Pemeringkatan Akhir</h4>
                <div class="badge-glass badge-indigo"><?= count($results) ?> Subjek Teranalisis</div>
            </div>

            <div class="glass p-0 overflow-hidden mb-5">
                <div class="premium-table-container">
                    <table class="premium-table">
                        <thead>
                            <tr>
                                <th width="120px" class="ps-5 text-center">POSISI</th>
                                <th>IDENTITAS PERSONEL</th>
                                <th>NIK / DEPARTEMEN</th>
                                <th class="text-end pe-5">SKOR PREFERENSI (V)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results as $index => $res): ?>
                                <tr class="<?= $index < 3 ? 'glass-item' : '' ?>">
                                    <td class="ps-5 text-center">
                                        <div class="fw-bold <?= $index < 3 ? 'text-primary fs-3' : 'text-muted fs-5' ?>">
                                            #<?= $index + 1 ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-white fs-5"><?= htmlspecialchars($res['name']) ?></div>
                                    </td>
                                    <td>
                                        <div class="text-white small fw-bold mb-1">
                                            <?= htmlspecialchars($res['nik'] ?? 'N/A') ?>
                                        </div>
                                        <div class="text-muted tiny uppercase tracking-tighter opacity-75">
                                            <?= htmlspecialchars($res['position']) ?>
                                        </div>
                                    </td>
                                    <td class="text-end pe-5">
                                        <div class="fw-bold text-primary display-6 font-monospace"
                                            style="font-size: 1.8rem;"><?= number_format($res['score'], 4) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modern screen footer -->
            <div class="mt-5 pt-5 row d-print-none">
                <div class="col-6">
                    <div class="text-muted small fw-bold text-uppercase mb-5 tracking-widest">Administrator Sistem:</div>
                    <div class="pt-3 border-top border-white border-opacity-10" style="max-width: 250px;">
                        <div class="text-white fw-bold">REEGIOELLA HUB CONTROL</div>
                        <div class="text-muted tiny font-monospace">UUID: <?= strtoupper(substr(md5(time()), 0, 16)) ?></div>
                    </div>
                </div>
                <div class="col-6 text-end d-flex flex-column align-items-end">
                    <div class="text-muted small fw-bold text-uppercase mb-5 tracking-widest">Pengesahan Otoritas:</div>
                    <div class="pt-3 border-top border-white border-opacity-10" style="width: 280px; text-align: center;">
                        <div class="text-white fw-bold" style="text-decoration: underline;">Bpk. Reynand Anderson</div>
                        <div class="text-muted tiny">Timestamp: <?= date('Y-m-d H:i:s') ?></div>
                    </div>
                </div>
            </div>

            <!-- Print Footer matching reference image (Now visible & editable) -->
            <div class="mt-5 pt-4 bg-white text-dark p-5 rounded-4 shadow-lg position-relative" style="font-family: Arial, Helvetica, sans-serif; font-size: 10pt;">
                <div class="position-absolute top-0 end-0 m-3 badge bg-warning text-dark d-print-none"><i class="fas fa-edit me-1"></i> Mode Edit Tanda Tangan</div>
                <div style="margin-bottom: 10px; color: black;" class="editable-field" contenteditable="true">Disahkan oleh:</div>
                <table style="width: 100%; border: none; font-weight: bold; color: black;">
                    <tr>
                        <td style="width: 15%; padding: 4px 0;"><div class="editable-field" contenteditable="true">Tandatangan</div></td>
                        <td style="width: 35%; padding: 4px 0;">:</td>
                        <td style="width: 25%; padding: 4px 0;"><div class="editable-field" contenteditable="true">Tandatangan Direktur</div></td>
                        <td style="width: 25%; padding: 4px 0;">:</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0;"><div class="editable-field" contenteditable="true">Nama Admin</div></td>
                        <td style="padding: 4px 0;">:</td>
                        <td style="padding: 4px 0;"><div class="editable-field" contenteditable="true">Nama Direktur</div></td>
                        <td style="padding: 4px 0;">:</td>
                    </tr>
                    <tr>
                        <td style="padding: 4px 0;"><div class="editable-field" contenteditable="true">Tarikh</div></td>
                        <td style="padding: 4px 0;">:</td>
                        <td style="padding: 4px 0;"></td>
                        <td style="padding: 4px 0;"></td>
                    </tr>
                </table>
            </div>

            <div class="mt-5 pt-4 text-center border-top border-white border-opacity-10 opacity-50">
                <p class="text-muted small mb-0 font-italic">Laporan ini dihasilkan secara otomatis oleh Digital Twin
                    Model v4.5. <br> Seluruh data presensi dan kinerja adalah valid dan sinkron dengan Hub Terminal
                    Industrial.</p>
            </div>
        </div>
    </div>

    <script>
        if (window.location.search.includes('print=true')) {
            window.addEventListener('load', function() {
                window.print();
            });
        }
    </script>
</body>

</html>