<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Auto-migration: Ensure scores table has created_at
try {
    $pdo->exec("ALTER TABLE scores ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
} catch (Exception $e) {
    // Ignore if already exists or other errors
}

// Handle Score Input
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_score'])) {
    $emp_id = $_POST['employee_id'];
    $crit_id = $_POST['criteria_id'];
    $score = $_POST['score'];

    $stmt = $pdo->prepare("INSERT INTO scores (employee_id, criteria_id, score) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE score = VALUES(score)");
    $stmt->execute([$emp_id, $crit_id, $score]);
    $message = "Analisis kinerja subjek berhasil disinkronkan ke pusat data.";
}

// Handle Filtering by Week
$filter_week = $_GET['filter_week'] ?? date('Y-\WW'); // e.g. 2023-W10
if (strpos($filter_week, '-W') !== false) {
    list($year, $week) = explode('-W', $filter_week);
    $dto = new DateTime();
    $dto->setISODate((int)$year, (int)$week);
    $start_date = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $end_date = $dto->format('Y-m-d');
} else {

    $start_date = date('Y-m-d', strtotime('monday this week'));
    $end_date = date('Y-m-d', strtotime('sunday this week'));
}

// Fetch Data for Metrics
$total_subjects = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$total_criteria = $pdo->query("SELECT COUNT(*) FROM criteria")->fetchColumn();
$avg_score = $pdo->query("SELECT AVG(score) FROM scores")->fetchColumn() ?: 0;

// Fetch Data for Dropdowns
$employees_list = $pdo->query("SELECT id, name FROM employees ORDER BY name ASC")->fetchAll();
$criteria_list = $pdo->query("SELECT id, name, weight, type FROM criteria ORDER BY id ASC")->fetchAll();

// Fetch All Scores for the selected week and Group by Employee
$stmt = $pdo->prepare("SELECT s.*, e.name as emp_name, c.name as crit_name 
                     FROM scores s 
                     JOIN employees e ON s.employee_id = e.id 
                     JOIN criteria c ON s.criteria_id = c.id 
                     WHERE DATE(s.created_at) BETWEEN ? AND ?
                     AND s.id IN (
                        SELECT MAX(id) FROM scores GROUP BY employee_id, criteria_id, DATE(created_at)
                     )
                     ORDER BY e.name ASC, c.id ASC");
$stmt->execute([$start_date, $end_date]);
$all_scores_raw = $stmt->fetchAll();



$consolidated_scores = [];
foreach ($all_scores_raw as $s) {
    if (!isset($consolidated_scores[$s['employee_id']])) {
        $consolidated_scores[$s['employee_id']] = [
            'name' => $s['emp_name'],
            'scores' => []
        ];
    }
    $consolidated_scores[$s['employee_id']]['scores'][$s['criteria_id']] = $s['score'];
}

// Optional: Calculate Preliminary SAW Result for 'Skor Gabungan'
// We'll perform basic normalization and weighting here
$saw_scores = [];
if (!empty($consolidated_scores) && !empty($criteria_list)) {
    // Get max/min for each criteria
    $crit_stats = [];
    foreach ($criteria_list as $c) {
        $scores_for_crit = array_column(array_column($consolidated_scores, 'scores'), $c['id']);
        if (!empty($scores_for_crit)) {
            $crit_stats[$c['id']] = [
                'max' => max($scores_for_crit),
                'min' => min($scores_for_crit)
            ];
        }
    }

    foreach ($consolidated_scores as $emp_id => $data) {
        $v_score = 0;
        foreach ($criteria_list as $c) {
            $score = $data['scores'][$c['id']] ?? 0;
            $weight = $c['weight'] / 100; // Assuming weights sum to 100
            
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
        $saw_scores[$emp_id] = $v_score;
    }
}


require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-5 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>

                    <h1 class="display-5 fw-bold text-white mb-2">Manajemen <span class="shimmer-text">Kinerja</span>
                    </h1>
                    <p class="text-muted fs-5">Parameterisasi analitik dan konsolidasi skor untuk setiap subjek
                        personel.</p>
                    <div
                        class="glass-pane d-inline-flex align-items-center p-2 px-3 mt-2 border-start border-3 border-primary">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        <span class="text-white small fw-bold">Skor Absensi diperbarui otomatis setiap scan Kiosk
                            (Siklus Mingguan)</span>
                    </div>
                </div>
                <div class="glass p-3 px-4 d-flex align-items-center gap-4 shadow-glow-mini"
                    style="border: 1px solid var(--primary-glow);">
                    <div class="text-center">
                        <div class="tiny text-primary fw-bold tracking-widest uppercase mb-1">Kesehatan Sistem</div>
                        <div class="fw-bold text-white fs-5"><i class="fas fa-shield-check text-emerald me-2"></i>
                            TERVERIFIKASI</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div
                class="glass d-flex align-items-center p-4 mb-5 border-start border-4 border-emerald animate-fadeIn shadow-glow-mini">
                <i class="fas fa-circle-check text-emerald me-3 fs-3"></i>
                <div class="fw-medium text-white"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <div class="bento-grid">
            <!-- Summary Metrics -->
            <div class="span-4">
                <div class="glass bento-card stagger-1">
                    <div class="widget-title"><i class="fas fa-users"></i> Subjek Terdaftar</div>
                    <div class="metric-value"><?= number_format($total_subjects) ?></div>
                    <div class="text-muted tiny mt-3 uppercase tracking-tighter">Personel siap dianalisis</div>
                </div>
            </div>
            <div class="span-4">
                <div class="glass bento-card stagger-2">
                    <div class="widget-title"><i class="fas fa-sliders-h"></i> Kriteria Aktif</div>
                    <div class="metric-value text-secondary"><?= number_format($total_criteria) ?></div>
                    <div class="text-muted tiny mt-3 uppercase tracking-tighter">Parameter DSS Terpasang</div>
                </div>
            </div>
            <div class="span-4">
                <div class="glass bento-card stagger-3">
                    <div class="widget-title"><i class="fas fa-chart-line"></i> Rata-rata Skor</div>
                    <div class="metric-value text-cyan"><?= number_format($avg_score, 1) ?></div>
                    <div class="text-muted tiny mt-3 uppercase tracking-tighter">Indeks Performa Global</div>
                </div>
            </div>

            <!-- Input Form (Bento Style) -->
            <div class="span-4">
                <div class="glass bento-card h-100 stagger-2">
                    <div class="widget-title mb-4"><i class="fas fa-pen-nib text-primary"></i> Log Entri Baru</div>
                    <form method="POST">
                        <div class="mb-4">
                            <label
                                class="form-label text-muted small fw-bold text-uppercase ms-1 tracking-widest">IDENTIFIKASI
                                PERSONEL</label>
                            <select name="employee_id" class="form-control-glass w-100" required>
                                <option value="">-- Pilih Subject --</option>
                                <?php foreach ($employees_list as $emp): ?>
                                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label
                                class="form-label text-muted small fw-bold text-uppercase ms-1 tracking-widest">PARAMETER
                                ANALITIK</label>
                            <select name="criteria_id" class="form-control-glass w-100" required>
                                <option value="">-- Pilih Kriteria --</option>
                                <?php foreach ($criteria_list as $crit): ?>
                                    <option value="<?= $crit['id'] ?>"><?= htmlspecialchars($crit['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-5">
                            <label
                                class="form-label text-muted small fw-bold text-uppercase ms-1 tracking-widest">KUALITAS
                                SKOR (0-100)</label>
                            <input type="number" step="0.01" name="score" class="form-control-glass w-100 fs-4 py-3"
                                placeholder="0.00" required>
                        </div>
                        <button type="submit" name="save_score" class="btn-premium w-100 justify-content-center py-3">
                            <i class="fas fa-cloud-arrow-up me-2"></i> PUBLIKASI NILAI
                        </button>
                    </form>

                </div>
            </div>

            <!-- Database Table (Bento Style) -->
            <div class="span-8">
                <div class="glass p-0 overflow-hidden shadow-glow-mini h-100 stagger-3">
                    <div
                        class="p-4 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center bg-white bg-opacity-5">
                        <div class="d-flex align-items-center gap-3">
                            <h3 class="text-white fs-5 fw-bold m-0"><i class="fas fa-database text-primary me-2"></i>
                            Rekapitulasi Skor</h3>
                            <form method="GET" class="d-flex align-items-center gap-2">
                                <label class="text-muted small fw-bold text-uppercase ms-2">Minggu Ke:</label>
                                <input type="week" name="filter_week" value="<?= $filter_week ?>" 
                                       class="form-control-glass py-1 px-2" style="font-size: 0.8rem; width: 200px;" 
                                       onchange="this.form.submit()">
                            </form>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="export_rekap.php?type=pdf&filter_week=<?= $filter_week ?>" target="_blank" class="btn-premium py-1 px-3" style="font-size: 0.75rem;">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </a>
                            <a href="export_rekap.php?type=excel&filter_week=<?= $filter_week ?>" class="btn-premium py-1 px-3" style="font-size: 0.75rem; background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.2); color: #10b981;">
                                <i class="fas fa-file-excel me-1"></i> EXCEL
                            </a>

                            <span class="badge-glass badge-indigo ms-2"><?= count($consolidated_scores) ?> PERSONEL</span>
                        </div>
                    </div>

                    <div class="premium-table-container" style="max-height: 500px;">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th class="ps-5">Subjek</th>
                                    <?php foreach ($criteria_list as $c): ?>
                                        <th class="text-center" title="<?= htmlspecialchars($c['name']) ?>">
                                            C<?= $c['id'] ?>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="text-end pe-5">Skor Gabungan (SAW)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($consolidated_scores)): ?>
                                    <tr>
                                        <td colspan="<?= count($criteria_list) + 2 ?>" class="text-center py-5 text-muted fade-50">
                                            <i class="fas fa-folder-open d-block fs-1 mb-3 opacity-25"></i>
                                            Belum ada data evaluasi tercatat.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($consolidated_scores as $emp_id => $data): ?>
                                        <tr>
                                            <td class="ps-5">
                                                <div class="fw-bold text-white"><?= htmlspecialchars($data['name']) ?></div>
                                            </td>
                                            <?php foreach ($criteria_list as $c): ?>
                                                <td class="text-center">
                                                    <?php if (isset($data['scores'][$c['id']])): ?>
                                                        <div class="text-primary fw-bold">
                                                            <?= (float)$data['scores'][$c['id']] ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-muted opacity-25">-</div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td class="text-end pe-5">
                                                <div class="badge-glass badge-emerald font-monospace fw-bold fs-6">
                                                    <?= number_format($saw_scores[$emp_id] ?? 0, 4) ?>
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
    .span-4 {
        grid-column: span 4;
    }

    .span-8 {
        grid-column: span 8;
    }

    .shadow-glow-mini {
        box-shadow: 0 0 15px var(--primary-glow);
    }

    .fade-50 {
        opacity: 0.5;
    }
</style>

<?php require_once 'includes/footer.php'; ?>