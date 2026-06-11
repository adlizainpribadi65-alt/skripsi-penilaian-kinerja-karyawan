<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Handle Actions
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add'])) {
        $name = $_POST['name'];
        $weight = $_POST['weight'];
        $type = $_POST['type'];
        $stmt = $pdo->prepare("INSERT INTO criteria (name, weight, type) VALUES (?, ?, ?)");
        $stmt->execute([$name, $weight, $type]);
        $message = "Kriteria optimasi berhasil diintegrasikan ke sistem.";
    } elseif (isset($_POST['delete'])) {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("DELETE FROM criteria WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Kriteria dihapus dari mesin analitik.";
    }
}

$stmt = $pdo->query("SELECT * FROM criteria ORDER BY id ASC");
$criteria = $stmt->fetchAll();

// Metrics
$total_weight = array_sum(array_column($criteria, 'weight'));
$benefit_count = count(array_filter($criteria, fn($c) => $c['type'] == 'benefit'));
$cost_count = count(array_filter($criteria, fn($c) => $c['type'] == 'cost'));

require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-5">
            <div class="d-flex justify-content-between align-items-center">
                <div>

                    <h1 class="display-5 fw-bold text-white mb-2">Kriteria Penilaian</h1>
                    <p class="text-muted fs-5">Konfigurasi variabel bobot preferensi dan tipe optimasi model SAW.</p>
                </div>
                <button class="btn-premium px-4" data-bs-toggle="modal" data-bs-target="#addCriteriaModal">
                    <i class="fas fa-plus-circle me-2"></i> Tambah Kriteria Baru
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div
                class="glass d-flex align-items-center p-4 mb-5 border-start border-4 border-primary">
                <i class="fas fa-check-circle text-primary me-3 fs-3"></i>
                <div class="fw-medium text-white"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <div class="bento-grid">
            <!-- Metrics Row -->
            <div class="span-4">
                <div class="glass bento-card stagger-1">
                    <div class="widget-title"><i class="fas fa-scale-balanced"></i> Total Akumulasi Bobot</div>
                    <div
                        class="metric-value <?= $total_weight == 100 ? 'text-emerald' : ($total_weight > 100 ? 'text-rose' : 'text-amber') ?>">
                        <?= number_format($total_weight, 0) ?>%
                    </div>
                    <div class="text-muted tiny mt-3 uppercase tracking-tighter">Ideal: 100% untuk model linear</div>
                </div>
            </div>
            <div class="span-4">
                <div class="glass bento-card stagger-2">
                    <div class="widget-title"><i class="fas fa-arrow-up-right-dots"></i> Mode Benefit</div>
                    <div class="metric-value text-emerald"><?= $benefit_count ?></div>
                    <div class="text-muted tiny mt-3 uppercase tracking-tighter">Kriteria Maksimasi Output</div>
                </div>
            </div>
            <div class="span-4">
                <div class="glass bento-card stagger-3">
                    <div class="widget-title"><i class="fas fa-arrow-down-right-dots"></i> Mode Cost</div>
                    <div class="metric-value text-rose"><?= $cost_count ?></div>
                    <div class="text-muted tiny mt-3 uppercase tracking-tighter">Kriteria Minimasi Biaya</div>
                </div>
            </div>

            <!-- Full Matrix Table -->
            <div class="span-12">
                <div class="glass p-0 overflow-hidden shadow-2xl animate-fadeIn stagger-2">
                    <div
                        class="p-4 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center bg-white bg-opacity-5">
                        <h3 class="text-dark fs-5 fw-bold m-0"><i class="fas fa-layer-group text-primary me-2"></i>
                            Kriteria</h3>
                        <span class="badge-glass badge-indigo text-dark"><?= count($criteria) ?> Variabel Aktif</span>
                    </div>
                    <div class="premium-table-container">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th width="10%" class="ps-5">ID</th>
                                    <th width="35%">Kriteria</th>
                                    <th width="20%" class="text-center">Bobot (%)</th>
                                    <th width="20%" class="text-center">Tipe Optimasi</th>
                                    <th width="15%" class="text-end pe-5">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($criteria)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted italic">Belum ada kriteria
                                            terdaftar.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($criteria as $c): ?>
                                        <tr>
                                            <td class="ps-5">
                                                <div class="badge-glass badge-indigo font-monospace text-primary"
                                                    style="font-size: 0.7rem;">
                                                    C-<?= sprintf("%02d", $c['id']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="fw-bold text-white fs-5"><?= htmlspecialchars($c['name']) ?></div>
                                            </td>
                                            <td class="text-center">
                                                <div class="fw-bold text-primary font-monospace fs-4">
                                                    <?= number_format($c['weight'], 2) ?>%</div>
                                            </td>
                                            <td class="text-center">
                                                <span
                                                    class="badge-glass <?= $c['type'] == 'benefit' ? 'badge-emerald' : 'badge-rose' ?>">
                                                    <i
                                                        class="fas <?= $c['type'] == 'benefit' ? 'fa-plus-circle' : 'fa-minus-circle' ?> me-2"></i>
                                                    <?= strtoupper($c['type'] == 'benefit' ? 'Benefit (Max)' : 'Cost (Min)') ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-5">
                                                <form method="POST" class="d-inline"
                                                    onsubmit="return confirm('Hapus kriteria ini secara permanen?')">
                                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                    <button type="submit" name="delete" class="btn-premium px-3 py-2"
                                                        style="background: rgba(244, 63, 94, 0.05); box-shadow: none; border: 1px solid rgba(244, 63, 94, 0.15); color: var(--accent);">
                                                        <i class="fas fa-trash-can"></i>
                                                    </button>
                                                </form>
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

<!-- Add Modal (Polished) -->
<div class="modal fade" id="addCriteriaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-pane p-1"
            style="background: rgba(15, 23, 42, 0.98); border: 2px solid var(--primary-glow); border-radius: 30px;">
            <div class="modal-body p-5">
                <div class="brand-font text-primary mb-2 fs-3 text-center tracking-tight">INTEGRASI METRIK</div>
                <p class="text-muted small text-center mb-5 uppercase tracking-widest opacity-50">Kalibrasi Perhitungan
                    SAW</p>

                <form method="POST">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase ms-1 tracking-widest">Identitas
                            Kriteria</label>
                        <input type="text" name="name" class="form-control-glass w-100"
                            placeholder="Ketik nama kriteria..." required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase ms-1 tracking-widest">Bobot
                                (%)</label>
                            <input type="number" step="0.01" name="weight" class="form-control-glass w-100"
                                placeholder="0.00" required>
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label text-muted small fw-bold text-uppercase ms-1 tracking-widest">Tipe
                                Model</label>
                            <select name="type" class="form-control-glass w-100">
                                <option value="benefit">Benefit</option>
                                <option value="cost">Cost</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <button type="submit" name="add" class="btn-premium w-100 justify-content-center py-3">
                            <i class="fas fa-microchip me-2"></i> SINKRONKAN PARAMETER
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    .span-4 {
        grid-column: span 4;
    }

    .span-12 {
        grid-column: span 12;
    }

    .shadow-glow-mini {
        box-shadow: 0 0 15px var(--primary-glow);
    }
</style>

<?php require_once 'includes/footer.php'; ?>