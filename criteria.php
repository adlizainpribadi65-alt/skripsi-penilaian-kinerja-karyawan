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
<div class="app-container" style="height: 100vh; overflow: hidden;">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main d-flex flex-column" style="height: 100vh; overflow: hidden; padding-bottom: 0;">
        <div class="header-section mb-2 flex-shrink-0 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 fw-bold text-white mb-1">Kriteria Penilaian</h1>
                    <p class="text-muted small mb-0">Konfigurasi variabel bobot preferensi dan tipe optimasi model SAW.</p>
                </div>
                <button class="btn-premium px-3 py-2" style="font-size: 0.85rem;" data-bs-toggle="modal" data-bs-target="#addCriteriaModal">
                    <i class="fas fa-plus-circle me-1"></i> Tambah Kriteria
                </button>
            </div>
        </div>

        <?php if ($message): ?>
            <div
                class="glass d-flex align-items-center p-3 mb-2 flex-shrink-0 border-start border-4 border-primary">
                <i class="fas fa-check-circle text-primary me-3 fs-3"></i>
                <div class="fw-medium text-white"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <!-- Metrics Row -->
        <div class="row g-2 mb-2 flex-shrink-0">
            <div class="col-4">
                <div class="glass bento-card p-3 h-100 d-flex flex-column justify-content-center stagger-1 border-start border-2 border-primary">
                    <div class="widget-title text-muted font-monospace mb-1" style="font-size: 0.65rem;"><i class="fas fa-scale-balanced me-1"></i> TOTAL AKUMULASI BOBOT</div>
                    <div
                        class="fw-bold fs-4 <?= $total_weight == 100 ? 'text-emerald' : ($total_weight > 100 ? 'text-rose' : 'text-amber') ?>">
                        <?= number_format($total_weight, 0) ?>%
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="glass bento-card p-3 h-100 d-flex flex-column justify-content-center stagger-2 border-start border-2" style="border-color: #10b981 !important;">
                    <div class="widget-title text-muted font-monospace mb-1" style="font-size: 0.65rem;"><i class="fas fa-arrow-up-right-dots me-1"></i> MODE BENEFIT</div>
                    <div class="fw-bold fs-4 text-emerald"><?= $benefit_count ?></div>
                </div>
            </div>
            <div class="col-4">
                <div class="glass bento-card p-3 h-100 d-flex flex-column justify-content-center stagger-3 border-start border-2" style="border-color: #ef4444 !important;">
                    <div class="widget-title text-muted font-monospace mb-1" style="font-size: 0.65rem;"><i class="fas fa-arrow-down-right-dots me-1"></i> MODE COST</div>
                    <div class="fw-bold fs-4 text-rose"><?= $cost_count ?></div>
                </div>
            </div>
        </div>

        <!-- Full Matrix Table -->
        <div class="glass p-0 overflow-hidden shadow-lg flex-grow-1 d-flex flex-column animate-fadeIn stagger-2 mb-3" style="min-height:0;">
            <div
                class="p-3 flex-shrink-0 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center bg-white bg-opacity-5">
                <h3 class="text-white fs-6 fw-bold m-0"><i class="fas fa-layer-group text-primary me-2"></i>
                    Daftar Kriteria</h3>
                <span class="badge-glass badge-indigo text-white" style="font-size: 0.7rem;"><?= count($criteria) ?> Variabel Aktif</span>
            </div>
            <div class="premium-table-container flex-grow-1 overflow-auto">
                <table class="premium-table" style="font-size: 0.85rem;">
                    <thead>
                        <tr>
                            <th width="10%" class="ps-4">ID</th>
                            <th width="35%">Kriteria</th>
                            <th width="20%" class="text-center">Bobot (%)</th>
                            <th width="20%" class="text-center">Tipe Optimasi</th>
                            <th width="15%" class="text-end pe-4">Aksi</th>
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
                                    <td class="ps-4">
                                        <div class="badge-glass badge-indigo font-monospace text-white fw-bold"
                                            style="font-size: 0.75rem;">
                                            C-<?= sprintf("%02d", $c['id']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-white fs-6"><?= htmlspecialchars($c['name']) ?></div>
                                    </td>
                                    <td class="text-center">
                                        <div class="fw-bold text-primary font-monospace fs-5">
                                            <?= number_format($c['weight'], 2) ?>%</div>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge-glass <?= $c['type'] == 'benefit' ? 'badge-emerald' : 'badge-rose' ?> py-1 px-2" style="font-size: 0.75rem; white-space: nowrap;">
                                            <i
                                                class="fas <?= $c['type'] == 'benefit' ? 'fa-plus-circle' : 'fa-minus-circle' ?> me-1"></i>
                                            <?= strtoupper($c['type'] == 'benefit' ? 'Benefit (Max)' : 'Cost (Min)') ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form method="POST" class="d-inline"
                                            onsubmit="return confirm('Hapus kriteria ini secara permanen?')">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" name="delete" class="btn-premium px-2 py-1"
                                                style="font-size: 0.75rem; background: rgba(244, 63, 94, 0.05); box-shadow: none; border: 1px solid rgba(244, 63, 94, 0.15); color: #ef4444;" title="Hapus">
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