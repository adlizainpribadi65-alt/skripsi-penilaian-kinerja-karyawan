<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Jalankan Sinkronisasi Otomatis
syncAttendanceToSAW($pdo);
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
<div class="app-container" style="height: 100vh; overflow: hidden;">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main d-flex flex-column" style="height: 100vh; overflow: hidden;">
        <div class="header-section mb-3 flex-shrink-0 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-end">
                <div>
                    <h1 class="display-6 fw-bold text-white mb-1">Kalkulasi Penilaian</h1>
                    <p class="text-muted fs-6 mb-0">Proses transformasi matriks keputusan ke skala normalisasi (0-1) berbasis kriteria.</p>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column flex-grow-1 min-vh-0 mb-3 animate-fadeIn" style="min-height:0;">
            <div class="glass p-4 shadow-lg d-flex flex-column h-100" style="min-height:0;">
                <div class="d-flex align-items-center gap-3 mb-3 flex-shrink-0">
                    <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; box-shadow: 0 0 20px var(--primary-glow); border: 2px solid var(--primary-glow);">
                        <i class="fas fa-check-double text-white fs-4"></i>
                    </div>
                    <div>
                        <h3 class="text-white fw-bold mb-0 fs-5">Matriks Normalisasi (R) Berhasil Dihitung</h3>
                        <p class="text-muted mb-0 small">Seluruh parameter kriteria telah dikonversi secara presisi berdasarkan tipe benefit/cost.</p>
                    </div>
                </div>

                <div class="premium-table-container flex-grow-1 overflow-auto" style="border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                    <table class="premium-table" style="font-size: 0.8rem;">
                        <thead>
                            <tr>
                                <th class="ps-4">Identitas Alternatif</th>
                                <?php foreach ($criteria as $c): ?>
                                    <th class="text-center"><?= htmlspecialchars($c['name']) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $e): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-white"><?= htmlspecialchars($e['name']) ?></div>
                                        <div class="tiny text-primary opacity-50 uppercase tracking-widest" style="font-size: 0.65rem;">ID: <?= htmlspecialchars($e['nik']) ?></div>
                                    </td>
                                    <?php foreach ($criteria as $c): ?>
                                        <td class="text-center">
                                            <div class="glass-item py-1 px-2 d-inline-block font-monospace fs-6 text-primary fw-bold" style="min-width: 60px;">
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

        <div class="glass p-3 mb-2 flex-shrink-0 text-center animate-fadeIn stagger-1 d-flex flex-row gap-4 align-items-center justify-content-center">
            <div class="widget-title m-0 tracking-widest text-start" style="font-size: 0.7rem; line-height:1.2;">PIPELINE EKSEKUSI<br>SELANJUTNYA</div>
            <div class="d-flex justify-content-center gap-3">
                <a href="ranking.php" class="btn-premium px-4 py-2" style="font-size: 0.85rem">
                    <i class="fas fa-ranking-star me-2"></i> LIHAT HASIL PERINGKAT
                </a>
                <a href="scores.php" class="btn-premium px-4 py-2 shadow-none" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); font-size: 0.85rem;">
                    <i class="fas fa-pen-nib me-2"></i> SESUAIKAN INPUT SKOR
                </a>
            </div>
        </div>
    </main>
</div>

<style>
    body, html {
        overflow: hidden;
    }
    .shadow-glow-mini { box-shadow: 0 0 15px var(--primary-glow); }
</style>

<?php require_once 'includes/footer.php'; ?>
