<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Jalankan Sinkronisasi Otomatis
syncAttendanceToSAW($pdo);
syncInventoryToSAW($pdo);
syncProductionToSAW($pdo);

// Ambil kriteria dan alternatif
$criteria = $pdo->query("SELECT * FROM criteria")->fetchAll();
$employees = $pdo->query("SELECT * FROM employees")->fetchAll();

// 1. Ambil Matrix Keputusan (R)
$matrix = [];
foreach ($employees as $e) {
    foreach ($criteria as $c) {
        $stmt = $pdo->prepare("SELECT score FROM scores WHERE employee_id = ? AND criteria_id = ?");
        $stmt->execute([$e['id'], $c['id']]);
        $matrix[$e['id']][$c['id']] = (float)$stmt->fetchColumn();
    }
}

// 2. Normalisasi Matrix
$norm_matrix = [];
foreach ($criteria as $c) {
    $scores = array_column($matrix, $c['id']);
    $max = !empty($scores) ? max($scores) : 1;
    $min = !empty($scores) ? min($scores) : 1;
    
    foreach ($employees as $e) {
        $val = $matrix[$e['id']][$c['id']];
        if ($c['type'] == 'benefit') {
            $norm_matrix[$e['id']][$c['id']] = $val / ($max ?: 1);
        } else {
            $norm_matrix[$e['id']][$c['id']] = ($min ?: 1) / ($val ?: 1);
        }
    }
}

require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-5 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <div class="badge-glass badge-indigo mb-3 uppercase tracking-widest font-monospace">ALGORITHMIC_NORMALIZATION_ENGINE</div>
                    <h1 class="display-5 fw-bold text-white mb-2">Mesin <span class="shimmer-text">Kalkulasi SAW</span></h1>
                    <p class="text-muted fs-5">Proses transformasi matriks keputusan ke skala normalisasi (0-1) berbasis kriteria.</p>
                </div>
                <div class="badge-glass badge-indigo p-3 px-4 shadow-glow-mini">
                    <i class="fas fa-microchip me-2 text-primary animate-pulse"></i> ENGINE_STATUS: STABLE
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5 animate-fadeIn">
            <div class="col-md-12">
                <div class="glass p-5 shadow-lg">
                    <div class="d-flex align-items-center gap-4 mb-5">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 64px; height: 64px; box-shadow: 0 0 30px var(--primary-glow); border: 2px solid var(--primary-glow);">
                            <i class="fas fa-check-double text-white fs-3"></i>
                        </div>
                        <div>
                            <h3 class="text-white fw-bold mb-1">Matriks Normalisasi (R) Berhasil Dihitung</h3>
                            <p class="text-muted mb-0">Seluruh parameter kriteria telah dikonversi secara presisi berdasarkan tipe benefit/cost.</p>
                        </div>
                    </div>

                    <div class="premium-table-container">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th class="ps-5">Identitas Alternatif</th>
                                    <?php foreach ($criteria as $c): ?>
                                        <th class="text-center"><?= htmlspecialchars($c['name']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($employees as $e): ?>
                                    <tr>
                                        <td class="ps-5">
                                            <div class="fw-bold text-white"><?= htmlspecialchars($e['name']) ?></div>
                                            <div class="tiny text-primary opacity-50 uppercase tracking-widest">ID: <?= htmlspecialchars($e['nik']) ?></div>
                                        </td>
                                        <?php foreach ($criteria as $c): ?>
                                            <td class="text-center">
                                                <div class="glass-item py-2 px-3 d-inline-block font-monospace fs-5 text-primary fw-bold" style="min-width: 80px;">
                                                    <?= number_format($norm_matrix[$e['id']][$c['id']], 3) ?>
                                                </div>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="glass p-5 mb-5 text-center animate-fadeIn stagger-1">
            <div class="widget-title mb-4 tracking-widest">PIPELINE EKSEKUSI SELANJUTNYA</div>
            <div class="d-flex justify-content-center gap-4">
                <a href="ranking.php" class="btn-premium px-5 py-3">
                    <i class="fas fa-ranking-star me-2"></i> LIHAT HASIL PERINGKAT
                </a>
                <a href="scores.php" class="btn-premium px-5 py-3 shadow-none" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass);">
                    <i class="fas fa-pen-nib me-2"></i> SESUAIKAN INPUT SKOR
                </a>
            </div>
        </div>
    </main>
</div>

<style>
    .shadow-glow-mini { box-shadow: 0 0 15px var(--primary-glow); }
</style>

<?php require_once 'includes/footer.php'; ?>
