<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
checkLogin();

// Fetch Core Data
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(DISTINCT employee_id) FROM attendance WHERE date = ?");
$stmt->execute([$today]);
$total_present = $stmt->fetchColumn();

$total_employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$presence_rate = ($total_employees > 0) ? round(($total_present / $total_employees) * 100) : 0;

$stmt = $pdo->query("SELECT a.*, e.name, e.position, e.nik 
                     FROM attendance a 
                     JOIN employees e ON a.employee_id = e.id 
                     WHERE a.date = CURDATE()
                     ORDER BY a.time_in DESC 
                     LIMIT 20");
$recent_logs = $stmt->fetchAll();

$hour = (int)date('H');
$current_shift = "Shift 1 (Pagi)";
if ($hour >= 14 && $hour < 22) $current_shift = "Shift 2 (Siang)";
if ($hour >= 22 || $hour < 6) $current_shift = "Shift 3 (Malam)";

require_once __DIR__ . '/../includes/header.php';
?>
<div class="app-container">
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-5 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="badge-glass badge-indigo mb-3 font-monospace tracking-widest">COMMAND_CENTER_V5.3</div>
                    <h1 class="display-5 fw-bold text-white mb-2">Dashboard Produksi Industri</h1>
                    <p class="text-muted fs-5">Monitoring real-time alur personel dan keberadaan produksi.</p>
                </div>
                <div class="glass p-3 px-4 d-flex align-items-center gap-4 shadow-glow-mini" style="border: 1px solid var(--primary-glow);">
                    <div class="text-center">
                        <div class="tiny text-primary fw-bold tracking-widest uppercase mb-1">Active Cycle</div>
                        <div class="fw-bold text-white fs-5"><?= $current_shift ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bento-grid">
            <!-- Stats -->
            <div class="col-span-4">
                <div class="glass bento-card h-100 stat-card">
                    <div class="widget-title"><i class="fas fa-users-viewfinder"></i> Floor Presence</div>
                    <div class="metric-value text-primary"><?= $presence_rate ?>%</div>
                    <div class="d-flex align-items-center gap-2 mt-4">
                        <div class="status-dot-blink bg-primary"></div>
                        <div class="text-white small fw-bold tracking-tighter"><?= $total_present ?> WORKERS ON FLOOR</div>
                    </div>
                </div>
            </div>

            <!-- AI Engine Status -->
            <div class="col-span-4">
                <div class="glass bento-card h-100">
                    <div class="widget-title"><i class="fas fa-microchip"></i> Engine Status</div>
                    <div class="d-flex flex-column justify-content-center h-100 pb-3">
                        <div class="fs-4 fw-bold text-white mb-2">SAW v4.2 Internal</div>
                        <div class="badge-glass badge-emerald w-fit d-flex align-items-center gap-2">
                             <i class="fas fa-link animate-pulse"></i> CONNECTED TO KIOSK
                        </div>
                        <div class="text-muted tiny mt-3 font-monospace">SYNC_INTEGRITY: 99.9%</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-span-4">
                <div class="glass bento-card h-100">
                    <div class="widget-title"><i class="fas fa-bolt"></i> Operational Sprints</div>
                    <div class="d-grid gap-2">
                        <a href="kiosk.php" target="_blank" class="btn-premium justify-content-center py-3">
                            <i class="fas fa-display"></i> Open Kiosk Terminal
                        </a>
                        <a href="../attendance.php" class="btn-premium justify-content-center py-3" style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); box-shadow: none;">
                            <i class="fas fa-file-invoice"></i> Audit Full Logs
                        </a>
                    </div>
                </div>
            </div>

            <!-- Live Movement Logs -->
            <div class="col-span-12 animate-fadeIn stagger-1">
                <div class="glass p-0 overflow-hidden shadow-2xl">
                    <div class="p-4 border-bottom border-white border-opacity-10 d-flex justify-content-between align-items-center bg-white bg-opacity-5">
                        <h3 class="text-white fs-5 fw-bold m-0"><i class="fas fa-shoe-prints text-primary me-2"></i> Real-time Movement Logs</h3>
                        <div class="badge-glass badge-indigo">LIVE_FEED</div>
                    </div>
                    <div class="premium-table-container">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th class="ps-5" style="width: 20%">Stempel Waktu</th>
                                    <th style="width: 20%">ID Identitas (NIK)</th>
                                    <th>Informasi Personel</th>
                                    <th>Jabatan / Divisi</th>
                                    <th class="text-end pe-5">Aktivitas</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_logs)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted italic">Belum ada aktivitas tercatat untuk hari ini.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_logs as $log): ?>
                                    <tr>
                                        <td class="ps-5 font-monospace">
                                            <div class="text-white fw-bold"><?= date('H:i:s', strtotime($log['time_in'])) ?></div>
                                            <div class="tiny text-muted uppercase">Today</div>
                                        </td>
                                        <td>
                                            <div class="badge-glass badge-indigo"><?= htmlspecialchars($log['nik']) ?></div>
                                        </td>
                                        <td>
                                            <div class="text-white fw-bold fs-5"><?= htmlspecialchars($log['name']) ?></div>
                                        </td>
                                        <td>
                                            <div class="text-muted small fw-bold uppercase tracking-tighter opacity-75"><?= htmlspecialchars($log['position']) ?></div>
                                        </td>
                                        <td class="pe-5 text-end">
                                            <span class="badge-glass badge-emerald">
                                                <i class="fas fa-arrow-right-to-bracket me-1"></i> ARIVAL_IN
                                            </span>
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
    .col-span-4 { grid-column: span 4; }
    .col-span-12 { grid-column: span 12; }
    .shadow-glow-mini { box-shadow: 0 0 20px var(--primary-glow); }
    .w-fit { width: fit-content; }
    .status-dot-blink { width: 8px; height: 8px; border-radius: 50%; box-shadow: 0 0 10px var(--primary-glow); animation: blink 1.5s infinite; }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
