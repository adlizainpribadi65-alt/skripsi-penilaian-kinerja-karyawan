<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

$message = "";

// Fetch criteria IDs dynamically
$crit_kualitas = $pdo->query("SELECT id FROM criteria WHERE name='Kualitas Kerja'")->fetchColumn();
$crit_disiplin = $pdo->query("SELECT id FROM criteria WHERE name='Disiplin'")->fetchColumn();
$crit_kerjasama = $pdo->query("SELECT id FROM criteria WHERE name='Kerjasama'")->fetchColumn();
$crit_absensi = $pdo->query("SELECT id FROM criteria WHERE name='Absensi'")->fetchColumn();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_penilaian'])) {
    $scores_kualitas = $_POST['score_kualitas'] ?? [];
    $scores_disiplin = $_POST['score_disiplin'] ?? [];
    $scores_kerjasama = $_POST['score_kerjasama'] ?? [];

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO scores (employee_id, criteria_id, score) VALUES (?, ?, ?) 
                               ON DUPLICATE KEY UPDATE score = VALUES(score)");

        foreach ($scores_kualitas as $emp_id => $val) {
            if (is_numeric($val) && $crit_kualitas) {
                $stmt->execute([$emp_id, $crit_kualitas, $val]);
            }
            if (isset($scores_disiplin[$emp_id]) && is_numeric($scores_disiplin[$emp_id]) && $crit_disiplin) {
                $stmt->execute([$emp_id, $crit_disiplin, $scores_disiplin[$emp_id]]);
            }
            if (isset($scores_kerjasama[$emp_id]) && is_numeric($scores_kerjasama[$emp_id]) && $crit_kerjasama) {
                $stmt->execute([$emp_id, $crit_kerjasama, $scores_kerjasama[$emp_id]]);
            }
        }

        $pdo->commit();
        $message = "Log penilaian berhasil diperbarui secara massal.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Gagal menyimpan: " . $e->getMessage();
    }
}

// Get all employees
$employees = $pdo->query("SELECT id, name, nik FROM employees ORDER BY name ASC")->fetchAll();

// Get existing scores for these criteria to pre-fill the inputs
$existing_scores = [];
if ($crit_kualitas && $crit_disiplin && $crit_kerjasama) {
    $ids = array_filter([$crit_kualitas, $crit_disiplin, $crit_kerjasama, $crit_absensi]);
    $ids_str = implode(',', $ids);
    $stmt = $pdo->query("SELECT employee_id, criteria_id, score FROM scores WHERE criteria_id IN ($ids_str)");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_scores[$row['employee_id']][$row['criteria_id']] = $row['score'];
    }
}

require_once 'includes/header.php';
?>
<div class="app-container" style="height: 100vh; overflow: hidden;">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main d-flex flex-column" style="height: 100vh; overflow: hidden; padding-bottom: 0;">
        <div class="header-section mb-1 flex-shrink-0 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 fw-bold text-white mb-2">Log <span class="shimmer-text">Penilaian</span></h1>
                    <p class="text-muted small mb-0">Inisialisasi input massal untuk Kualitas Kerja, Disiplin, dan Kerjasama.</p>
                </div>
                <div>
                    <!-- Section widget removed for a cleaner look -->
                </div>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="glass p-2 mb-2 border-start border-4 border-emerald animate-fadeIn flex-shrink-0">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-check-circle text-emerald fs-4"></i>
                    <div class="text-white fw-medium small"><?= $message ?></div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$crit_kualitas || !$crit_disiplin || !$crit_kerjasama): ?>
            <div class="glass p-2 mb-2 border-start border-4 border-amber animate-fadeIn flex-shrink-0">
                <div class="d-flex align-items-center gap-3">
                    <i class="fas fa-exclamation-triangle text-amber fs-4"></i>
                    <div class="text-white fw-medium small">Peringatan: Kriteria tidak lengkap di database. Pastikan nama kriteria sesuai.</div>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" class="d-flex flex-column flex-grow-1 w-100 mb-2" style="min-height: 0;">
            <!-- Submit Card -->
            <div class="w-100 flex-shrink-0 mb-3">
                <div class="glass d-flex flex-row justify-content-between align-items-center p-3 shadow-glow-hover"
                    style="border: 2px dashed rgba(99, 102, 241, 0.1); border-radius: 12px;">
                        <div>
                            <h3 class="text-white fw-bold mb-1 fs-6"><i
                                    class="fas fa-cloud-arrow-up text-primary me-2"></i> Finalisasi Data Penilaian</h3>
                            <p class="text-muted small mb-0">Pastikan seluruh input telah diverifikasi sebelum disimpan
                                ke sistem.</p>
                        </div>
                        <button type="submit" name="save_penilaian" class="btn-premium px-4 py-2" style="font-size: 0.85rem;">
                            <i class="fas fa-save me-2"></i> SIMPAN PENILAIAN MASSAL
                        </button>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="w-100 animate-fadeIn stagger-1 flex-grow-1 d-flex flex-column" style="min-height: 0;">
                    <div class="glass p-0 overflow-hidden shadow-lg h-100 d-flex flex-column w-100" style="min-height: 0;">
                        <div
                            class="p-3 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center flex-shrink-0 bg-white bg-opacity-5">
                            <h3 class="text-white fs-6 fw-bold m-0"><i
                                    class="fas fa-users-viewfinder text-primary me-2"></i> Daftar Personel Berdinas</h3>
                            <span class="badge-glass badge-indigo" style="font-size: 0.7rem;"><?= count($employees) ?> Entri Aktif</span>
                        </div>
                        <div class="premium-table-container flex-grow-1 overflow-auto">
                            <table class="premium-table">
                                <thead>
                                    <tr>
                                        <th class="ps-4" style="width: 20%">Informasi Personel</th>
                                        <th class="text-center" style="width: 20%">Kualitas Kerja (25%)</th>
                                        <th class="text-center" style="width: 20%">Disiplin (20%)</th>
                                        <th class="text-center" style="width: 20%">Kerjasama (15%)</th>
                                        <th class="text-center pe-4" style="width: 20%">Absensi (AUTO)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $row):
                                        $emp_id = $row['id'];
                                        $val_kualitas = $existing_scores[$emp_id][$crit_kualitas] ?? 0;
                                        $val_disiplin = $existing_scores[$emp_id][$crit_disiplin] ?? 0;
                                        $val_kerjasama = $existing_scores[$emp_id][$crit_kerjasama] ?? 0;
                                        $val_absensi = $existing_scores[$emp_id][$crit_absensi] ?? 0;
                                        ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="text-white fw-bold fs-6"><?= htmlspecialchars($row['name']) ?>
                                                </div>
                                                <div class="badge-glass badge-indigo mt-1" style="font-size: 0.65rem;">
                                                    <i
                                                        class="fas fa-id-badge me-1 text-primary"></i><?= htmlspecialchars($row['nik']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <input type="number" step="0.01" name="score_kualitas[<?= $emp_id ?>]"
                                                        class="form-control-glass text-center fs-6"
                                                        style="width: 100px; border-radius: 8px; padding: 8px;"
                                                        value="<?= (float) $val_kualitas ?>" min="0" max="100">
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center">
                                                    <input type="number" step="0.01" name="score_disiplin[<?= $emp_id ?>]"
                                                        class="form-control-glass text-center fs-6"
                                                        style="width: 100px; border-radius: 8px; padding: 8px;"
                                                        value="<?= (float) $val_disiplin ?>" min="0" max="100">
                                                </div>
                                            </td>
                                            <td class="pe-4">
                                                <div class="d-flex justify-content-center">
                                                    <input type="number" step="0.01" name="score_kerjasama[<?= $emp_id ?>]"
                                                        class="form-control-glass text-center fs-6"
                                                        style="width: 100px; border-radius: 8px; padding: 8px;"
                                                        value="<?= (float) $val_kerjasama ?>" min="0" max="100">
                                                </div>
                                            </td>
                                            <td class="pe-4">
                                                <div class="d-flex flex-column align-items-center">
                                                    <div class="fw-bold text-primary fs-5"><?= (float) $val_absensi ?></div>
                                                    <div class="badge-glass badge-emerald mt-1" style="font-size: 0.5rem;">
                                                        <i class="fas fa-sync fa-spin"></i> OTOMATIS
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
        </form>
    </main>
</div>

<style>
    body, html {
        overflow: hidden;
    }
    .col-span-12 {
        grid-column: span 12;
    }

    .shadow-glow-mini {
        box-shadow: 0 0 15px var(--primary-glow);
    }

    .shadow-glow-hover:hover {
        box-shadow: 0 0 30px var(--primary-glow);
        border-color: var(--primary) !important;
    }
</style>

<?php require_once 'includes/footer.php'; ?>