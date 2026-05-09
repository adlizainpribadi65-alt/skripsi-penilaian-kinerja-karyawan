<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

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

// Fetch Data for Metrics
$total_subjects = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$total_criteria = $pdo->query("SELECT COUNT(*) FROM criteria")->fetchColumn();
$avg_score = $pdo->query("SELECT AVG(score) FROM scores")->fetchColumn() ?: 0;

// Fetch Data for Dropdowns
$employees = $pdo->query("SELECT id, name FROM employees ORDER BY name ASC")->fetchAll();
$criteria = $pdo->query("SELECT id, name FROM criteria ORDER BY id ASC")->fetchAll();

// Fetch Existing Scores
$stmt = $pdo->query("SELECT s.*, e.name as emp_name, e.position, c.name as crit_name 
                     FROM scores s 
                     JOIN employees e ON s.employee_id = e.id 
                     JOIN criteria c ON s.criteria_id = c.id 
                     ORDER BY e.name ASC, c.id ASC");
$all_scores = $stmt->fetchAll();

require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-5 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="badge-glass badge-indigo mb-3 font-monospace tracking-widest"><i
                            class="fas fa-microchip text-cyan me-2"></i> METRIC_CALIBRATION_HUB_V2</div>
                    <h1 class="display-5 fw-bold text-white mb-2">Manajemen <span class="shimmer-text">Kinerja</span>
                    </h1>
                    <p class="text-muted fs-5">Parameterisasi analitik dan konsolidasi skor untuk setiap subjek
                        personel.</p>
                </div>
                <div class="glass p-3 px-4 d-flex align-items-center gap-4 shadow-glow-mini"
                    style="border: 1px solid var(--primary-glow);">
                    <div class="text-center">
                        <div class="tiny text-primary fw-bold tracking-widest uppercase mb-1">System Health</div>
                        <div class="fw-bold text-white fs-5"><i class="fas fa-shield-check text-emerald me-2"></i>
                            VERIFIED</div>
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
                                <?php foreach ($employees as $emp): ?>
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
                                <?php foreach ($criteria as $crit): ?>
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
                        <h3 class="text-white fs-5 fw-bold m-0"><i class="fas fa-database text-primary me-2"></i>
                            Teragregasi Database Skor</h3>
                        <span class="badge-glass badge-indigo"><?= count($all_scores) ?> PARAM_ENTRIES</span>
                    </div>
                    <div class="premium-table-container" style="max-height: 500px;">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th class="ps-5">Subjek & Parameter</th>
                                    <th class="text-center">Indeks Kinerja</th>
                                    <th class="text-end pe-5">Integritas Log</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($all_scores)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center py-5 text-muted fade-50">
                                            <i class="fas fa-folder-open d-block fs-1 mb-3 opacity-25"></i>
                                            Belum ada data evaluasi tercatat.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($all_scores as $s): ?>
                                        <tr>
                                            <td class="ps-5">
                                                <div class="fw-bold text-white fs-5"><?= htmlspecialchars($s['emp_name']) ?>
                                                </div>
                                                <div class="badge-glass badge-indigo mt-1" style="font-size: 0.6rem;">
                                                    <?= strtoupper(htmlspecialchars($s['crit_name'])) ?></div>
                                            </td>
                                            <td class="text-center">
                                                <div class="fw-bold text-primary display-6" style="font-size: 1.5rem;">
                                                    <?= (float) $s['score'] ?></div>
                                            </td>
                                            <td class="text-end pe-5">
                                                <div
                                                    class="d-inline-flex align-items-center gap-2 text-emerald small font-monospace opacity-75">
                                                    <i class="fas fa-shield-check"></i> SECURE_HASH
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