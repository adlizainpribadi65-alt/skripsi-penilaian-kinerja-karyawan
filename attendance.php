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
                <div class="brand-font text-primary tracking-widest small mb-1">MONITORING REAL-TIME</div>
                <h1 class="display-5 fw-bold text-white">Log & Riwayat <span class="shimmer-text">Presensi</span></h1>
                <p class="text-muted fs-5">Pantau aktivitas kehadiran dan pindai identitas personel garmen secara instan.</p>
            </div>
            <div class="glass-pane p-2 px-4 d-flex align-items-center mb-2">
                <div class="status-dot-blink bg-secondary me-2"></div>
                <span class="text-white small fw-bold">Sistem Monitoring Aktif</span>
            </div>
        </div>

        <div class="row g-4 mb-5" id="summary-cards">
            <div class="col-md-3">
                <div class="glass bento-card h-100 stat-card" style="border-left: 4px solid var(--secondary);">
                    <div class="widget-title"><i class="fas fa-users"></i> Hadir Hari Ini</div>
                    <div class="d-flex align-items-end justify-content-between">
                        <div class="metric-value text-white" id="stat-hadir">--</div>
                        <div class="badge-glass badge-emerald">LIVE</div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass bento-card h-100 stat-card" style="border-left: 4px solid var(--accent);">
                    <div class="widget-title"><i class="fas fa-clock"></i> Terlambat</div>
                    <div class="d-flex align-items-end justify-content-between">
                        <div class="metric-value text-accent" id="stat-terlambat">--</div>
                        <i class="fas fa-exclamation-triangle text-accent opacity-25 fs-2"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass bento-card h-100 stat-card">
                    <div class="widget-title"><i class="fas fa-user-slash"></i> Absen / Izin</div>
                    <div class="d-flex align-items-end justify-content-between">
                        <div class="metric-value text-muted" id="stat-absen">--</div>
                        <i class="fas fa-plane-departure text-muted opacity-25 fs-2"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass bento-card h-100 stat-card" style="border-left: 4px solid var(--primary);">
                    <div class="widget-title"><i class="fas fa-shield-halved"></i> Penolakan Akses</div>
                    <div class="d-flex align-items-end justify-content-between">
                        <div class="metric-value text-primary" id="stat-ditolak">--</div>
                        <div class="badge-glass badge-rose">ALARM</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Tabel Riwayat -->
            <div class="col-lg-8 animate-fadeIn stagger-1">
                <div class="glass p-0 overflow-hidden shadow-lg">
                    <div class="p-4 border-bottom border-white border-opacity-10">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="text-white fs-5 fw-bold m-0"><i class="fas fa-history text-primary me-2"></i> Riwayat Kehadiran</h3>
                            <button onclick="exportToCSV()" class="btn-premium py-2 px-3">
                                <i class="fas fa-file-excel"></i> CETAK EXCEL
                            </button>
                        </div>
                        <!-- Filter Bar -->
                        <div class="row g-2">
                            <div class="col-md-5">
                                <input type="text" id="logSearch" class="form-control-glass w-100" placeholder="Cari Nama atau NIK..." onkeyup="filterLogs()">
                            </div>
                            <div class="col-md-4">
                                <select id="statusFilter" class="form-control-glass w-100" onchange="filterLogs()">
                                    <option value="">Semua Status</option>
                                    <option value="Present">Hadir (Present)</option>
                                    <option value="Late">Terlambat (Late)</option>
                                    <option value="Absent">Absen (Absent)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <input type="date" id="dateFilter" class="form-control-glass w-100" onchange="filterLogs()">
                            </div>
                        </div>
                    </div>
                    <div class="premium-table-container">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th class="ps-4">Waktu</th>
                                    <th>Identitas Personel</th>
                                    <th>Status Logging</th>
                                    <th class="text-end pe-4">Klasifikasi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($logs as $log): ?>
                                <tr class="log-row" data-name="<?= strtolower($log['name']) ?>" data-nik="<?= $log['nik'] ?>" data-status="<?= $log['status'] ?>" data-date="<?= $log['date'] ?>">
                                    <td class="ps-4">
                                        <div class="text-white small fw-bold"><?= htmlspecialchars($log['time_in'] ?? '--:--') ?></div>
                                        <div class="text-muted tiny" style="font-size: 0.65rem;"><?= date('d M Y', strtotime($log['date'])) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-white"><?= htmlspecialchars($log['name']) ?></div>
                                        <div class="text-muted tiny opacity-50"><?= htmlspecialchars($log['nik']) ?></div>
                                    </td>
                                    <td>
                                        <?php if (!empty($log['time_out'])): ?>
                                            <span class="badge-glass badge-rose">KELUAR (OUT)</span>
                                        <?php else: ?>
                                            <span class="badge-glass badge-emerald">MASUK (IN)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <?php 
                                        if (!empty($log['time_in'])) {
                                            $hour = (int)date('H', strtotime($log['time_in']));
                                            echo $hour <= 8 ? '<span class="badge-glass badge-emerald py-1 px-2" style="font-size: 0.6rem;">NORMAL</span>' : '<span class="badge-glass badge-rose py-1 px-2" style="font-size: 0.6rem;">TERLAMBAT</span>';
                                        } else {
                                            echo '<span class="text-muted small">ABSEN</span>';
                                        }
                                        ?>
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
                <div class="glass p-4 h-100">
                    <h3 class="text-white fs-5 fw-bold mb-4 d-flex align-items-center">
                        <i class="fas fa-tower-broadcast text-primary me-2"></i> Live Feed Kiosk
                    </h3>
                    
                    <div class="timeline">
                        <?php if (empty($kiosk_scans)): ?>
                            <div class="p-4 text-center glass-pane">
                                <i class="fas fa-radar fa-spin text-muted fs-1 mb-3"></i>
                                <p class="text-muted small">Menunggu aktivitas pindaian...</p>
                            </div>
                        <?php else: ?>
                            <?php foreach($kiosk_scans as $scan): ?>
                            <div class="glass-item p-3 mb-3 animate-fadeIn">
                                <div class="d-flex justify-content-between">
                                    <div class="fw-bold text-white small"><?= htmlspecialchars($scan['name'] ?? 'TIDAK DIKENAL') ?></div>
                                    <div class="text-primary tiny font-monospace"><?= date('H:i:s', strtotime($scan['timestamp'])) ?></div>
                                </div>
                                <div class="text-muted tiny mb-2">ID: <?= htmlspecialchars($scan['nik']) ?></div>
                                <div>
                                    <span class="badge-glass <?= $scan['status'] == 'APPROVED' ? 'badge-emerald' : 'badge-rose' ?> py-0" style="font-size: 0.55rem;">
                                        <?= $scan['status'] == 'APPROVED' ? 'TERMINAL APPROVED' : 'ACCESS DENIED' ?>
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
        <div class="modal-content glass-pane p-1" style="background: rgba(15, 23, 42, 0.98); border: 1px solid var(--primary-glow);">
            <div class="modal-body p-5 text-center">
                <div class="brand-font text-primary mb-4 fs-3">DETAIL IDENTITAS</div>
                <div id="modal-content">
                    <i class="fas fa-circle-notch fa-spin fs-1 text-muted"></i>
                </div>
                <button type="button" class="btn-premium mt-5 w-100 justify-content-center" data-bs-dismiss="modal">TUTUP DATA</button>
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
    alert.innerHTML = `<div class="fw-bold text-accent">${msg}</div><div class="text-white small">Verifikasi log terminal secepatnya.</div>`;
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
        if(row.style.display !== 'none') {
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
    a.setAttribute('download', `REPORT_ATTENDANCE_${new Date().toISOString().slice(0,10)}.csv`);
    a.click();
}

// Start Update Cycle
updateDashboard();
setInterval(updateDashboard, 5000);
</script>
<style>
.status-dot-blink { width: 8px; height: 8px; border-radius: 50%; animation: blink 1.5s infinite; }
@keyframes blink { 0% { opacity: 1; } 50% { opacity: 0.3; } 100% { opacity: 1; } }
.timeline-item { padding-left: 20px; border-left: 2px solid var(--border-glass); position: relative; padding-bottom: 20px; }
.timeline-item::before { content: ''; position: absolute; left: -7px; top: 5px; width: 12px; height: 12px; border-radius: 50%; background: var(--primary); }
</style>
