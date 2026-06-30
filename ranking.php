<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Fetch Criteria and Weights
$criteria = $pdo->query("SELECT * FROM criteria ORDER BY id ASC")->fetchAll();
$employees_raw = $pdo->query("SELECT * FROM employees")->fetchAll();

// 1. Fetch Scores Matrix
$matrix = [];
$employee_data = [];
foreach ($employees_raw as $e) {
    $employee_data[$e['id']] = $e;
    foreach ($criteria as $c) {
        $stmt = $pdo->prepare("SELECT score FROM scores WHERE employee_id = ? AND criteria_id = ?");
        $stmt->execute([$e['id'], $c['id']]);
        $matrix[$e['id']][$c['id']] = (float)$stmt->fetchColumn();
    }
}

// 2. Normalize and Calculate Preference Value (V)
$rankings = [];
if (!empty($matrix) && !empty($criteria)) {
    // Get max/min for each criteria
    $crit_stats = [];
    foreach ($criteria as $c) {
        $scores_for_crit = array_column($matrix, $c['id']);
        if (!empty($scores_for_crit)) {
            $crit_stats[$c['id']] = [
                'max' => max($scores_for_crit),
                'min' => min($scores_for_crit)
            ];
        }
    }

    foreach ($matrix as $emp_id => $scores) {
        $v_score = 0;
        foreach ($criteria as $c) {
            $score = $scores[$c['id']];
            $weight = $c['weight'] / 100;

            if (isset($crit_stats[$c['id']])) {
                $max = $crit_stats[$c['id']]['max'];
                $min = $crit_stats[$c['id']]['min'];

                if ($c['type'] == 'benefit' && $max > 0) {
                    $r = $score / $max;
                } elseif ($c['type'] == 'cost' && $score > 0) {
                    $r = $min / $score;
                } else {
                    $r = 0;
                }
                $v_score += $weight * $r;
            }
        }
        $rankings[] = [
            'id' => $emp_id,
            'name' => $employee_data[$emp_id]['name'],
            'score' => $v_score
        ];
    }
}

// 3. Sort Rankings by Score Descending
usort($rankings, function($a, $b) {
    return $b['score'] <=> $a['score'];
});

$total_evaluated = count($rankings);


require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <!-- Formal Print Header (Visible only when printing) -->
        <div class="print-header d-none text-center mb-5">
            <h1 class="text-uppercase fw-bold border-bottom pb-3">Laporan Hasil Penilaian Peringkat Akhir</h1>
            <p class="mt-2 text-muted">Metode AHP dan Simple Additive Weighting (SAW) | Tanggal: <?= date('d/m/Y H:i') ?></p>
        </div>

        <div class="header-section mb-5 animate-fadeIn no-screen">
            <div class="d-flex justify-content-between align-items-center">
                <div>

                    <h1 class="display-5 fw-bold text-white mb-2">Laporan Peringkat Akhir</h1>
                    <p class="text-muted fs-5">Hasil konsolidasi preferensi personel berdasarkan kriteria SAW
                        terverifikasi.</p>
                </div>
                <div class="d-flex gap-3">
                    <a href="report.php?print=true" target="_blank" class="btn-premium px-4 text-decoration-none">
                        <i class="fas fa-file-pdf me-2"></i> Ekspor Laporan
                    </a>
                </div>
            </div>
        </div>

        <div class="bento-grid">
            <!-- Supreme Podium (Cinematic Centerpiece) -->
            <div class="span-12 podium-section">
                <div class="glass bento-card d-flex flex-column align-items-center justify-content-center py-5 animate-fadeIn overflow-hidden"
                    style="min-height: 480px; background: radial-gradient(circle at center, rgba(99, 102, 241, 0.12) 0%, transparent 80%);">

                    <div class="podium-glow-aura"></div>

                    <div class="d-flex justify-content-around align-items-end w-100 px-5 position-relative"
                        style="z-index: 2;">

                        <!-- Rank 2 -->
                        <div class="text-center stagger-1 no-print" style="width: 220px;">
                            <div class="text-muted tiny fw-bold mb-4 tracking-widest uppercase opacity-50">Juara Kedua
                            </div>
                            <div class="glass p-4 mb-4 mx-auto podium-step step-2"
                                style="width: 140px; height: 140px; border-radius: 35px; display: flex; align-items: center; justify-content: center; border-color: rgba(255,255,255,0.05);">
                                <i class="fas fa-medal text-slate-400 fa-3x opacity-50"></i>
                            </div>
                            <h4 class="text-white fw-bold mb-1 fs-5">
                                <?= htmlspecialchars($rankings[1]['name'] ?? 'PENDING...') ?>
                            </h4>
                            <div class="text-primary fw-bold font-monospace fs-5"><?= number_format($rankings[1]['score'] ?? 0, 4) ?></div>
                        </div>

                        <!-- Rank 1 (The Winner / Certificate Focus) -->
                        <div class="text-center stagger-2 winner-highlight w-100">

                            <!-- Certificate Template (Formal & Visible) -->
                            <div class="certificate-section mb-5">
                                <div class="certificate-border">
                                    <div class="certificate-header">
                                        <h1 class="cert-main-title">SURAT KEPUTUSAN</h1>
                                        <p class="cert-sub-title">PENETAPAN HASIL PENILAIAN TERBAIK</p>
                                        <p class="cert-number">Nomor:
                                            <?= date('Y') ?>/DSS-SAW/SK/<?= str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT) ?>
                                        </p>
                                    </div>

                                    <div class="cert-body">
                                        <p class="cert-intro">Berdasarkan hasil analisis Sistem Pendukung Keputusan
                                            (DSS) Penilaian Kinerja Karyawan dengan metode AHP dan SAW terhadap seluruh subyek
                                            yang dinilai, dengan ini menetapkan bahwa:</p>

                                        <div class="cert-recipient">
                                            <p class="recipient-label">DIBERIKAN KEPADA:</p>
                                            <h2 class="recipient-name">
                                                <?= htmlspecialchars($rankings[0]['name'] ?? 'PENDING...') ?>
                                            </h2>
                                        </div>

                                        <p class="cert-achievement">Sebagai</p>
                                        <h3 class="cert-rank-title">PERINGKAT TERBAIK (RANK 1)</h3>

                                        <div class="cert-details">
                                            <p>Dengan total indeks preferensi (V) sebesar: <span
                                                    class="cert-score"><?= number_format($rankings[0]['score'] ?? 0, 4) ?></span></p>
                                        </div>

                                        <p class="cert-closing">Demikian penetapan ini dibuat secara objektif
                                            berdasarkan data kriteria yang telah divalidasi oleh sistem untuk
                                            dipergunakan sebagaimana mestinya.</p>
                                    </div>

                                    <div class="cert-footer">
                                        <div class="cert-sig-box">
                                            <p class="sig-date">Ditetapkan pada: <?= date('d F Y') ?></p>
                                            <div class="sig-line-container">
                                                <div class="sig-line"></div>
                                                <p class="sig-role">Manajer Operasional</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="badge-glass badge-indigo mb-4 shimmer-text scale-125 no-print">PERFORMA TERBAIK
                            </div>
                            <div class="glass p-5 mb-4 mx-auto podium-step step-1 no-print"
                                style="width: 200px; height: 200px; border-radius: 45px; background: linear-gradient(135deg, rgba(99,102,241,0.25) 0%, transparent 100%); display: flex; align-items: center; justify-content: center; border: 2px solid var(--primary); box-shadow: 0 0 60px var(--primary-glow);">
                                <i class="fas fa-crown text-primary fa-6x animate-pulse"></i>
                            </div>
                            <h2 class="winner-name text-white fw-bold mb-1 display-6 no-print">
                                <?= htmlspecialchars($rankings[0]['name'] ?? 'PENDING...') ?>
                            </h2>
                            <div class="winner-score text-primary display-4 fw-bold font-monospace no-print"><?= number_format($rankings[0]['score'] ?? 0, 4) ?>
                            </div>
                            <div class="mt-4 no-print">
                                <button onclick="printCert()" class="btn-premium px-5 py-3 shadow-lg"
                                    style="background: linear-gradient(135deg, #000 0%, #333 100%); border: 1px solid #444;">
                                    <i class="fas fa-certificate me-2 text-warning"></i> CETAK SERTIFIKAT RESMI
                                </button>
                            </div>
                        </div>

                        <!-- Rank 3 -->
                        <div class="text-center stagger-3 no-print" style="width: 220px;">
                            <div class="text-muted tiny fw-bold mb-4 tracking-widest uppercase opacity-50">Juara Ketiga
                            </div>
                            <div class="glass p-4 mb-4 mx-auto podium-step step-3"
                                style="width: 120px; height: 120px; border-radius: 30px; display: flex; align-items: center; justify-content: center; border-color: rgba(255,255,255,0.05);">
                                <i class="fas fa-medal text-amber-700 fa-2x opacity-30"></i>
                            </div>
                            <h4 class="text-white fw-bold mb-1 fs-5">
                                <?= htmlspecialchars($rankings[2]['name'] ?? 'PENDING...') ?>
                            </h4>
                            <div class="text-primary fw-bold font-monospace fs-5"><?= number_format($rankings[2]['score'] ?? 0, 4) ?></div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Detailed Rankings List -->
            <div class="span-12 report-table-section no-print">
                <div class="glass p-0 overflow-hidden shadow-2xl animate-fadeIn stagger-2">
                    <div
                        class="p-4 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center bg-white bg-opacity-5">
                        <h3 class="text-white fs-5 fw-bold m-0"><i class="fas fa-list-ol text-primary me-2"></i>
                            Papan Peringkat</h3>
                        <span class="badge-glass badge-indigo"><?= $total_evaluated ?> Data</span>
                    </div>
                    <div class="premium-table-container">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th width="80px" class="ps-5 text-center">Rank</th>
                                    <th>Nama Subyek</th>
                                    <th>Nilai (V)</th>
                                    <th>Status</th>
                                    <th class="text-end pe-5">Integritas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rankings)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted italic">Data kosong.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rankings as $index => $rank): ?>
                                        <tr class="<?= $index < 3 ? 'bg-white bg-opacity-5' : '' ?>">
                                            <td class="ps-5 text-center">
                                                <div class="fw-bold fs-4 <?= $index < 3 ? 'text-primary' : 'text-muted' ?>">
                                                    #<?= $index + 1 ?></div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-primary rounded-circle"
                                                        style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; background: rgba(99,102,241,0.2) !important; border: 1px solid var(--primary-glow);">
                                                        <?= substr($rank['name'], 0, 1) ?>
                                                    </div>
                                                    <div class="fw-bold text-white fs-5"><?= htmlspecialchars($rank['name']) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-primary font-monospace fs-4">
                                                    <?= number_format($rank['score'], 4) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge-glass fw-bold <?= $index < 2 ? 'badge-emerald' : ($index < 5 ? 'badge-indigo' : 'badge-rose') ?>"
                                                    style="opacity: 1 !important; border-width: 1.5px;">
                                                    <?= $index < 2 ? 'DIREKOMENDASIKAN' : ($index < 5 ? 'STANDAR' : 'EVALUASI') ?>
                                                </span>
                                            </td>
                                            <td class="pe-5 text-end">
                                                <div class="d-inline-flex align-items-center gap-2 text-emerald fw-bold font-monospace"
                                                    style="letter-spacing: 0.1em; font-size: 0.75rem;">
                                                    <i class="fas fa-shield-check"></i> TERVERIFIKASI
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .span-12 {
        grid-column: span 12;
    }

    .podium-step {
        transition: transform 0.5s ease;
        cursor: pointer;
    }

    .podium-step:hover {
        transform: scale(1.05);
    }

    .podium-glow-aura {
        position: absolute;
        width: 1000px;
        height: 1000px;
        background: radial-gradient(circle, rgba(99, 102, 241, 0.08) 0%, transparent 60%);
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        pointer-events: none;
    }

    /* Certificate Print Settings */
    @page {
        size: A4 landscape;
        margin: 0;
    }

    @media print {

        body,
        html {
            width: 297mm !important;
            height: 210mm !important;
        }
    }
</style>

<script>
    function printCert() {
        var style = document.createElement('style');
        style.id = 'print-page-style';
        style.innerHTML = '@page { size: A4 landscape !important; margin: 0 !important; }';
        document.head.appendChild(style);

        document.body.classList.add('print-cert');
        window.print();

        setTimeout(function () {
            if (document.getElementById('print-page-style')) {
                document.head.removeChild(style);
            }
            document.body.classList.remove('print-cert');
        }, 1000);
    }
</script>

<?php require_once 'includes/footer.php'; ?>