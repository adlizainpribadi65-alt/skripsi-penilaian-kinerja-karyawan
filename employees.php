<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Handle Add Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $nik = trim($_POST['nik'] ?? '');
    if ($nik !== '' && stripos($nik, 'ID-') !== 0) {
        $nik = 'ID-' . $nik;
    } elseif (stripos($nik, 'ID-') === 0) {
        $nik = 'ID-' . substr($nik, 3);
    }
    $name = $_POST['name'] ?? '';
    $position = $_POST['position'] ?? '';
    $department = $_POST['department'] ?? '';

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("INSERT INTO employees (nik, name, position, department) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nik, $name, $position, $department]);
        $new_emp_id = $pdo->lastInsertId();

        // Initialize scores for SAW to prevent errors
        initializeEmployeeScores($pdo, $new_emp_id);

        $pdo->commit();
        $_SESSION['success_msg'] = "Karyawan baru berhasil ditambahkan.";
    } catch (PDOException $e) {
        if ($pdo->inTransaction())
            $pdo->rollBack();
        $_SESSION['error_msg'] = "Gagal menambahkan karyawan: " . $e->getMessage();
    }
    header("Location: employees.php");
    exit;
}

// Handle Edit Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $nik = trim($_POST['nik'] ?? '');
    if ($nik !== '' && stripos($nik, 'ID-') !== 0) {
        $nik = 'ID-' . $nik;
    } elseif (stripos($nik, 'ID-') === 0) {
        $nik = 'ID-' . substr($nik, 3);
    }
    $name = $_POST['name'] ?? '';
    $position = $_POST['position'] ?? '';
    $department = $_POST['department'] ?? '';

    try {
        $stmt = $pdo->prepare("UPDATE employees SET nik = ?, name = ?, position = ?, department = ? WHERE id = ?");
        $stmt->execute([$nik, $name, $position, $department, $id]);
        $_SESSION['success_msg'] = "Data personel berhasil diperbarui.";
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Gagal memperbarui data personel: " . $e->getMessage();
    }
    header("Location: employees.php");
    exit;
}

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
<div class="app-container" style="height: 100vh; overflow: hidden;">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main d-flex flex-column" style="height: 100vh; overflow: hidden;">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show glass ms-0 mb-4" role="alert"
                style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981;">
                <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success_msg'];
                unset($_SESSION['success_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show glass ms-0 mb-4" role="alert"
                style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444;">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error_msg'];
                unset($_SESSION['error_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="header-section mb-2 flex-shrink-0 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>

                    <h1 class="display-5 fw-bold text-white mb-2">Direktori <span class="shimmer-text">Personel</span>
                    </h1>
                    <p class="text-muted fs-5 mb-0">Manajemen basis data karyawan, otorisasi ID, dan identitas sistem
                        terpadu.</p>
                </div>
                <div class="d-flex gap-3">
                    <button class="btn-premium px-4" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                        <i class="fas fa-user-plus me-2"></i> Tambah Karyawan Baru
                    </button>
                </div>
            </div>
        </div>

        <div class="bento-grid mb-2 flex-shrink-0">
            <!-- Stats Row -->
            <div class="span-4">
                <div class="glass bento-card stagger-1">
                    <div class="widget-title"><i class="fas fa-users-viewfinder"></i> Total Personel</div>
                    <div class="metric-value"><?= number_format($total_employees) ?></div>
                    <div class="text-muted tiny mt-3 uppercase tracking-tighter">Subjek Evaluasi Terdaftar</div>
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
        </div>

        <!-- Full List -->
        <div class="span-12 d-flex flex-column" style="flex: 1 1 auto; min-height: 0;">
            <div class="glass p-0 overflow-hidden shadow-2xl animate-fadeIn stagger-2 d-flex flex-column h-100 w-100">
                <div
                    class="p-4 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center bg-white bg-opacity-5 flex-shrink-0">
                    <h3 class="text-white fs-5 fw-bold m-0"><i class="fas fa-users-gear text-primary me-2"></i>
                        Database Personel Terverifikasi</h3>
                    <div class="d-flex gap-3">
                        <div class="glass p-1 px-3 d-flex align-items-center gap-2"
                            style="border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                            <i class="fas fa-magnifying-glass small text-muted"></i>
                            <input type="text" class="bg-transparent border-0 text-white small outline-none"
                                placeholder="Cari personel..." style="width: 150px;">
                        </div>
                        <span class="badge-glass badge-indigo"><?= $total_employees ?> DATA</span>
                    </div>
                </div>
                <div class="premium-table-container flex-grow-1 overflow-auto">
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
                                            <div class="text-muted small fw-bold text-uppercase tracking-tighter opacity-75">
                                                <?= htmlspecialchars($emp['position']) ?>
                                            </div>
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
                                                    style="background: rgba(34, 211, 238, 0.05); box-shadow: none; border: 1px solid rgba(34, 211, 238, 0.2); color: var(--cyan);"
                                                    title="Daftar Wajah">
                                                    <i class="fas fa-face-viewfinder"></i>
                                                </a>
                                                <button class="btn-premium px-3 py-2" data-bs-toggle="modal"
                                                    data-bs-target="#editEmployeeModal" data-bs-id="<?= $emp['id'] ?>"
                                                    data-bs-nik="<?= htmlspecialchars($emp['nik']) ?>"
                                                    data-bs-name="<?= htmlspecialchars($emp['name']) ?>"
                                                    data-bs-position="<?= htmlspecialchars($emp['position']) ?>"
                                                    data-bs-department="<?= htmlspecialchars($emp['department']) ?>"
                                                    style="background: rgba(255,255,255,0.03); box-shadow: none; border: 1px solid var(--border-glass);"
                                                    title="Edit Personel">
                                                    <i class="fas fa-pen-to-square text-primary"></i>
                                                </button>
                                                <form method="POST" class="m-0"
                                                    onsubmit="return confirm('Apakah Anda yakin ingin menghapus personel ini? Semua data terkait (termasuk nilai) akan ikut terhapus.');">
                                                    <input type="hidden" name="delete_id" value="<?= $emp['id'] ?>">
                                                    <button type="submit" name="action" value="delete"
                                                        class="btn-premium px-3 py-2"
                                                        style="background: rgba(239, 68, 68, 0.05); box-shadow: none; border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444;"
                                                        title="Hapus Personel">
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
    body,
    html {
        overflow: hidden;
    }

    .span-4 {
        grid-column: span 4;
    }

    .span-12 {
        grid-column: span 12;
    }

    .shadow-glow-mini {
        box-shadow: 0 0 15px var(--primary-glow);
    }

    /* Reduce margin and padding of content main */
    .content-main {
        padding: 8px 30px 8px !important;
    }

    /* Make title smaller & reduce margins */
    .header-section {
        margin-bottom: 0.25rem !important;
    }

    .header-section h1 {
        font-size: 1.5rem !important;
        margin-bottom: 0.15rem !important;
    }

    .header-section p {
        font-size: 0.8rem !important;
        margin-bottom: 0 !important;
    }

    .header-section .btn-premium {
        padding: 6px 12px !important;
        font-size: 0.8rem !important;
    }

    /* Shrink Bento Grid stats row */
    .bento-grid {
        gap: 12px !important;
        margin-bottom: 0.4rem !important;
    }

    .bento-card {
        padding: 8px 12px !important;
        min-height: 70px !important;
        justify-content: center !important;
    }

    .bento-card .widget-title {
        font-size: 0.52rem !important;
        margin: 0 0 2px 0 !important;
        letter-spacing: 0.12em !important;
    }

    .bento-card .metric-value {
        font-size: 1.35rem !important;
        margin-bottom: 0 !important;
    }

    .bento-card .text-muted.tiny {
        font-size: 0.55rem !important;
        margin-top: 1px !important;
    }

    /* Shrink table padding */
    .premium-table thead th {
        padding: 6px 12px !important;
        font-size: 0.65rem !important;
    }

    .premium-table td {
        padding: 6px 12px !important;
        font-size: 0.82rem !important;
    }

    .premium-table td div.fs-5 {
        font-size: 0.92rem !important;
    }

    .badge-glass {
        padding: 3px 8px !important;
        font-size: 0.58rem !important;
    }
</style>

<?php require_once 'includes/footer.php'; ?>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass shadow-2xl border-0"
            style="background: var(--bg-card); backdrop-filter: blur(25px);">
            <div class="modal-header border-bottom border-white border-opacity-10 p-4">
                <h5 class="modal-title text-white fw-bold" id="addEmployeeModalLabel">
                    <i class="fas fa-user-plus text-primary me-2"></i> Registrasi Personel Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form action="employees.php" method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase tracking-widest">Hash ID /
                            NIK</label>
                        <input type="text" name="nik" class="form-control-glass w-100"
                            placeholder="Contoh: ID-2024001 (Otomatis ditambahkan)" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase tracking-widest">Nama
                            Lengkap</label>
                        <input type="text" name="name" class="form-control-glass w-100"
                            placeholder="Masukkan nama lengkap..." required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase tracking-widest">Posisi /
                            Jabatan</label>
                        <input type="text" name="position" class="form-control-glass w-100"
                            placeholder="Contoh: Senior Tailor" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small fw-bold text-uppercase tracking-widest">Divisi /
                            Departemen</label>
                        <input type="text" name="department" class="form-control-glass w-100"
                            placeholder="Contoh: Produksi Utama" required>
                    </div>
                </div>
                <div class="modal-footer border-top border-white border-opacity-10 p-4 pt-0">
                    <button type="button" class="btn text-white-50 fw-bold me-3" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn-premium px-4">
                        <i class="fas fa-save me-2"></i> SIMPAN PERSONEL
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass shadow-2xl border-0"
            style="background: var(--bg-card); backdrop-filter: blur(25px);">
            <div class="modal-header border-bottom border-white border-opacity-10 p-4">
                <h5 class="modal-title text-white fw-bold" id="editEmployeeModalLabel">
                    <i class="fas fa-user-pen text-primary me-2"></i> Perbarui Data Personel
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <form action="employees.php" method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit-id">
                <div class="modal-body p-4">
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase tracking-widest">Hash ID /
                            NIK</label>
                        <input type="text" name="nik" id="edit-nik" class="form-control-glass w-100"
                            placeholder="Contoh: ID-2024001" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase tracking-widest">Nama
                            Lengkap</label>
                        <input type="text" name="name" id="edit-name" class="form-control-glass w-100"
                            placeholder="Masukkan nama lengkap..." required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label text-muted small fw-bold text-uppercase tracking-widest">Posisi /
                            Jabatan</label>
                        <input type="text" name="position" id="edit-position" class="form-control-glass w-100"
                            placeholder="Contoh: Senior Tailor" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label text-muted small fw-bold text-uppercase tracking-widest">Divisi /
                            Departemen</label>
                        <input type="text" name="department" id="edit-department" class="form-control-glass w-100"
                            placeholder="Contoh: Produksi Utama" required>
                    </div>
                </div>
                <div class="modal-footer border-top border-white border-opacity-10 p-4 pt-0">
                    <button type="button" class="btn text-white-50 fw-bold me-3" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn-premium px-4">
                        <i class="fas fa-rotate me-2"></i> SIMPAN PERUBAHAN
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const editModal = document.getElementById('editEmployeeModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                const id = button.getAttribute('data-bs-id');
                const nik = button.getAttribute('data-bs-nik');
                const name = button.getAttribute('data-bs-name');
                const position = button.getAttribute('data-bs-position');
                const department = button.getAttribute('data-bs-department');

                editModal.querySelector('#edit-id').value = id;
                editModal.querySelector('#edit-nik').value = nik;
                editModal.querySelector('#edit-name').value = name;
                editModal.querySelector('#edit-position').value = position;
                editModal.querySelector('#edit-department').value = department;
            });
        }
    });
</script>