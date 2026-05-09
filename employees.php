<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];
    try {
        // Because of ON DELETE CASCADE in the database, 
        // deleting an employee will automatically delete their scores and attendance.
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        // Optional: Also delete enrolled face images if they exist in the file system
        $face_dir = 'industrial/faces/' . $delete_id;
        if (is_dir($face_dir)) {
            $files = array_diff(scandir($face_dir), array('.', '..'));
            foreach ($files as $file) {
                unlink("$face_dir/$file");
            }
            rmdir($face_dir);
        }

        $_SESSION['success_msg'] = "Personel berhasil dihapus.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Gagal menghapus personel: " . $e->getMessage();
    }
    header("Location: employees.php");
    exit;
}

// Fetch Employees Data
$stmt = $pdo->query("SELECT * FROM employees ORDER BY name ASC");
$employees = $stmt->fetchAll();

// Statistics
$total_employees = count($employees);
$active_employees = $total_employees; // Assuming all for now, can be refined with status column
$divisions = $pdo->query("SELECT COUNT(DISTINCT position) FROM employees")->fetchColumn();

require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-5 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="badge-glass badge-indigo mb-3 font-monospace tracking-widest"><i
                            class="fas fa-id-card text-cyan me-2"></i> PERSONNEL_ASSET_MANAGER</div>
                    <h1 class="display-5 fw-bold text-white mb-2">Direktori <span class="shimmer-text">Personel</span>
                    </h1>
                    <p class="text-muted fs-5">Manajemen basis data karyawan, otorisasi ID, dan identitas sistem
                        terpadu.</p>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn-premium px-4" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                        <i class="fas fa-user-plus me-2"></i> Tambah Karyawan Baru
                    </button>
                </div>
            </div>
        </div>

        <div class="bento-grid">
            <!-- Stats Row -->
            <div class="span-4">
                <div class="glass bento-card stagger-1">
                    <div class="widget-title"><i class="fas fa-users-viewfinder"></i> Total Personel</div>
                    <div class="metric-value"><?= number_format($total_employees) ?></div>
                    <div class="text-muted tiny mt-3 uppercase tracking-tighter">Subject Evaluasi Terdaftar</div>
                </div>
            </div>
            <div class="span-4">
                <div class="glass bento-card stagger-2">
                    <div class="widget-title"><i class="fas fa-building-user"></i> Total Divisi</div>
                    <div class="metric-value text-secondary"><?= number_format($divisions) ?></div>
                    <div class="text-muted tiny mt-3 uppercase tracking-tighter">Departemen Aktif Terpantau</div>
                </div>
            </div>
            <div class="span-4">
                <div class="glass bento-card stagger-3">
                    <div class="widget-title"><i class="fas fa-shield-halved"></i> Status Keaktifan</div>
                    <div class="metric-value text-cyan">100%</div>
                    <div class="text-muted tiny mt-3 uppercase tracking-tighter">Sistem Identitas Online</div>
                </div>
            </div>

            <!-- Full List -->
            <div class="span-12">
                <div class="glass p-0 overflow-hidden shadow-2xl animate-fadeIn stagger-2">
                    <div
                        class="p-4 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center bg-white bg-opacity-5">
                        <h3 class="text-white fs-5 fw-bold m-0"><i class="fas fa-users-gear text-primary me-2"></i>
                            Database Personel Terverifikasi</h3>
                        <div class="d-flex gap-3">
                            <div class="glass p-1 px-3 d-flex align-items-center gap-2"
                                style="border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                                <i class="fas fa-magnifying-glass small text-muted"></i>
                                <input type="text" class="bg-transparent border-0 text-white small outline-none"
                                    placeholder="Cari personel..." style="width: 150px;">
                            </div>
                            <span class="badge-glass badge-indigo"><?= $total_employees ?> ENTRIES</span>
                        </div>
                    </div>
                    <div class="premium-table-container">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th class="ps-5">Hash ID / NIK</th>
                                    <th>Nama Lengkap</th>
                                    <th>Posisi / Divisi</th>
                                    <th class="text-center">Keaktifan</th>
                                    <th class="text-end pe-5">Manajemen Sesi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($employees)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted italic">
                                            <i class="fas fa-folder-open d-block fs-1 mb-3 opacity-25"></i>
                                            Belum ada data karyawan terdaftar.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $emp): ?>
                                        <tr>
                                            <td class="ps-5">
                                                <div class="badge-glass badge-indigo py-2 font-monospace"
                                                    style="font-size: 0.7rem;">
                                                    <i
                                                        class="fas fa-fingerprint me-2 text-primary"></i><?= htmlspecialchars($emp['nik']) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="bg-primary rounded-circle"
                                                        style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center; font-weight: 800; color: white; background: rgba(99,102,241,0.15) !important; border: 1px solid var(--primary-glow);">
                                                        <?= strtoupper(substr($emp['name'], 0, 1)) ?>
                                                    </div>
                                                    <div class="fw-bold text-white fs-5"><?= htmlspecialchars($emp['name']) ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div
                                                    class="text-muted small fw-bold text-uppercase tracking-tighter opacity-75">
                                                    <?= htmlspecialchars($emp['position']) ?></div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge-glass badge-emerald">
                                                    <i class="fas fa-circle-check me-1"></i> AKTIF
                                                </span>
                                            </td>
                                            <td class="text-end pe-5">
                                                <div class="d-flex justify-content-end gap-2">
                                                    <a href="industrial/enroll_face.php?id=<?= $emp['id'] ?>"
                                                        class="btn-premium px-3 py-2"
                                                        style="background: rgba(34, 211, 238, 0.05); box-shadow: none; border: 1px solid rgba(34, 211, 238, 0.2); color: var(--cyan);" title="Enroll Face">
                                                        <i class="fas fa-face-viewfinder"></i>
                                                    </a>
                                                    <button class="btn-premium px-3 py-2"
                                                        style="background: rgba(255,255,255,0.03); box-shadow: none; border: 1px solid var(--border-glass);" title="Edit Personel">
                                                        <i class="fas fa-pen-to-square text-primary"></i>
                                                    </button>
                                                    <form method="POST" class="m-0" onsubmit="return confirm('Apakah Anda yakin ingin menghapus personel ini? Semua data terkait (termasuk nilai) akan ikut terhapus.');">
                                                        <input type="hidden" name="delete_id" value="<?= $emp['id'] ?>">
                                                        <button type="submit" name="action" value="delete" class="btn-premium px-3 py-2"
                                                            style="background: rgba(239, 68, 68, 0.05); box-shadow: none; border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444;" title="Hapus Personel">
                                                            <i class="fas fa-trash-can"></i>
                                                        </button>
                                                    </form>
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

    .span-12 {
        grid-column: span 12;
    }

    .shadow-glow-mini {
        box-shadow: 0 0 15px var(--primary-glow);
    }
</style>

<?php require_once 'includes/footer.php'; ?>