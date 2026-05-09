<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// --- Filter Management ---
$mode = $_GET['mode'] ?? 'day'; // day, week, month, year
$selected_date = $_GET['date'] ?? date('Y-m-d');
$selected_month = $_GET['month'] ?? date('m');
$selected_year = $_GET['year'] ?? date('Y');
$selected_week = $_GET['week'] ?? date('W'); // Week of the year

$where_clause = "";
$title_suffix = "";

switch ($mode) {
    case 'week':
        $where_clause = "WHERE WEEK(date, 1) = :week AND YEAR(date) = :year AND DAYOFWEEK(date) != 1"; 
        $title_suffix = "Minggu ke-" . $selected_week . ", " . $selected_year;
        $params = ['week' => $selected_week, 'year' => $selected_year];
        break;
    case 'month':
        $where_clause = "WHERE MONTH(date) = :month AND YEAR(date) = :year";
        $months_indo = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        $title_suffix = $months_indo[(int)$selected_month] . " " . $selected_year;
        $params = ['month' => $selected_month, 'year' => $selected_year];
        break;
    case 'year':
        $where_clause = "WHERE YEAR(date) = :year";
        $title_suffix = "Tahun " . $selected_year;
        $params = ['year' => $selected_year];
        break;
    case 'day':
    default:
        $where_clause = "WHERE date = :date";
        $title_suffix = date('d M Y', strtotime($selected_date));
        $params = ['date' => $selected_date];
        break;
}

// 1. Fetch Aggregated Stats
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present,
        SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) as late,
        SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) as absent
    FROM attendance
    $where_clause
");
$stmt->execute($params);
$stats = $stmt->fetch();

// 2. Fetch Detailed Logs
$stmt = $pdo->prepare("
    SELECT a.*, e.name, e.nik, e.position 
    FROM attendance a
    JOIN employees e ON a.employee_id = e.id
    $where_clause
    ORDER BY a.date DESC, a.time_in DESC
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// 3. Fetch Chart Data (Trend)
$stmt = $pdo->prepare("
    SELECT date as label, COUNT(*) as count 
    FROM attendance 
    $where_clause 
    GROUP BY date 
    ORDER BY date ASC
");
$stmt->execute($params);
$chart_data = $stmt->fetchAll();

$extra_head = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-5 d-flex justify-content-between align-items-end animate-fadeIn">
            <div>
                <div class="brand-font text-primary tracking-widest small mb-1">ARCHIVE & ANALYTICS</div>
                <h1 class="display-5 fw-bold text-white mb-2">Arsip <span class="shimmer-text">Historis</span></h1>
                <p class="text-muted fs-5">Laporan komprehensif kehadiran periode <strong><?= $title_suffix ?></strong>.</p>
            </div>
            <div class="d-flex gap-2 mb-2">
                <button onclick="window.print()" class="btn-premium px-4"><i class="fas fa-print me-2"></i> PDF</button>
                <button onclick="exportCSV()" class="btn-premium px-4" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); box-shadow: none;"><i class="fas fa-file-excel me-2"></i> CSV</button>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="glass p-4 mb-5 animate-fadeIn" style="border-radius: 20px;">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="text-muted tiny fw-bold mb-2 uppercase tracking-widest">MODE ANALISIS</label>
                    <select name="mode" class="form-control-glass w-100" onchange="this.form.submit()">
                        <option value="day" <?= $mode == 'day' ? 'selected' : '' ?>>Harian</option>
                        <option value="week" <?= $mode == 'week' ? 'selected' : '' ?>>Mingguan</option>
                        <option value="month" <?= $mode == 'month' ? 'selected' : '' ?>>Bulanan</option>
                        <option value="year" <?= $mode == 'year' ? 'selected' : '' ?>>Tahunan</option>
                    </select>
                </div>

                <?php if($mode == 'day'): ?>
                    <div class="col-md-3">
                        <label class="text-muted tiny fw-bold mb-2 uppercase tracking-widest">PILIH TANGGAL</label>
                        <input type="date" name="date" class="form-control-glass w-100" value="<?= $selected_date ?>" onchange="this.form.submit()">
                    </div>
                <?php elseif($mode == 'week'): ?>
                    <div class="col-md-2">
                        <label class="text-muted tiny fw-bold mb-2 uppercase tracking-widest">MINGGU KE-</label>
                        <select name="week" class="form-control-glass w-100" onchange="this.form.submit()">
                            <?php for($i=1; $i<=52; $i++) echo "<option value='$i' ".($selected_week == $i ? 'selected' : '').">$i</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="text-muted tiny fw-bold mb-2 uppercase tracking-widest">TAHUN</label>
                        <select name="year" class="form-control-glass w-100" onchange="this.form.submit()">
                            <?php for($i=date('Y'); $i>=2023; $i--) echo "<option value='$i' ".($selected_year == $i ? 'selected' : '').">$i</option>"; ?>
                        </select>
                    </div>
                <?php elseif($mode == 'month'): ?>
                    <div class="col-md-3">
                        <label class="text-muted tiny fw-bold mb-2 uppercase tracking-widest">BULAN</label>
                        <select name="month" class="form-control-glass w-100" onchange="this.form.submit()">
                            <?php foreach($months_indo as $num => $name) echo "<option value='$num' ".($selected_month == $num ? 'selected' : '').">$name</option>"; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="text-muted tiny fw-bold mb-2 uppercase tracking-widest">TAHUN</label>
                        <select name="year" class="form-control-glass w-100" onchange="this.form.submit()">
                            <?php for($i=date('Y'); $i>=2023; $i--) echo "<option value='$i' ".($selected_year == $i ? 'selected' : '').">$i</option>"; ?>
                        </select>
                    </div>
                <?php elseif($mode == 'year'): ?>
                    <div class="col-md-3">
                        <label class="text-muted tiny fw-bold mb-2 uppercase tracking-widest">TAHUN ANALISIS</label>
                        <select name="year" class="form-control-glass w-100" onchange="this.form.submit()">
                            <?php for($i=date('Y'); $i>=2023; $i--) echo "<option value='$i' ".($selected_year == $i ? 'selected' : '').">$i</option>"; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="col-md"></div>
                <div class="col-md-auto">
                    <a href="history.php" class="btn-premium px-3 py-2" style="background: rgba(244, 63, 94, 0.05); color: var(--accent); border-color: rgba(244, 63, 94, 0.1);"><i class="fas fa-undo"></i> Reset</a>
                </div>
            </form>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-lg-3">
                <div class="glass p-4 stat-card h-100 shadow-glow-mini" style="border-top: 2px solid var(--primary);">
                    <div class="widget-title mb-2">Total Rekaman</div>
                    <div class="display-6 text-white fw-bold mb-0"><?= number_format($stats['total']) ?></div>
                    <div class="tiny text-muted mt-2 font-monospace tracking-widest">LOGS_PROCESSED</div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="glass p-4 stat-card h-100" style="border-top: 2px solid var(--secondary);">
                    <div class="widget-title mb-2">Hadir On-Time</div>
                    <div class="display-6 text-emerald fw-bold mb-0"><?= number_format($stats['present']) ?></div>
                    <div class="tiny text-emerald mt-2"><i class="fas fa-circle-check me-1"></i> VERIFIED_ACCESS</div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="glass p-4 stat-card h-100" style="border-top: 2px solid var(--accent);">
                    <div class="widget-title mb-2">Terlambat</div>
                    <div class="display-6 text-accent fw-bold mb-0"><?= number_format($stats['late']) ?></div>
                    <div class="tiny text-accent mt-2"><i class="fas fa-clock me-1"></i> FLAG_DELAYED</div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="glass p-4 stat-card h-100" style="border-top: 2px solid var(--text-dim);">
                    <div class="widget-title mb-2">Mangkir / Izin</div>
                    <div class="display-6 text-white fw-bold mb-0 opacity-50"><?= number_format($stats['absent']) ?></div>
                    <div class="tiny text-muted mt-2"><i class="fas fa-user-slash me-1"></i> RECORD_MISSING</div>
                </div>
            </div>
        </div>

        <div class="glass p-5 mb-5 shadow-lg animate-fadeIn stagger-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="text-white fs-5 fw-bold mb-0"><i class="fas fa-chart-line text-primary me-2"></i> Fluktuasi Aktivitas Presensi</h3>
                <span class="badge-glass badge-indigo">Chart Mode: Line / Timeseries</span>
            </div>
            <div style="height: 350px;">
                <canvas id="historyTrendChart"></canvas>
            </div>
        </div>

        <div class="glass p-0 overflow-hidden shadow-2xl animate-fadeIn stagger-2">
            <div class="p-4 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center">
                <h3 class="text-white fs-5 fw-bold mb-0"><i class="fas fa-table-list text-primary me-2"></i> Rincian Log Sesi Terarsip</h3>
                <div class="col-md-3">
                    <input type="text" id="tableSearch" class="form-control-glass ps-4" placeholder="Cari Personel..." onkeyup="filterTable()">
                </div>
            </div>
            <div class="premium-table-container">
                <table class="premium-table" id="historyTable">
                    <thead>
                        <tr>
                            <th class="ps-5">Stempel Waktu</th>
                            <th>Identitas Personel</th>
                            <th>Jam Masuk</th>
                            <th>Jam Keluar</th>
                            <th class="text-end pe-5">Status Akhir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                        <tr class="log-row" data-name="<?= strtolower($log['name']) ?>">
                            <td class="ps-5">
                                <div class="text-white fw-bold"><?= date('d M Y', strtotime($log['date'])) ?></div>
                                <div class="text-muted tiny uppercase tracking-widest"><?= date('l', strtotime($log['date'])) ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-white"><?= htmlspecialchars($log['name']) ?></div>
                                <div class="text-muted small opacity-50"><?= $log['nik'] ?> • <?= $log['position'] ?></div>
                            </td>
                            <td class="text-white font-monospace"><?= $log['time_in'] ?? '--:--' ?></td>
                            <td class="text-white font-monospace"><?= $log['time_out'] ?? '--:--' ?></td>
                            <td class="text-end pe-5">
                                <?php 
                                switch($log['status']) {
                                    case 'Present': echo '<span class="badge-glass badge-emerald"><i class="fas fa-check-circle me-1"></i> HADIR</span>'; break;
                                    case 'Late': echo '<span class="badge-glass badge-amber"><i class="fas fa-clock me-1"></i> LAMBAT</span>'; break;
                                    case 'Absent': echo '<span class="badge-glass badge-rose"><i class="fas fa-circle-xmark me-1"></i> ALPA</span>'; break;
                                    default: echo '<span class="badge-glass badge-indigo">PENDING</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($logs)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted italic"> 📁 Tidak ditemukan rekaman data untuk periode ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('historyTrendChart').getContext('2d');
    const chartLabels = <?= json_encode(array_map(fn($d) => $d['label'], $chart_data)) ?>;
    const chartValues = <?= json_encode(array_map(fn($d) => $d['count'], $chart_data)) ?>;

    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels.map(l => {
                const d = new Date(l);
                return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
            }),
            datasets: [{
                label: 'Capaian Presensi',
                data: chartValues,
                borderColor: '#6366f1',
                borderWidth: 4,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#6366f1',
                pointBorderWidth: 2,
                pointRadius: 5,
                fill: true,
                backgroundColor: gradient
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.03)' }, ticks: { color: 'rgba(255,255,255,0.4)', font: { weight: 'bold' } } },
                x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.4)', font: { weight: 'bold' } } }
            }
        }
    });
});

function filterTable() {
    const query = document.getElementById('tableSearch').value.toLowerCase();
    const rows = document.querySelectorAll('.log-row');
    rows.forEach(row => {
        const name = row.getAttribute('data-name');
        row.style.display = name.includes(query) ? '' : 'none';
    });
}

function exportCSV() {
    const table = document.getElementById('historyTable');
    let csv = [];
    const rows = table.querySelectorAll('tr');
    for (let i = 0; i < rows.length; i++) {
        const row = [], cols = rows[i].querySelectorAll('td, th');
        for (let j = 0; j < cols.length; j++) row.push('"' + cols[j].innerText.trim().replace(/"/g, '""') + '"');
        csv.push(row.join(','));
    }
    const blob = new Blob([csv.join("\n")], { type: 'text/csv' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = "LOG_HISTORY_<?= str_replace([' ', ','], '_', $title_suffix) ?>.csv";
    document.body.appendChild(a);
    a.click();
}
</script>
<style>
    .shadow-glow-mini { box-shadow: 0 0 20px var(--primary-glow); }
</style>
