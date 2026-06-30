<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Fetch Latest Attendance with Employee names
$stmt = $pdo->query("
    SELECT a.*, e.name, e.nik, e.position 
    FROM attendance a 
    JOIN employees e ON a.employee_id = e.id 
    ORDER BY a.date DESC, a.id DESC 
    LIMIT 20
");
$logs = $stmt->fetchAll();

// Fetch Kiosk Log (Raw Scans)
$stmt2 = $pdo->query("
    SELECT l.*, e.name, e.nik 
    FROM industrial_logs l 
    LEFT JOIN employees e ON l.employee_id = e.id 
    ORDER BY l.timestamp DESC 
    LIMIT 10
");
$kiosk_scans = $stmt2->fetchAll();

// Add Chart.js to header
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-5 d-flex justify-content-between align-items-end animate-fadeIn">
            <div>
                <div class="text-secondary fw-bold small mb-1 tracking-widest text-uppercase">MONITORING REAL-TIME</div>
                <h1 class="display-5 fw-bold text-dark">Log & Riwayat Presensi</h1>
                <p class="text-muted fs-5">Pantau aktivitas kehadiran dan pindai identitas personel secara instan.</p>
            </div>
            <div class="card border-0 shadow-sm rounded-pill p-2 px-4 d-flex align-items-center mb-2">
                <div class="status-dot-blink bg-success me-2" style="width:10px;height:10px;"></div>
                <span class="text-dark small fw-bold">Sistem Monitoring Aktif</span>
            </div>
        </div>

        <div class="row g-4 mb-5" id="summary-cards">
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100 p-4"
                    style="border-radius: 12px; border-left: 5px solid #10b981 !important;">
                    <div class="text-secondary fw-bold small mb-3 text-uppercase tracking-widest"
                        style="letter-spacing:2px;"><i class="fas fa-users-viewfinder"></i> Hadir Hari Ini</div>
                    <div class="d-flex align-items-end justify-content-between mt-auto">
                        <div class="display-4 fw-bold text-dark" style="line-height: 1;" id="stat-hadir">0</div>
                        <span
                            class="badge rounded-pill bg-success text-success bg-opacity-10 py-2 px-3 fw-bold">LIVE</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100 p-4"
                    style="border-radius: 12px; border-left: 5px solid #ef4444 !important;">
                    <div class="text-secondary fw-bold small mb-3 text-uppercase tracking-widest"
                        style="letter-spacing:2px;"><i class="fas fa-clock"></i> Terlambat</div>
                    <div class="d-flex align-items-end justify-content-between mt-auto">
                        <div class="display-4 fw-bold text-danger" style="line-height: 1;" id="stat-terlambat">0</div>
                        <i class="fas fa-exclamation-triangle text-danger opacity-25 fs-2"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100 p-4"
                    style="border-radius: 12px; border-left: 5px solid #e5e7eb !important;">
                    <div class="text-secondary fw-bold small mb-3 text-uppercase tracking-widest"
                        style="letter-spacing:2px;"><i class="fas fa-plane-slash"></i> Absen / Izin</div>
                    <div class="d-flex align-items-end justify-content-between mt-auto">
                        <div class="display-4 fw-bold text-secondary" style="line-height: 1;" id="stat-absen">0</div>
                        <i class="fas fa-plane-departure text-secondary opacity-25 fs-2"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-0 h-100 p-4"
                    style="border-radius: 12px; border-left: 5px solid #3b82f6 !important;">
                    <div class="text-secondary fw-bold small mb-3 text-uppercase tracking-widest"
                        style="letter-spacing:2px;"><i class="fas fa-shield"></i> Penolakan Akses</div>
                    <div class="d-flex align-items-end justify-content-between mt-auto">
                        <div class="display-4 fw-bold text-primary" style="line-height: 1;" id="stat-ditolak">0</div>
                        <span
                            class="badge rounded-pill bg-danger text-danger bg-opacity-10 py-2 px-3 fw-bold">ALARM</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Tabel Riwayat -->
            <div class="col-lg-8 animate-fadeIn stagger-1">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white">
                    <div class="p-4 border-bottom border-light border-2">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="text-dark fs-5 fw-bold m-0"><i class="fas fa-history text-primary me-2"></i>
                                Riwayat Kehadiran</h3>
                            <button onclick="exportToCSV()" class="btn btn-primary rounded-3 text-white fw-bold px-4">
                                <i class="fas fa-file-excel"></i> CETAK EXCEL
                            </button>
                        </div>
                        <!-- Filter Bar -->
                        <div class="row g-2">
                            <div class="col-md-5">
                                <input type="text" id="logSearch"
                                    class="form-control rounded-3 border-0 bg-light py-2 px-3 fw-medium text-dark"
                                    placeholder="Cari Nama atau NIK..." onkeyup="filterLogs()">
                            </div>
                            <div class="col-md-4">
                                <select id="statusFilter"
                                    class="form-select rounded-3 border-0 bg-light py-2 px-3 fw-medium"
                                    onchange="filterLogs()">
                                    <option value="">Semua Status</option>
                                    <option value="Present">Hadir (Present)</option>
                                    <option value="Late">Terlambat (Late)</option>
                                    <option value="Absent">Absen (Absent)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="dateFilter"
                                    class="form-control rounded-3 border-0 bg-light py-2 px-3 fw-medium"
                                    onchange="filterLogs()">
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-borderless">
                            <thead>
                                <tr class="text-secondary opacity-50 small tracking-widest text-uppercase">
                                    <th class="ps-4 py-3 fw-bold">Waktu</th>
                                    <th class="text-center py-3 fw-bold">Identitas Personel</th>
                                    <th class="text-center py-3 fw-bold">Status Logging</th>
                                    <th class="text-center pe-4 py-3 fw-bold">Klasifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr class="log-row" data-name="<?= strtolower($log['name']) ?>"
                                        data-nik="<?= $log['nik'] ?>" data-status="<?= $log['status'] ?>"
                                        data-date="<?= $log['date'] ?>">
                                        <td class="ps-4">
                                            <div class="text-dark small fw-bold">
                                                <?= htmlspecialchars($log['time_in'] ?? '--:--') ?>
                                            </div>
                                            <div class="text-muted tiny" style="font-size: 0.65rem;">
                                                <?= date('d M Y', strtotime($log['date'])) ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($log['name']) ?></div>
                                            <div class="text-muted tiny opacity-75"><?= htmlspecialchars($log['nik']) ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center">
                                                <?php if (!empty($log['time_out'])): ?>
                                                    <span class="badge rounded-pill bg-danger text-danger bg-opacity-10">KELUAR
                                                        (OUT)</span>
                                                <?php else: ?>
                                                    <span class="badge rounded-pill bg-success text-success bg-opacity-10">MASUK
                                                        (IN)</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-center pe-4">
                                            <div class="d-flex justify-content-center">
                                                <?php
                                                if ($log['status'] === 'Present') {
                                                    echo '<span class="badge rounded-pill bg-success text-success bg-opacity-10 py-2 px-3 fw-bold" style="font-size: 0.65rem;">HADIR</span>';
                                                } elseif ($log['status'] === 'Late') {
                                                    echo '<span class="badge rounded-pill bg-danger text-danger bg-opacity-10 py-2 px-3 fw-bold" style="font-size: 0.65rem;">TERLAMBAT</span>';
                                                } else {
                                                    echo '<span class="badge rounded-pill bg-secondary text-secondary bg-opacity-10 py-2 px-3 fw-bold" style="font-size: 0.65rem;">' . ($log['status'] === 'Absent' ? 'ABSEN' : strtoupper($log['status'])) . '</span>';
                                                }
                                                ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Live Feed Section -->
            <div class="col-lg-4 animate-fadeIn stagger-2">
                <div class="card shadow-sm border-0 rounded-4 p-4 h-100 bg-white">
                    <h3 class="text-dark fs-5 fw-bold mb-4 d-flex align-items-center">
                        <i class="fas fa-tower-broadcast text-primary me-2"></i> Umpan Langsung Kios
                    </h3>

                    <div class="timeline">
                        <?php if (empty($kiosk_scans)): ?>
                            <div class="p-4 text-center rounded-3 bg-light">
                                <i class="fas fa-radar fa-spin text-muted fs-1 mb-3"></i>
                                <p class="text-muted small">Menunggu aktivitas pindaian...</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($kiosk_scans as $scan): ?>
                                <div class="p-3 mb-3 animate-fadeIn" style="border-bottom: 1px solid #f1f5f9;">
                                    <div class="d-flex justify-content-between">
                                        <div class="fw-bold text-dark small">
                                            <?= htmlspecialchars($scan['name'] ?? 'TIDAK DIKENAL') ?>
                                        </div>
                                        <div class="text-primary tiny font-monospace fw-bold">
                                            <?= date('H:i:s', strtotime($scan['timestamp'])) ?>
                                        </div>
                                    </div>
                                    <div class="text-muted tiny mb-2">ID: <?= htmlspecialchars($scan['nik']) ?></div>
                                    <div>
                                        <span
                                            class="badge rounded-pill fw-bold <?= $scan['status'] == 'APPROVED' ? 'bg-success text-success bg-opacity-10' : 'bg-danger text-danger bg-opacity-10' ?> py-1 px-2"
                                            style="font-size: 0.55rem;">
                                            <?= $scan['status'] == 'APPROVED' ? 'TERMINAL DISETUJUI' : 'AKSES DITOLAK' ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/footer.php'; ?>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content glass-pane p-1"
            style="background: rgba(15, 23, 42, 0.98); border: 1px solid var(--primary-glow);">
            <div class="modal-body p-5 text-center">
                <div class="brand-font text-primary mb-4 fs-3">DETAIL IDENTITAS</div>
                <div id="modal-content">
                    <i class="fas fa-circle-notch fa-spin fs-1 text-muted"></i>
                </div>
                <button type="button" class="btn-premium mt-5 w-100 justify-content-center"
                    data-bs-dismiss="modal">TUTUP DATA</button>
            </div>
        </div>
    </div>
</div>

<script>
    let attendanceChart;
    let lastDeniedCount = 0;

    async function updateDashboard() {
        try {
            const response = await fetch('industrial/fetch_attendance.php');
            const data = await response.json();
            if (!data.success) return;

            // Update Stats
            document.getElementById('stat-hadir').innerText = data.stats.hadir;
            document.getElementById('stat-terlambat').innerText = data.stats.terlambat;
            document.getElementById('stat-absen').innerText = data.stats.absen;

            const deniedCount = parseInt(data.stats.ditolak);
            document.getElementById('stat-ditolak').innerText = deniedCount;

            // Alarm Trigger
            if (deniedCount > lastDeniedCount && lastDeniedCount !== 0) {
                triggerAlert("❗ PENOLAKAN AKSES TERDETEKSI");
            }
            lastDeniedCount = deniedCount;
        } catch (e) { console.error("Sync Error", e); }
    }

    function triggerAlert(msg) {
        const alert = document.createElement('div');
        alert.className = 'glass-pane p-4 mb-2 animate-fadeIn pulse-cyan';
        alert.style.position = 'fixed'; alert.style.top = '20px'; alert.style.right = '20px';
        alert.style.borderColor = 'var(--accent)';
        alert.style.zIndex = '9999';
        alert.innerHTML = `<div class="fw-bold text-accent">${msg}</div><div class="text-white small">Segera verifikasi log terminal.</div>`;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }

    function filterLogs() {
        const query = document.getElementById('logSearch').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        const date = document.getElementById('dateFilter').value;
        const rows = document.querySelectorAll('.log-row');

        rows.forEach(row => {
            const name = row.getAttribute('data-name');
            const nik = row.getAttribute('data-nik');
            const rStatus = row.getAttribute('data-status');
            const rDate = row.getAttribute('data-date');

            const matchQuery = name.includes(query) || nik.includes(query);
            const matchStatus = !status || rStatus === status;
            const matchDate = !date || rDate === date;

            row.style.display = (matchQuery && matchStatus && matchDate) ? '' : 'none';
        });
    }

    function exportToCSV() {
        const rows = document.querySelectorAll('.log-row');
        let csv = "Waktu,Nama,NIK,Status\n";
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const time = row.cells[0].innerText.replace(/\n/g, ' ');
                const name = row.cells[1].querySelector('.fw-bold').innerText;
                const nik = row.cells[1].querySelector('.tiny').innerText;
                const status = row.cells[3].innerText;
                csv += `"${time}","${name}","${nik}","${status}"\n`;
            }
        });

        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.setAttribute('href', url);
        a.setAttribute('download', `REPORT_ATTENDANCE_${new Date().toISOString().slice(0, 10)}.csv`);
        a.click();
    }

    // Start Update Cycle
    updateDashboard();
    setInterval(updateDashboard, 5000);
</script>
<style>
    .status-dot-blink {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        animation: blink 1.5s infinite;
    }

    @keyframes blink {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0.3;
        }

        100% {
            opacity: 1;
        }
    }

    .timeline-item {
        padding-left: 20px;
        border-left: 2px solid var(--border-glass);
        position: relative;
        padding-bottom: 20px;
    }

    .timeline-item::before {
        content: '';
        position: absolute;
        left: -7px;
        top: 5px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--primary);
    }
</style>