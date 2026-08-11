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

// Rekapitulasi Mingguan Only
$message = "";

// Handle Filtering Cycles
$filter_type = $_GET['filter_type'] ?? 'weekly';
$filter_date = $_GET['filter_date'] ?? date('Y-m-d');
$filter_week = $_GET['filter_week'] ?? date('Y-\WW');
$filter_month = $_GET['filter_month'] ?? date('Y-m');

$start_date = '';
$end_date = '';
$display_period = '';

if ($filter_type === 'daily') {
    $start_date = $filter_date;
    $end_date = $filter_date;
    $display_period = "Harian ($filter_date)";
} elseif ($filter_type === 'monthly') {
    $start_date = $filter_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $display_period = "Bulanan ($filter_month)";
} else {
    // default to weekly
    $filter_type = 'weekly';
    if (strpos($filter_week, '-W') !== false) {
        list($year, $week) = explode('-W', $filter_week);
        $dto = new DateTime();
        $dto->setISODate((int) $year, (int) $week);
        $dto->modify('-1 day');
        $start_date = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $end_date = $dto->format('Y-m-d');
    } else {
        $day_of_week = date('w');
        $start_date = date('Y-m-d', strtotime("-$day_of_week days"));
        $end_date = date('Y-m-d', strtotime($start_date . ' +6 days'));
    }
    $display_period = "Mingguan ($filter_week)";
}

// Fetch Data for Metrics
$total_subjects = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$total_criteria = $pdo->query("SELECT COUNT(*) FROM criteria")->fetchColumn();
$avg_score = $pdo->query("SELECT AVG(score) FROM scores")->fetchColumn() ?: 0;

// Fetch Data for Dropdowns
$employees_list = $pdo->query("SELECT id, name FROM employees ORDER BY id ASC")->fetchAll();
$criteria_list = $pdo->query("SELECT id, name, weight, type FROM criteria ORDER BY id ASC")->fetchAll();

// Fetch All Employees chronologically
$employees_raw = $pdo->query("SELECT id, name FROM employees ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch All Current Scores
$stmt = $pdo->prepare("SELECT s.* FROM scores s");
$stmt->execute();
$all_scores_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$consolidated_scores = [];
// Initialize base to ensure ALL employees are shown and in exact ID order
foreach ($employees_raw as $emp) {
    $consolidated_scores[$emp['id']] = [
        'name' => $emp['name'],
        'scores' => []
    ];
}

// Merge scores
foreach ($all_scores_raw as $s) {
    if (isset($consolidated_scores[$s['employee_id']])) {
        $consolidated_scores[$s['employee_id']]['scores'][$s['criteria_id']] = $s['score'];
    }
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

            if (isset($data['scores'][$c['id']])) {
                $v_score += $score * $weight;
            }
        }
        $saw_scores[$emp_id] = $v_score;
    }
}


require_once 'includes/header.php';
?>
<div class="app-container" style="height: 100vh; overflow: hidden;">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main d-flex flex-column" style="height: 100vh; overflow: hidden;">
        <!-- Header compact -->
        <div class="header-section mb-2 flex-shrink-0 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 fw-bold text-white mb-1">Rekapitulasi <span class="shimmer-text">Perangkingan</span></h1>
                    <p class="text-muted small mb-0">Laporan mingguan hasil perangkingan skor karyawan berdasarkan metode terverifikasi.</p>
                </div>
                <div class="glass p-2 px-3 d-flex align-items-center gap-3 shadow-glow-mini"
                    style="border: 1px solid var(--primary-glow);">
                    <div class="text-center">
                        <div class="tiny text-primary fw-bold tracking-widest uppercase mb-0" style="font-size:0.55rem">Kesehatan Sistem</div>
                        <div class="fw-bold text-white small"><i class="fas fa-shield-check text-emerald me-1"></i> TERVERIFIKASI</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="glass d-flex align-items-center p-2 px-3 mb-2 border-start border-4 border-emerald animate-fadeIn flex-shrink-0">
                <i class="fas fa-circle-check text-emerald me-2 fs-5"></i>
                <div class="fw-medium text-white small"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <!-- Top Row: 3 Metric Cards -->
        <div class="row g-2 mb-2 flex-shrink-0">
            <div class="col-4">
                <div class="glass p-3 stagger-1">
                    <div class="widget-title mb-1" style="font-size:0.6rem"><i class="fas fa-users"></i> Subjek Terdaftar</div>
                    <div class="fw-bold text-white fs-4"><?= number_format($total_subjects) ?></div>
                    <div class="text-muted" style="font-size:0.6rem">Personel siap dianalisis</div>
                </div>
            </div>
            <div class="col-4">
                <div class="glass p-3 stagger-2">
                    <div class="widget-title mb-1" style="font-size:0.6rem"><i class="fas fa-sliders-h"></i> Kriteria Aktif</div>
                    <div class="fw-bold text-secondary fs-4"><?= number_format($total_criteria) ?></div>
                    <div class="text-muted" style="font-size:0.6rem">Parameter DSS Terpasang</div>
                </div>
            </div>
            <div class="col-4">
                <div class="glass p-3 stagger-3">
                    <div class="widget-title mb-1" style="font-size:0.6rem"><i class="fas fa-chart-line"></i> Rata-rata Skor</div>
                    <div class="fw-bold text-cyan fs-4"><?= number_format($avg_score, 1) ?></div>
                    <div class="text-muted" style="font-size:0.6rem">Indeks Performa Global</div>
                </div>
            </div>
        </div>

        <!-- Bottom Row: Form (left) + Table (right) -->
        <div class="row g-2 flex-grow-1" style="min-height: 0;">
            <!-- Table -->
            <div class="col-12 h-100 d-flex flex-column" style="min-height:0">
                <div class="glass p-0 overflow-hidden shadow-glow-mini h-100 stagger-3 d-flex flex-column" style="min-height:0">
                    <div class="p-3 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center bg-white bg-opacity-5 flex-shrink-0">
                        <div class="d-flex align-items-center gap-2">
                            <h3 class="text-white fs-6 fw-bold m-0"><i class="fas fa-database text-primary me-2"></i>Rekapitulasi Skor</h3>
                            <form method="GET" class="d-flex align-items-center gap-2">
                                <select name="filter_type" class="form-control-glass py-1 px-2"
                                    style="font-size: 0.75rem;" onchange="this.form.submit()">
                                    <option value="daily" <?= $filter_type == 'daily' ? 'selected' : '' ?>>Harian</option>
                                    <option value="weekly" <?= $filter_type == 'weekly' ? 'selected' : '' ?>>Mingguan</option>
                                    <option value="monthly" <?= $filter_type == 'monthly' ? 'selected' : '' ?>>Bulanan</option>
                                </select>
                                <?php if ($filter_type == 'daily'): ?>
                                    <input type="date" name="filter_date" value="<?= $filter_date ?>"
                                        class="form-control-glass py-1 px-2" style="font-size: 0.75rem;"
                                        onchange="this.form.submit()">
                                <?php elseif ($filter_type == 'monthly'): ?>
                                    <input type="month" name="filter_month" value="<?= $filter_month ?>"
                                        class="form-control-glass py-1 px-2" style="font-size: 0.75rem;"
                                        onchange="this.form.submit()">
                                <?php else: ?>
                                    <input type="week" name="filter_week" value="<?= $filter_week ?>"
                                        class="form-control-glass py-1 px-2" style="font-size: 0.75rem; width: 180px;"
                                        onchange="this.form.submit()">
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                            <a href="export_rekap.php?type=pdf&filter_type=<?= $filter_type ?>&filter_date=<?= $filter_date ?>&filter_week=<?= $filter_week ?>&filter_month=<?= $filter_month ?>"
                                target="_blank" class="btn-premium py-1 px-2" style="font-size: 0.65rem;">
                                <i class="fas fa-file-pdf me-1"></i> PDF
                            </a>
                            <a href="export_rekap.php?type=excel&filter_type=<?= $filter_type ?>&filter_date=<?= $filter_date ?>&filter_week=<?= $filter_week ?>&filter_month=<?= $filter_month ?>"
                                class="btn-premium py-1 px-2"
                                style="font-size: 0.65rem; background: rgba(16, 185, 129, 0.1); border-color: rgba(16, 185, 129, 0.2); color: #10b981;">
                                <i class="fas fa-file-excel me-1"></i> EXCEL
                            </a>
                            <span class="badge-glass badge-indigo" style="font-size:0.6rem"><?= count($consolidated_scores) ?> PERSONEL</span>
                        </div>
                    </div>

                    <div class="premium-table-container flex-grow-1 overflow-auto">
                        <table class="premium-table" style="font-size: 0.8rem;">
                            <thead>
                                <tr>
                                    <th class="ps-4">Informasi Personel</th>
                                    <?php foreach ($criteria_list as $c): ?>
                                        <th class="text-center text-primary" style="background: rgba(99,102,241,0.05);" title="Bobot <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['weight']) ?>%)">
                                            <?= htmlspecialchars($c['name']) ?><br>
                                            <span style="font-size: 0.65rem;" class="text-muted"><?= htmlspecialchars($c['weight']) ?>%</span>
                                        </th>
                                    <?php endforeach; ?>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Target Kinerja</th>
                                    <th class="text-center pe-4">Keterangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($consolidated_scores)): ?>
                                    <tr>
                                        <td colspan="<?= count($criteria_list) + 2 ?>"
                                            class="text-center py-4 text-muted fade-50">
                                            <i class="fas fa-folder-open d-block fs-3 mb-2 opacity-25"></i>
                                            Belum ada data evaluasi tercatat.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($consolidated_scores as $emp_id => $data): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-white"><?= htmlspecialchars($data['name']) ?></div>
                                            </td>
                                            <?php foreach ($criteria_list as $c): ?>
                                                <td class="text-center border-start border-primary border-opacity-10" style="background: rgba(99,102,241,0.02);">
                                                    <?php if (isset($data['scores'][$c['id']])): ?>
                                                        <div class="text-emerald fw-bold">
                                                            <?php 
                                                                $val = (float) $data['scores'][$c['id']];
                                                                $wdt = $val * ($c['weight'] / 100);
                                                                echo rtrim(rtrim(number_format($wdt, 2, ',', ''), '0'), ',');
                                                            ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-muted opacity-25">-</div>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endforeach; ?>
                                            <td class="text-center">
                                                <div class="badge-glass badge-indigo font-monospace fw-bold" style="font-size:0.8rem">
                                                    <?php 
                                                        $score100 = round($saw_scores[$emp_id] ?? 0, 2);
                                                        echo str_replace('.00', '', number_format($score100, 2, ',', '')); 
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="text-center font-monospace fw-bold fs-6">70</td>
                                            <?php
                                                if ($score100 > 70) {
                                                    $ket = 'DIGAJI';
                                                    $bg = 'badge-emerald';
                                                } elseif ($score100 == 70) {
                                                    $ket = 'DIBINA';
                                                    $bg = 'badge-indigo';
                                                } else {
                                                    $ket = 'DIKELUARKAN';
                                                    $bg = 'badge-rose';
                                                }
                                            ?>
                                            <td class="text-center pe-4">
                                                <div class="d-flex align-items-center justify-content-end gap-2">
                                                    <span class="badge-glass fw-bold <?= $bg ?>" style="opacity: 1 !important; border-width: 1.5px;">
                                                        <?= $ket ?>
                                                    </span>
                                                    <?php if ($ket === 'DIBINA'): ?>
                                                    <form method="POST" action="sop_termination.php" class="m-0" title="Proses Surat Peringatan">
                                                        <input type="hidden" name="action" value="auto_sop_trigger">
                                                        <input type="hidden" name="employee_id" value="<?= $emp_id ?>">
                                                        <input type="hidden" name="trigger_type" value="dibina">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning shadow-none" style="font-size:0.6rem; padding: 2px 6px;">
                                                            <i class="fas fa-file-pen me-1"></i> SP
                                                        </button>
                                                    </form>
                                                    <?php elseif ($ket === 'DIKELUARKAN'): ?>
                                                    <form method="POST" action="sop_termination.php" class="m-0" title="Proses PHK Langsung" onsubmit="return confirm('Apakah Anda yakin ingin MELAKUKAN PHK LANGSUNG untuk karyawan ini?');">
                                                        <input type="hidden" name="action" value="auto_sop_trigger">
                                                        <input type="hidden" name="employee_id" value="<?= $emp_id ?>">
                                                        <input type="hidden" name="trigger_type" value="dikeluarkan">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger shadow-none" style="font-size:0.6rem; padding: 2px 6px;">
                                                            <i class="fas fa-user-xmark me-1"></i> PHK
                                                        </button>
                                                    </form>
                                                    <?php endif; ?>
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
    body, html {
        overflow: hidden;
    }

    .shadow-glow-mini {
        box-shadow: 0 0 15px var(--primary-glow);
    }

    .fade-50 {
        opacity: 0.5;
    }
</style>

<?php require_once 'includes/footer.php'; ?>