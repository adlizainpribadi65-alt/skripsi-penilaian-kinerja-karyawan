<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Fetch Core Data
$total_employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$total_criteria = $pdo->query("SELECT COUNT(*) FROM criteria")->fetchColumn();

// Fetch Today's Attendance Stats
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT 
                        COUNT(*) as total,
                        COUNT(CASE WHEN status = 'Present' THEN 1 END) as present
                       FROM attendance WHERE date = ?");
$stmt->execute([$today]);
$att_stats = $stmt->fetch();
$presence_rate = ($att_stats['total'] > 0) ? round(($att_stats['present'] / $att_stats['total']) * 100) : 0;

// Fetch Top Performers
$stmt = $pdo->query("SELECT e.name, AVG(s.score) as avg_score 
                     FROM employees e 
                     JOIN scores s ON e.id = s.employee_id 
                     GROUP BY e.id 
                     ORDER BY avg_score DESC 
                     LIMIT 5");
$top_performers = $stmt->fetchAll();

require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <header class="header-section mb-5 animate-fadeIn">
            <div>
                <h1 class="display-4 fw-bold text-white mb-2 brand-font">ANALYTICS <span class="shimmer-text">HUB</span></h1>
                <p class="text-muted fs-5">Decision Support System using Precision SAW Methodology.</p>
            </div>
        </header>

        <div class="bento-grid">
            <div class="col-span-8 animate-fadeIn">
                <div class="glass bento-card shadow-lg h-100">
                    <div class="widget-title"><i class="fas fa-chart-line"></i> System Overview & Analytics</div>
                    <div class="row g-4 mt-2">
                        <div class="col-md-6">
                            <div class="glass-pane p-4">
                                <div class="metric-value"><?= $total_employees ?></div>
                                <div class="text-muted small fw-bold uppercase tracking-widest mt-2">Subjek Aktif</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="glass-pane p-4">
                                <div class="metric-value text-primary"><?= $total_criteria ?></div>
                                <div class="text-muted small fw-bold uppercase tracking-widest mt-2">Parameter Kriteria</div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="glass-pane p-4 mt-2 h-100">
                                <div class="widget-title mb-4">Top Performance Benchmarks</div>
                                <div class="d-flex align-items-end gap-3" style="height: 200px;">
                                    <?php foreach($top_performers as $p): ?>
                                        <div class="flex-grow-1 d-flex flex-column align-items-center">
                                            <div class="w-100 shadow-glow" style="height: <?= ($p['avg_score'] / 100 * 100) ?>%; background: linear-gradient(180deg, var(--primary) 0%, transparent 100%); border-radius: 8px 8px 0 0;"></div>
                                            <div class="tiny text-white-50 mt-2 text-truncate w-100 text-center"><?= htmlspecialchars($p['name']) ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-4 animate-fadeIn stagger-1">
                <div class="glass bento-card shadow-lg h-100">
                    <div class="widget-title"><i class="fas fa-calendar-day"></i> Presensi Hari Ini</div>
                    <div class="text-center py-4">
                        <div class="metric-value" style="font-size: 4rem; color: var(--secondary);"><?= $presence_rate ?>%</div>
                        <div class="badge-glass badge-emerald mt-2">REAL-TIME UPTIME</div>
                    </div>
                    <div class="mt-4 p-4 glass-pane">
                         <div class="widget-title small mb-3">Engine Status</div>
                         <div class="d-flex align-items-center gap-2 mb-2">
                             <div class="status-dot-blink bg-primary"></div>
                             <span class="text-white small fw-bold">SAW Matrix v4.2 Active</span>
                         </div>
                         <div class="text-muted tiny">Sync: Automated / Industrial Terminal</div>
                    </div>
                    <a href="attendance.php" class="btn-premium w-100 justify-content-center mt-auto mt-4">
                        Update Logs <i class="fas fa-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .col-span-8 { grid-column: span 8; }
    .col-span-4 { grid-column: span 4; }
    .status-dot-blink { width: 8px; height: 8px; border-radius: 50%; animation: blink 1.5s infinite; }
    @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.3; } }
</style>

<?php require_once 'includes/footer.php'; ?>
