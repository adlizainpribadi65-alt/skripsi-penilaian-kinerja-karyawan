<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Auto-create termination_records table if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS termination_records (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        termination_type ENUM('resign','layoff','contract_end','disciplinary','mutual_agreement') NOT NULL,
        reason TEXT NOT NULL,
        warning_1_date DATE DEFAULT NULL,
        warning_2_date DATE DEFAULT NULL,
        warning_3_date DATE DEFAULT NULL,
        effective_date DATE NOT NULL,
        notice_period_days INT DEFAULT 30,
        severance_months DECIMAL(5,2) DEFAULT 0,
        status ENUM('draft','sp1_issued','sp2_issued','sp3_issued','review','approved','executed','cancelled') DEFAULT 'draft',
        approved_by VARCHAR(100) DEFAULT NULL,
        notes TEXT DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Table might already exist, ignore
}

// Handle new termination record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_termination') {
        $emp_id = intval($_POST['employee_id']);
        $type = $_POST['termination_type'];
        $reason = trim($_POST['reason']);
        $effective_date = $_POST['effective_date'];
        $notice_days = intval($_POST['notice_period_days'] ?? 30);
        $severance = floatval($_POST['severance_months'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        try {
            $stmt = $pdo->prepare("INSERT INTO termination_records (employee_id, termination_type, reason, effective_date, notice_period_days, severance_months, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'draft')");
            $stmt->execute([$emp_id, $type, $reason, $effective_date, $notice_days, $severance, $notes]);
            $_SESSION['success_msg'] = "Proses pemberhentian berhasil diinisiasi. Status: DRAFT.";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Gagal membuat rekaman: " . $e->getMessage();
        }
        header("Location: sop_termination.php");
        exit;
    }

    if ($_POST['action'] === 'auto_sop_trigger') {
        $emp_id = intval($_POST['employee_id']);
        $trigger_type = $_POST['trigger_type']; 
        
        if ($trigger_type === 'dibina') {
            $type = 'disciplinary';
            $reason = "Kinerja bulanan/mingguan gagal mencapai target minimum (70). Karyawan masuk dalam masa pembinaan khusus dan dievaluasi.";
            $status = 'draft';
        } else {
            // dikeluarkan => langsung saja (executed)
            $type = 'layoff';
            $reason = "Evaluasi hasil kinerja fatal (DIKELUARKAN). Kegagalan berulang atau performa ekstrem di luar batas toleransi perusahaan.";
            $status = 'executed';
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO termination_records (employee_id, termination_type, reason, effective_date, notice_period_days, severance_months, status) VALUES (?, ?, ?, CURDATE(), 30, 0, ?)");
            $stmt->execute([$emp_id, $type, $reason, $status]);
            
            $record_id = $pdo->lastInsertId();

            if ($status === 'executed') {
                try {
                    $pdo->exec("ALTER TABLE employees ADD COLUMN IF NOT EXISTS status ENUM('active','terminated','resigned') DEFAULT 'active'");
                } catch (Exception $e) {}
                $pdo->prepare("UPDATE employees SET status = 'terminated' WHERE id = ?")->execute([$emp_id]);
                $pdo->prepare("UPDATE termination_records SET approved_by = 'Sistem Auto-Kinerja' WHERE id = ?")->execute([$record_id]);
            }
            $_SESSION['success_msg'] = "SOP berhasil diinisiasi otomatis dari Rekapitulasi Kinerja.";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Gagal memproses auto-SOP: " . $e->getMessage();
        }
        header("Location: sop_termination.php");
        exit;
    }

    if ($_POST['action'] === 'update_status') {
        $record_id = intval($_POST['record_id']);
        $new_status = $_POST['new_status'];
        $warning_field = $_POST['warning_field'] ?? null;
        $approved_by = trim($_POST['approved_by'] ?? '');

        try {
            if ($warning_field && in_array($warning_field, ['warning_1_date', 'warning_2_date', 'warning_3_date'])) {
                $stmt = $pdo->prepare("UPDATE termination_records SET status = ?, $warning_field = CURDATE(), approved_by = ? WHERE id = ?");
                $stmt->execute([$new_status, $approved_by ?: null, $record_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE termination_records SET status = ?, approved_by = ? WHERE id = ?");
                $stmt->execute([$new_status, $approved_by ?: null, $record_id]);
            }

            // If status is 'executed', actually deactivate the employee
            if ($new_status === 'executed') {
                // Add status column if not exists
                try {
                    $pdo->exec("ALTER TABLE employees ADD COLUMN IF NOT EXISTS status ENUM('active','terminated','resigned') DEFAULT 'active'");
                } catch (Exception $e) { /* ignore */ }

                $rec = $pdo->prepare("SELECT employee_id FROM termination_records WHERE id = ?");
                $rec->execute([$record_id]);
                $emp_id = $rec->fetchColumn();
                if ($emp_id) {
                    $pdo->prepare("UPDATE employees SET status = 'terminated' WHERE id = ?")->execute([$emp_id]);
                }
            }

            $_SESSION['success_msg'] = "Status berhasil diperbarui menjadi: " . strtoupper($new_status);
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Gagal memperbarui status: " . $e->getMessage();
        }
        header("Location: sop_termination.php");
        exit;
    }

    if ($_POST['action'] === 'cancel_termination') {
        $record_id = intval($_POST['record_id']);
        try {
            $pdo->prepare("UPDATE termination_records SET status = 'cancelled' WHERE id = ?")->execute([$record_id]);
            $_SESSION['success_msg'] = "Proses pemberhentian dibatalkan.";
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Gagal membatalkan: " . $e->getMessage();
        }
        header("Location: sop_termination.php");
        exit;
    }
}

// Fetch all employee data for dropdown
$employees_list = $pdo->query("SELECT id, nik, name, position, department FROM employees ORDER BY id ASC")->fetchAll();

// Fetch all termination records with employee details
$records = $pdo->query("SELECT t.*, e.name as emp_name, e.nik as emp_nik, e.position as emp_position, e.department as emp_department 
                         FROM termination_records t 
                         JOIN employees e ON t.employee_id = e.id 
                         ORDER BY t.created_at DESC")->fetchAll();

// Statistics
$total_records = count($records);
$active_processes = count(array_filter($records, fn($r) => !in_array($r['status'], ['executed', 'cancelled'])));
$executed_count = count(array_filter($records, fn($r) => $r['status'] === 'executed'));
$cancelled_count = count(array_filter($records, fn($r) => $r['status'] === 'cancelled'));

// Status labels and colors
$status_config = [
    'draft' => ['label' => 'DRAFT', 'badge' => 'badge-indigo', 'icon' => 'fa-file-pen', 'color' => '#6366f1'],
    'sp1_issued' => ['label' => 'SP-1 TERBIT', 'badge' => 'badge-amber', 'icon' => 'fa-triangle-exclamation', 'color' => '#f59e0b'],
    'sp2_issued' => ['label' => 'SP-2 TERBIT', 'badge' => 'badge-amber', 'icon' => 'fa-triangle-exclamation', 'color' => '#f97316'],
    'sp3_issued' => ['label' => 'SP-3 TERBIT', 'badge' => 'badge-rose', 'icon' => 'fa-circle-exclamation', 'color' => '#ef4444'],
    'review' => ['label' => 'DALAM REVIEW', 'badge' => 'badge-cyan', 'icon' => 'fa-magnifying-glass', 'color' => '#06b6d4'],
    'approved' => ['label' => 'DISETUJUI', 'badge' => 'badge-emerald', 'icon' => 'fa-circle-check', 'color' => '#10b981'],
    'executed' => ['label' => 'TEREKSEKUSI', 'badge' => 'badge-rose', 'icon' => 'fa-user-xmark', 'color' => '#ef4444'],
    'cancelled' => ['label' => 'DIBATALKAN', 'badge' => 'badge-muted', 'icon' => 'fa-ban', 'color' => '#64748b'],
];

// Type labels
$type_labels = [
    'resign' => 'Pengunduran Diri',
    'layoff' => 'Pemutusan Hubungan Kerja',
    'contract_end' => 'Berakhirnya Kontrak',
    'disciplinary' => 'Pelanggaran Disiplin',
    'mutual_agreement' => 'Kesepakatan Bersama',
];

require_once 'includes/header.php';
?>
<div class="app-container" style="height: 100vh; overflow: hidden;">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main d-flex flex-column" style="height: 100vh; overflow: hidden; padding-bottom: 0;">
        <?php if (isset($_SESSION['success_msg'])): ?>
            <div class="alert alert-success alert-dismissible fade show mb-2 flex-shrink-0" role="alert"
                style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: #10b981; border-radius: 12px;">
                <i class="fas fa-check-circle me-2"></i> <?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_msg'])): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-2 flex-shrink-0" role="alert"
                style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; border-radius: 12px;">
                <i class="fas fa-exclamation-circle me-2"></i> <?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="header-section mb-2 flex-shrink-0 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="display-6 fw-bold text-white mb-1">
                        <i class="fas fa-file-shield text-primary me-2"></i>SOP <span class="shimmer-text">Pemberhentian</span>
                    </h1>
                    <p class="text-muted small mb-0">Prosedur Operasional Standar pemberhentian karyawan sesuai Undang-Undang Ketenagakerjaan.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn-premium px-3 py-2" data-bs-toggle="modal" data-bs-target="#sopGuideModal" style="font-size: 0.8rem;">
                        <i class="fas fa-book-open me-1"></i> Panduan SOP
                    </button>
                    <button class="btn-premium px-3 py-2" data-bs-toggle="modal" data-bs-target="#newTerminationModal" style="font-size: 0.8rem;">
                        <i class="fas fa-plus-circle me-1"></i> Proses Baru
                    </button>
                </div>
            </div>
        </div>

        <!-- Stats Row -->
        <div class="row g-2 mb-2 flex-shrink-0">
            <div class="col-3">
                <div class="glass p-2 px-3 text-center h-100 d-flex flex-column justify-content-center border-start border-2 border-primary">
                    <div class="text-muted font-monospace" style="font-size: 0.55rem; letter-spacing: 1px;">TOTAL KASUS</div>
                    <div class="fw-bold text-white fs-4"><?= $total_records ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="glass p-2 px-3 text-center h-100 d-flex flex-column justify-content-center border-start border-2" style="border-color: #f59e0b !important;">
                    <div class="text-muted font-monospace" style="font-size: 0.55rem; letter-spacing: 1px;">PROSES AKTIF</div>
                    <div class="fw-bold fs-4" style="color: #f59e0b;"><?= $active_processes ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="glass p-2 px-3 text-center h-100 d-flex flex-column justify-content-center border-start border-2" style="border-color: #ef4444 !important;">
                    <div class="text-muted font-monospace" style="font-size: 0.55rem; letter-spacing: 1px;">TELAH EKSEKUSI</div>
                    <div class="fw-bold fs-4" style="color: #ef4444;"><?= $executed_count ?></div>
                </div>
            </div>
            <div class="col-3">
                <div class="glass p-2 px-3 text-center h-100 d-flex flex-column justify-content-center border-start border-2" style="border-color: #64748b !important;">
                    <div class="text-muted font-monospace" style="font-size: 0.55rem; letter-spacing: 1px;">DIBATALKAN</div>
                    <div class="fw-bold fs-4" style="color: #64748b;"><?= $cancelled_count ?></div>
                </div>
            </div>
        </div>

        <!-- SOP Flow Visual -->
        <div class="glass p-3 mb-2 flex-shrink-0 animate-fadeIn stagger-1">
            <div class="d-flex align-items-center justify-content-between gap-1" style="font-size: 0.65rem;">
                <div class="text-center flex-fill">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px; background: rgba(99,102,241,0.15); border: 1px solid rgba(99,102,241,0.3);">
                        <i class="fas fa-file-pen text-primary" style="font-size: 0.7rem;"></i>
                    </div>
                    <div class="text-white fw-bold">Draft</div>
                    <div class="text-muted" style="font-size: 0.55rem;">Inisiasi Kasus</div>
                </div>
                <i class="fas fa-chevron-right text-muted opacity-50"></i>
                <div class="text-center flex-fill">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px; background: rgba(245,158,11,0.15); border: 1px solid rgba(245,158,11,0.3);">
                        <i class="fas fa-triangle-exclamation" style="color: #f59e0b; font-size: 0.7rem;"></i>
                    </div>
                    <div class="text-white fw-bold">SP-1</div>
                    <div class="text-muted" style="font-size: 0.55rem;">Peringatan Pertama</div>
                </div>
                <i class="fas fa-chevron-right text-muted opacity-50"></i>
                <div class="text-center flex-fill">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px; background: rgba(249,115,22,0.15); border: 1px solid rgba(249,115,22,0.3);">
                        <i class="fas fa-triangle-exclamation" style="color: #f97316; font-size: 0.7rem;"></i>
                    </div>
                    <div class="text-white fw-bold">SP-2</div>
                    <div class="text-muted" style="font-size: 0.55rem;">Peringatan Kedua</div>
                </div>
                <i class="fas fa-chevron-right text-muted opacity-50"></i>
                <div class="text-center flex-fill">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px; background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3);">
                        <i class="fas fa-circle-exclamation" style="color: #ef4444; font-size: 0.7rem;"></i>
                    </div>
                    <div class="text-white fw-bold">SP-3</div>
                    <div class="text-muted" style="font-size: 0.55rem;">Peringatan Terakhir</div>
                </div>
                <i class="fas fa-chevron-right text-muted opacity-50"></i>
                <div class="text-center flex-fill">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px; background: rgba(6,182,212,0.15); border: 1px solid rgba(6,182,212,0.3);">
                        <i class="fas fa-magnifying-glass" style="color: #06b6d4; font-size: 0.7rem;"></i>
                    </div>
                    <div class="text-white fw-bold">Review</div>
                    <div class="text-muted" style="font-size: 0.55rem;">Evaluasi Manajemen</div>
                </div>
                <i class="fas fa-chevron-right text-muted opacity-50"></i>
                <div class="text-center flex-fill">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px; background: rgba(16,185,129,0.15); border: 1px solid rgba(16,185,129,0.3);">
                        <i class="fas fa-circle-check" style="color: #10b981; font-size: 0.7rem;"></i>
                    </div>
                    <div class="text-white fw-bold">Approved</div>
                    <div class="text-muted" style="font-size: 0.55rem;">Disetujui Atasan</div>
                </div>
                <i class="fas fa-chevron-right text-muted opacity-50"></i>
                <div class="text-center flex-fill">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px; background: rgba(239,68,68,0.2); border: 2px solid rgba(239,68,68,0.5);">
                        <i class="fas fa-user-xmark" style="color: #ef4444; font-size: 0.7rem;"></i>
                    </div>
                    <div class="text-white fw-bold">Eksekusi</div>
                    <div class="text-muted" style="font-size: 0.55rem;">Pemberhentian Final</div>
                </div>
            </div>
        </div>

        <!-- Records Table -->
        <div class="glass p-0 overflow-hidden shadow-lg flex-grow-1 d-flex flex-column animate-fadeIn stagger-2" style="min-height: 0;">
            <div class="p-3 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center bg-white bg-opacity-5 flex-shrink-0">
                <div class="d-flex align-items-center gap-2">
                    <h3 class="text-white fs-6 fw-bold m-0"><i class="fas fa-clipboard-list text-primary me-2"></i>Rekaman Proses Pemberhentian</h3>
                </div>
                <span class="badge-glass badge-indigo" style="font-size:0.6rem"><?= $total_records ?> KASUS</span>
            </div>
            <div class="premium-table-container flex-grow-1 overflow-auto">
                <table class="premium-table" style="font-size: 0.78rem;">
                    <thead>
                        <tr>
                            <th class="ps-4" style="min-width: 60px;">No</th>
                            <th style="min-width: 160px;">Personel</th>
                            <th style="min-width: 140px;">Jenis</th>
                            <th style="min-width: 130px;">Status</th>
                            <th class="text-center" style="min-width: 90px;">Tanggal Efektif</th>
                            <th class="text-center" style="min-width: 80px;">Masa Tenggang</th>
                            <th class="text-end pe-4" style="min-width: 120px;">Tindakan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fas fa-folder-open d-block fs-3 mb-2 opacity-25"></i>
                                    Belum ada proses pemberhentian yang tercatat.<br>
                                    <small class="opacity-50">Klik "Proses Baru" untuk memulai.</small>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $i => $rec): 
                                $sc = $status_config[$rec['status']] ?? $status_config['draft'];
                                $next_status = null;
                                $next_label = '';
                                $warning_field = null;

                                // Determine next possible status transition
                                switch ($rec['status']) {
                                    case 'draft': $next_status = 'sp1_issued'; $next_label = 'Terbitkan SP-1'; $warning_field = 'warning_1_date'; break;
                                    case 'sp1_issued': $next_status = 'sp2_issued'; $next_label = 'Terbitkan SP-2'; $warning_field = 'warning_2_date'; break;
                                    case 'sp2_issued': $next_status = 'sp3_issued'; $next_label = 'Terbitkan SP-3'; $warning_field = 'warning_3_date'; break;
                                    case 'sp3_issued': $next_status = 'review'; $next_label = 'Ajukan Review'; break;
                                    case 'review': $next_status = 'approved'; $next_label = 'Setujui'; break;
                                    case 'approved': $next_status = 'executed'; $next_label = 'Eksekusi Final'; break;
                                }
                            ?>
                                <tr>
                                    <td class="ps-4">
                                        <span class="fw-bold text-muted">#<?= $i + 1 ?></span>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="rounded-circle d-flex align-items-center justify-content-center" 
                                                style="width: 32px; height: 32px; min-width: 32px; background: rgba(99,102,241,0.12); border: 1px solid rgba(99,102,241,0.25); font-weight: 800; color: var(--primary); font-size: 0.7rem;">
                                                <?= strtoupper(substr($rec['emp_name'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold text-white" style="font-size: 0.8rem;"><?= htmlspecialchars($rec['emp_name']) ?></div>
                                                <div class="text-muted" style="font-size: 0.6rem;"><?= htmlspecialchars($rec['emp_position'] ?? '-') ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-white fw-medium" style="font-size: 0.75rem;"><?= $type_labels[$rec['termination_type']] ?? $rec['termination_type'] ?></span>
                                    </td>
                                    <td>
                                        <!-- Status Badge with progress indicators -->
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge-glass <?= $sc['badge'] ?> py-1 px-2" style="font-size: 0.6rem; white-space: nowrap;">
                                                <i class="fas <?= $sc['icon'] ?> me-1"></i><?= $sc['label'] ?>
                                            </span>
                                        </div>
                                        <!-- SP Progress Dots -->
                                        <div class="d-flex gap-1 mt-1">
                                            <div class="rounded-circle" style="width: 6px; height: 6px; background: <?= $rec['warning_1_date'] ? '#f59e0b' : 'rgba(255,255,255,0.1)' ?>;" title="<?= $rec['warning_1_date'] ? 'SP-1: '.$rec['warning_1_date'] : 'SP-1: Belum' ?>"></div>
                                            <div class="rounded-circle" style="width: 6px; height: 6px; background: <?= $rec['warning_2_date'] ? '#f97316' : 'rgba(255,255,255,0.1)' ?>;" title="<?= $rec['warning_2_date'] ? 'SP-2: '.$rec['warning_2_date'] : 'SP-2: Belum' ?>"></div>
                                            <div class="rounded-circle" style="width: 6px; height: 6px; background: <?= $rec['warning_3_date'] ? '#ef4444' : 'rgba(255,255,255,0.1)' ?>;" title="<?= $rec['warning_3_date'] ? 'SP-3: '.$rec['warning_3_date'] : 'SP-3: Belum' ?>"></div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="font-monospace text-white" style="font-size: 0.75rem;"><?= date('d/m/Y', strtotime($rec['effective_date'])) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span class="fw-bold text-primary"><?= $rec['notice_period_days'] ?></span>
                                        <span class="text-muted" style="font-size: 0.6rem;">hari</span>
                                    </td>
                                    <td class="text-end pe-4">
                                        <div class="d-flex justify-content-end gap-1">
                                            <!-- Detail Button -->
                                            <button class="btn-premium px-2 py-1" style="font-size: 0.65rem; background: rgba(255,255,255,0.03); box-shadow: none; border: 1px solid var(--border-glass);" 
                                                data-bs-toggle="modal" data-bs-target="#detailModal<?= $rec['id'] ?>" title="Lihat Detail">
                                                <i class="fas fa-eye text-primary"></i>
                                            </button>

                                            <?php if (in_array($rec['status'], ['approved', 'executed'])): ?>
                                                <!-- Print Letter Button -->
                                                <a href="print_termination_letter.php?id=<?= $rec['id'] ?>" target="_blank" class="btn-premium px-2 py-1" style="font-size: 0.65rem; background: rgba(59, 130, 246, 0.1); box-shadow: none; border: 1px solid rgba(59, 130, 246, 0.3); color: #3b82f6;" title="Cetak Surat Pemberhentian">
                                                    <i class="fas fa-print"></i>
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($next_status && !in_array($rec['status'], ['executed', 'cancelled'])): ?>
                                                <!-- Next Step Button -->
                                                <form method="POST" class="m-0 d-inline" onsubmit="return confirm('Lanjutkan ke tahap: <?= $next_label ?>?');">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="record_id" value="<?= $rec['id'] ?>">
                                                    <input type="hidden" name="new_status" value="<?= $next_status ?>">
                                                    <?php if ($warning_field): ?>
                                                        <input type="hidden" name="warning_field" value="<?= $warning_field ?>">
                                                    <?php endif; ?>
                                                    <?php if ($next_status === 'approved' || $next_status === 'executed'): ?>
                                                        <input type="hidden" name="approved_by" value="<?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>">
                                                    <?php endif; ?>
                                                    <button type="submit" class="btn-premium px-2 py-1" style="font-size: 0.65rem; background: rgba(<?= $next_status === 'executed' ? '239,68,68' : '16,185,129' ?>,0.1); box-shadow: none; border: 1px solid rgba(<?= $next_status === 'executed' ? '239,68,68' : '16,185,129' ?>,0.3); color: <?= $next_status === 'executed' ? '#ef4444' : '#10b981' ?>;" title="<?= $next_label ?>">
                                                        <i class="fas fa-forward-step me-1"></i><?= $next_label ?>
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (!in_array($rec['status'], ['executed', 'cancelled'])): ?>
                                                <!-- Cancel Button -->
                                                <form method="POST" class="m-0 d-inline" onsubmit="return confirm('Batalkan proses pemberhentian ini?');">
                                                    <input type="hidden" name="action" value="cancel_termination">
                                                    <input type="hidden" name="record_id" value="<?= $rec['id'] ?>">
                                                    <button type="submit" class="btn-premium px-2 py-1" style="font-size: 0.65rem; background: rgba(100,116,139,0.1); box-shadow: none; border: 1px solid rgba(100,116,139,0.3); color: #64748b;" title="Batalkan">
                                                        <i class="fas fa-ban"></i>
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
    </main>
</div>

<!-- Detail Modals for each record -->
<?php foreach ($records as $rec): 
    $sc = $status_config[$rec['status']] ?? $status_config['draft'];
?>
<div class="modal fade" id="detailModal<?= $rec['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass shadow-2xl border-0" style="background: var(--bg-card); backdrop-filter: blur(25px);">
            <div class="modal-header border-bottom border-white border-opacity-10 p-4">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-file-shield text-primary me-2"></i> Detail Proses Pemberhentian
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Employee Info Card -->
                <div class="glass p-3 mb-3" style="background: rgba(99,102,241,0.05); border: 1px solid rgba(99,102,241,0.15);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; background: rgba(99,102,241,0.15); border: 2px solid var(--primary); font-weight: 800; color: var(--primary); font-size: 1.1rem;">
                            <?= strtoupper(substr($rec['emp_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold text-white fs-5"><?= htmlspecialchars($rec['emp_name']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($rec['emp_nik'] ?? '') ?> — <?= htmlspecialchars($rec['emp_position'] ?? '') ?> / <?= htmlspecialchars($rec['emp_department'] ?? '') ?></div>
                        </div>
                        <div class="ms-auto">
                            <span class="badge-glass <?= $sc['badge'] ?> px-3 py-2" style="font-size: 0.75rem;">
                                <i class="fas <?= $sc['icon'] ?> me-1"></i> <?= $sc['label'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="glass p-3 h-100">
                            <div class="text-muted small fw-bold text-uppercase tracking-widest mb-2" style="font-size: 0.6rem;">Informasi Kasus</div>
                            <table style="font-size: 0.8rem; width: 100%;">
                                <tr><td class="text-muted pe-3 py-1">Jenis</td><td class="text-white fw-bold"><?= $type_labels[$rec['termination_type']] ?? $rec['termination_type'] ?></td></tr>
                                <tr><td class="text-muted pe-3 py-1">Tanggal Efektif</td><td class="text-white fw-bold"><?= date('d F Y', strtotime($rec['effective_date'])) ?></td></tr>
                                <tr><td class="text-muted pe-3 py-1">Masa Tenggang</td><td class="text-white fw-bold"><?= $rec['notice_period_days'] ?> hari</td></tr>
                                <tr><td class="text-muted pe-3 py-1">Pesangon</td><td class="text-white fw-bold"><?= $rec['severance_months'] ?> bulan gaji</td></tr>
                                <?php if ($rec['approved_by']): ?>
                                    <tr><td class="text-muted pe-3 py-1">Disetujui Oleh</td><td class="text-white fw-bold"><?= htmlspecialchars($rec['approved_by']) ?></td></tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="glass p-3 h-100">
                            <div class="text-muted small fw-bold text-uppercase tracking-widest mb-2" style="font-size: 0.6rem;">Riwayat Surat Peringatan</div>
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-center gap-2 p-2 rounded" style="background: rgba(255,255,255,0.03);">
                                    <div class="rounded-circle" style="width: 10px; height: 10px; min-width: 10px; background: <?= $rec['warning_1_date'] ? '#f59e0b' : 'rgba(255,255,255,0.1)' ?>;"></div>
                                    <span class="text-white small fw-bold">SP-1</span>
                                    <span class="text-muted small ms-auto"><?= $rec['warning_1_date'] ? date('d/m/Y', strtotime($rec['warning_1_date'])) : 'Belum diterbitkan' ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2 p-2 rounded" style="background: rgba(255,255,255,0.03);">
                                    <div class="rounded-circle" style="width: 10px; height: 10px; min-width: 10px; background: <?= $rec['warning_2_date'] ? '#f97316' : 'rgba(255,255,255,0.1)' ?>;"></div>
                                    <span class="text-white small fw-bold">SP-2</span>
                                    <span class="text-muted small ms-auto"><?= $rec['warning_2_date'] ? date('d/m/Y', strtotime($rec['warning_2_date'])) : 'Belum diterbitkan' ?></span>
                                </div>
                                <div class="d-flex align-items-center gap-2 p-2 rounded" style="background: rgba(255,255,255,0.03);">
                                    <div class="rounded-circle" style="width: 10px; height: 10px; min-width: 10px; background: <?= $rec['warning_3_date'] ? '#ef4444' : 'rgba(255,255,255,0.1)' ?>;"></div>
                                    <span class="text-white small fw-bold">SP-3</span>
                                    <span class="text-muted small ms-auto"><?= $rec['warning_3_date'] ? date('d/m/Y', strtotime($rec['warning_3_date'])) : 'Belum diterbitkan' ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="glass p-3">
                            <div class="text-muted small fw-bold text-uppercase tracking-widest mb-1" style="font-size: 0.6rem;">Alasan Pemberhentian</div>
                            <div class="text-white small"><?= nl2br(htmlspecialchars($rec['reason'])) ?></div>
                            <?php if ($rec['notes']): ?>
                                <div class="text-muted small fw-bold text-uppercase tracking-widest mb-1 mt-3" style="font-size: 0.6rem;">Catatan Tambahan</div>
                                <div class="text-muted small"><?= nl2br(htmlspecialchars($rec['notes'])) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- New Termination Modal -->
<div class="modal fade" id="newTerminationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content glass shadow-2xl border-0" style="background: var(--bg-card); backdrop-filter: blur(25px);">
            <div class="modal-header border-bottom border-white border-opacity-10 p-4">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-file-circle-plus text-primary me-2"></i> Inisiasi Proses Pemberhentian Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="create_termination">
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold text-uppercase tracking-widest" style="font-size: 0.6rem;">Personel yang Bersangkutan</label>
                            <select name="employee_id" class="form-control-glass w-100 py-2" style="font-size: 0.85rem;" required>
                                <option value="">-- Pilih Karyawan --</option>
                                <?php foreach ($employees_list as $emp): ?>
                                    <option value="<?= $emp['id'] ?>"><?= htmlspecialchars($emp['name']) ?> (<?= htmlspecialchars($emp['nik']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-muted small fw-bold text-uppercase tracking-widest" style="font-size: 0.6rem;">Jenis Pemberhentian</label>
                            <select name="termination_type" class="form-control-glass w-100 py-2" style="font-size: 0.85rem;" required>
                                <option value="">-- Pilih Jenis --</option>
                                <option value="resign">Pengunduran Diri (Resign)</option>
                                <option value="layoff">Pemutusan Hubungan Kerja (PHK)</option>
                                <option value="contract_end">Berakhirnya Kontrak</option>
                                <option value="disciplinary">Pelanggaran Disiplin</option>
                                <option value="mutual_agreement">Kesepakatan Bersama</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold text-uppercase tracking-widest" style="font-size: 0.6rem;">Alasan / Dasar Hukum</label>
                            <textarea name="reason" class="form-control-glass w-100" rows="3" style="font-size: 0.85rem; resize: none;" 
                                placeholder="Jelaskan alasan pemberhentian secara detail (contoh: Pelanggaran Pasal XX PP/UU Ketenagakerjaan No. XX Tahun 20XX)" required></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold text-uppercase tracking-widest" style="font-size: 0.6rem;">Tanggal Efektif</label>
                            <input type="date" name="effective_date" class="form-control-glass w-100 py-2" style="font-size: 0.85rem;" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold text-uppercase tracking-widest" style="font-size: 0.6rem;">Masa Tenggang (Hari)</label>
                            <input type="number" name="notice_period_days" class="form-control-glass w-100 py-2" style="font-size: 0.85rem;" value="30" min="0" max="180" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-muted small fw-bold text-uppercase tracking-widest" style="font-size: 0.6rem;">Pesangon (Bulan Gaji)</label>
                            <input type="number" step="0.5" name="severance_months" class="form-control-glass w-100 py-2" style="font-size: 0.85rem;" value="0" min="0" max="24">
                        </div>
                        <div class="col-12">
                            <label class="form-label text-muted small fw-bold text-uppercase tracking-widest" style="font-size: 0.6rem;">Catatan Tambahan (Opsional)</label>
                            <textarea name="notes" class="form-control-glass w-100" rows="2" style="font-size: 0.85rem; resize: none;" 
                                placeholder="Catatan internal, referensi dokumen pendukung, dll."></textarea>
                        </div>
                    </div>

                    <!-- Legal Reference Card -->
                    <div class="mt-3 p-3 rounded-3" style="background: rgba(245,158,11,0.08); border: 1px solid rgba(245,158,11,0.2);">
                        <div class="d-flex align-items-start gap-2">
                            <i class="fas fa-triangle-exclamation mt-1" style="color: #f59e0b;"></i>
                            <div style="font-size: 0.75rem;">
                                <div class="fw-bold mb-1" style="color: #f59e0b;">Dasar Hukum yang Berlaku</div>
                                <div class="text-muted" style="line-height: 1.5;">
                                    Proses pemberhentian harus sesuai dengan <strong class="text-white">UU No. 13 Tahun 2003</strong> tentang Ketenagakerjaan 
                                    jo. <strong class="text-white">UU No. 11 Tahun 2020</strong> (Cipta Kerja) dan <strong class="text-white">PP No. 35 Tahun 2021</strong> 
                                    tentang PKWT, Alih Daya, Waktu Kerja, dan PHK. Pastikan seluruh tahapan SP telah dilalui sebelum eksekusi pemberhentian.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top border-white border-opacity-10 p-4">
                    <button type="button" class="btn text-white-50 fw-bold me-3" data-bs-dismiss="modal">BATAL</button>
                    <button type="submit" class="btn-premium px-4">
                        <i class="fas fa-file-circle-plus me-2"></i> INISIASI PROSES
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SOP Guide Modal -->
<div class="modal fade" id="sopGuideModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content glass shadow-2xl border-0" style="background: var(--bg-card); backdrop-filter: blur(25px);">
            <div class="modal-header border-bottom border-white border-opacity-10 p-4">
                <h5 class="modal-title text-white fw-bold">
                    <i class="fas fa-book-open text-primary me-2"></i> Panduan SOP Pemberhentian Karyawan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Step 1 -->
                <div class="d-flex gap-3 mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                        style="width: 40px; height: 40px; background: rgba(99,102,241,0.15); border: 2px solid rgba(99,102,241,0.4); font-weight: 800; color: var(--primary);">1</div>
                    <div>
                        <div class="fw-bold text-white mb-1">Identifikasi dan Dokumentasi Pelanggaran</div>
                        <div class="text-muted small" style="line-height: 1.6;">
                            Kumpulkan bukti-bukti pelanggaran atau alasan pemberhentian. Dokumentasikan setiap insiden secara kronologis, 
                            termasuk tanggal, saksi, dan bukti pendukung. Buat laporan tertulis yang ditandatangani oleh atasan langsung.
                        </div>
                    </div>
                </div>
                <!-- Step 2 -->
                <div class="d-flex gap-3 mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                        style="width: 40px; height: 40px; background: rgba(245,158,11,0.15); border: 2px solid rgba(245,158,11,0.4); font-weight: 800; color: #f59e0b;">2</div>
                    <div>
                        <div class="fw-bold text-white mb-1">Penerbitan Surat Peringatan 1 (SP-1)</div>
                        <div class="text-muted small" style="line-height: 1.6;">
                            Berikan SP-1 secara resmi dan tertulis kepada karyawan. SP-1 berlaku selama <strong class="text-white">6 bulan</strong> sejak diterbitkan. 
                            Karyawan diberikan kesempatan untuk memperbaiki kinerja/perilaku. Lampirkan bukti pelanggaran dan harapan perbaikan yang jelas.
                        </div>
                    </div>
                </div>
                <!-- Step 3 -->
                <div class="d-flex gap-3 mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                        style="width: 40px; height: 40px; background: rgba(249,115,22,0.15); border: 2px solid rgba(249,115,22,0.4); font-weight: 800; color: #f97316;">3</div>
                    <div>
                        <div class="fw-bold text-white mb-1">Penerbitan Surat Peringatan 2 (SP-2)</div>
                        <div class="text-muted small" style="line-height: 1.6;">
                            Jika pelanggaran berlanjut dalam masa berlaku SP-1, terbitkan SP-2. SP-2 berlaku selama <strong class="text-white">6 bulan</strong>. 
                            Lakukan konseling formal dengan HR dan atasan. Dokumentasikan hasil pembinaan dan target perbaikan kuantitatif.
                        </div>
                    </div>
                </div>
                <!-- Step 4 -->
                <div class="d-flex gap-3 mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                        style="width: 40px; height: 40px; background: rgba(239,68,68,0.15); border: 2px solid rgba(239,68,68,0.4); font-weight: 800; color: #ef4444;">4</div>
                    <div>
                        <div class="fw-bold text-white mb-1">Penerbitan Surat Peringatan 3 (SP-3 / Terakhir)</div>
                        <div class="text-muted small" style="line-height: 1.6;">
                            SP-3 adalah peringatan terakhir sebelum pemberhentian. Informasikan kepada karyawan bahwa pelanggaran berikutnya 
                            akan mengakibatkan Pemutusan Hubungan Kerja (PHK). Libatkan serikat pekerja jika ada.
                        </div>
                    </div>
                </div>
                <!-- Step 5 -->
                <div class="d-flex gap-3 mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                        style="width: 40px; height: 40px; background: rgba(6,182,212,0.15); border: 2px solid rgba(6,182,212,0.4); font-weight: 800; color: #06b6d4;">5</div>
                    <div>
                        <div class="fw-bold text-white mb-1">Review dan Musyawarah Bipartit</div>
                        <div class="text-muted small" style="line-height: 1.6;">
                            Lakukan musyawarah bipartit antara manajemen dan karyawan untuk mencapai kesepakatan. 
                            Jika gagal, ajukan ke mediasi di Dinas Ketenagakerjaan setempat. 
                            Semua upaya negosiasi harus terdokumentasi dengan baik.
                        </div>
                    </div>
                </div>
                <!-- Step 6 -->
                <div class="d-flex gap-3 mb-4">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                        style="width: 40px; height: 40px; background: rgba(16,185,129,0.15); border: 2px solid rgba(16,185,129,0.4); font-weight: 800; color: #10b981;">6</div>
                    <div>
                        <div class="fw-bold text-white mb-1">Persetujuan Manajemen Senior</div>
                        <div class="text-muted small" style="line-height: 1.6;">
                            Setelah seluruh proses dilalui, ajukan keputusan PHK ke manajemen senior / direktur untuk persetujuan final. 
                            Hitung hak-hak karyawan: uang pesangon, uang penghargaan masa kerja, dan uang penggantian hak 
                            sesuai ketentuan <strong class="text-white">PP No. 35 Tahun 2021</strong>.
                        </div>
                    </div>
                </div>
                <!-- Step 7 -->
                <div class="d-flex gap-3 mb-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                        style="width: 40px; height: 40px; background: rgba(239,68,68,0.2); border: 2px solid rgba(239,68,68,0.5); font-weight: 800; color: #ef4444;">7</div>
                    <div>
                        <div class="fw-bold text-white mb-1">Eksekusi Pemberhentian</div>
                        <div class="text-muted small" style="line-height: 1.6;">
                            Terbitkan Surat Keputusan (SK) Pemberhentian resmi. Selesaikan administrasi: pembayaran hak, 
                            surat referensi kerja, pengembalian aset perusahaan, pencabutan akses sistem, dan update database karyawan. 
                            Arsipkan seluruh dokumen minimal <strong class="text-white">5 tahun</strong>.
                        </div>
                    </div>
                </div>

                <!-- Legal Reference -->
                <div class="p-3 rounded-3 mt-3" style="background: rgba(99,102,241,0.08); border: 1px solid rgba(99,102,241,0.2);">
                    <div class="fw-bold text-primary small mb-2"><i class="fas fa-gavel me-1"></i> Referensi Hukum</div>
                    <ul class="text-muted small mb-0 ps-3" style="line-height: 1.8;">
                        <li><strong class="text-white">UU No. 13 Tahun 2003</strong> — Ketenagakerjaan (Pasal 151-172)</li>
                        <li><strong class="text-white">UU No. 11 Tahun 2020</strong> — Cipta Kerja (Cluster Ketenagakerjaan)</li>
                        <li><strong class="text-white">PP No. 35 Tahun 2021</strong> — PKWT, Alih Daya, Waktu Kerja, dan PHK</li>
                        <li><strong class="text-white">Kepmenaker No. 150/2000</strong> — Penyelesaian PHK dan Penetapan Pesangon</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    body, html {
        overflow: hidden !important;
    }
    .badge-amber {
        background: rgba(245, 158, 11, 0.1) !important;
        border: 1px solid rgba(245, 158, 11, 0.25) !important;
        color: #f59e0b !important;
    }
    .badge-cyan {
        background: rgba(6, 182, 212, 0.1) !important;
        border: 1px solid rgba(6, 182, 212, 0.25) !important;
        color: #06b6d4 !important;
    }
    .badge-muted {
        background: rgba(100, 116, 139, 0.1) !important;
        border: 1px solid rgba(100, 116, 139, 0.25) !important;
        color: #64748b !important;
    }
    .premium-table th, .premium-table td {
        padding: 8px 10px !important;
        vertical-align: middle;
    }
</style>

<?php require_once 'includes/footer.php'; ?>
