<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Sinkronisasi data ke mesin SAW
syncAttendanceToSAW($pdo);
syncProductionToSAW($pdo);
syncInventoryToSAW($pdo);

// 1. Statistik SAW & Personel
$total_employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$performance_avg = $pdo->query("SELECT AVG(score) FROM scores")->fetchColumn() ?? 0;

// 2. Statistik Inventori (Aggregated from inventori_reggioella)
$q_inv_masuk = $pdo->query("SELECT SUM(jumlah) FROM inventori_reggioella.barang_masuk")->fetchColumn() ?? 0;
$q_inv_keluar = $pdo->query("SELECT SUM(jumlah) FROM inventori_reggioella.barang_keluar")->fetchColumn() ?? 0;
$total_stok = $q_inv_masuk - $q_inv_keluar;
$masuk_today = $pdo->query("SELECT SUM(jumlah) FROM inventori_reggioella.barang_masuk WHERE DATE(tanggal_masuk) = CURDATE()")->fetchColumn() ?? 0;

// 3. Statistik Presensi
$hadir_today = $pdo->query("SELECT COUNT(DISTINCT employee_id) FROM attendance WHERE date = CURDATE() AND status = 'Present'")->fetchColumn() ?? 0;
$attendance_rate = ($total_employees > 0) ? ($hadir_today / $total_employees) * 100 : 0;

// 4. Produksi
$total_production = $pdo->query("SELECT SUM(quantity) FROM production_logs")->fetchColumn() ?? 0;

require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-5 animate-fadeIn">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <div class="badge-glass badge-indigo mb-3 font-monospace tracking-widest"><i
                            class="fas fa-tower-broadcast text-cyan me-2"></i> REEGIOELLA_ECOSYSTEM_NODE_V5.5</div>
                    <h1 class="display-5 fw-bold text-white mb-2">Pusat Performa <span
                            class="shimmer-text">Ecosystem</span></h1>
                    <p class="text-muted fs-5">Analisis terintegrasi antara Logistik, Produksi, dan Kinerja Personel
                        teragregasi.</p>
                </div>
                <div class="glass p-3 px-4 d-flex align-items-center gap-4 shadow-glow-mini"
                    style="border: 1px solid var(--primary-glow);">
                    <div class="text-center">
                        <div class="tiny text-primary fw-bold tracking-widest uppercase mb-1">Live Sync</div>
                        <div class="badge-glass badge-emerald"><i class="fas fa-link animate-pulse me-2"></i> ACTIVE
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="bento-grid">
            <!-- Row 1: Key Metrics -->
            <div class="span-3">
                <div class="glass bento-card h-100 stagger-1">
                    <div class="widget-title"><i class="fas fa-users-viewfinder"></i> Personel Aktif</div>
                    <div class="metric-value"><?= $total_employees ?></div>
                    <div class="d-flex align-items-center gap-2 mt-4">
                        <div class="status-dot-blink bg-primary"></div>
                        <div class="text-white small fw-bold tracking-tighter"><?= $hadir_today ?> HADIR HARI INI</div>
                    </div>
                </div>
            </div>

            <div class="span-3">
                <div class="glass bento-card h-100 stagger-2">
                    <div class="widget-title"><i class="fas fa-fingerprint"></i> Laju Presensi</div>
                    <div class="metric-value text-secondary"><?= number_format($attendance_rate, 1) ?>%</div>
                    <div class="progress mt-4 bg-white bg-opacity-5" style="height: 6px; border-radius: 10px;">
                        <div class="progress-bar bg-secondary shadow-glow-mini"
                            style="width: <?= $attendance_rate ?>%; transition: width 1.5s ease;"></div>
                    </div>
                </div>
            </div>

            <div class="span-3">
                <div class="glass bento-card h-100 stagger-3">
                    <div class="widget-title"><i class="fas fa-warehouse-full"></i> Volume Logistik</div>
                    <div class="metric-value text-cyan"><?= number_format($total_stok) ?></div>
                    <div class="badge-glass badge-indigo mt-3 w-fit" style="font-size: 0.6rem;">
                        <i class="fas fa-arrow-up me-1"></i> +<?= number_format($masuk_today) ?> Recieved Today
                    </div>
                </div>
            </div>

            <div class="span-3">
                <div class="glass bento-card h-100 stagger-4">
                    <div class="widget-title"><i class="fas fa-gears"></i> Total Produksi</div>
                    <div class="metric-value text-accent"><?= number_format($total_production) ?></div>
                    <div class="text-muted tiny mt-3 font-monospace uppercase opacity-50">Units_Aggregated</div>
                </div>
            </div>

            <!-- Row 2: Analytics & High-End Controls -->
            <div class="span-8">
                <div class="glass bento-card animate-fadeIn stagger-2" style="min-height: 520px;">
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <div class="widget-title mb-0"><i class="fas fa-chart-line"></i> Indeks Performa Karyawan
                            (Ecosystem Average)</div>
                        <div class="d-flex gap-2">
                            <span class="badge-glass badge-indigo">ANALYTICS_V4</span>
                            <span class="badge-glass badge-emerald">OPTIMIZED</span>
                        </div>
                    </div>
                    <div style="height: 420px; padding-bottom: 20px;">
                        <canvas id="mainPerformanceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="span-4">
                <div class="glass bento-card h-100 animate-fadeIn stagger-3">
                    <div class="widget-title mb-4"><i class="fas fa-bolt-lightning text-cyan"></i> Kontrol Pusat SAW
                    </div>
                    <div class="d-grid gap-3 mb-5">
                        <a href="calculate.php" class="btn-premium justify-content-center py-3">
                            <i class="fas fa-calculator"></i> Jalankan SAW Engine
                        </a>
                        <a href="../inventori/dashboard.php" class="btn-premium justify-content-center py-3"
                            style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); box-shadow: none;">
                            <i class="fas fa-boxes-packing"></i> Hub Logistik
                        </a>
                        <a href="production.php" class="btn-premium justify-content-center py-3"
                            style="background: rgba(255,255,255,0.03); border: 1px solid var(--border-glass); box-shadow: none;">
                            <i class="fas fa-industry"></i> Operasi Produksi
                        </a>
                    </div>

                    <div class="glass-pane p-4 text-center mt-5"
                        style="border: 1px dashed var(--primary-glow); background: linear-gradient(180deg, rgba(99, 102, 241, 0.05) 0%, transparent 100%);">
                        <div class="text-primary fw-bold tiny tracking-widest mb-1 opacity-75">GLOBAL PERFORMANCE SCORE
                        </div>
                        <div class="display-4 fw-bold text-white mb-1 brand-font">
                            <?= number_format($performance_avg, 2) ?>
                        </div>
                        <div class="badge-glass badge-indigo py-1">Standardized Index</div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    .w-fit {
        width: fit-content;
    }

    .shadow-glow-mini {
        box-shadow: 0 0 15px var(--primary-glow);
    }

    .status-dot-blink {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        box-shadow: 0 0 10px var(--primary);
        animation: blink 1.5s infinite;
    }

    @keyframes blink {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.3;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('mainPerformanceChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.4)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['SEN', 'SEL', 'RAB', 'KAM', 'JUM', 'SAB', 'MIN'],
            datasets: [{
                data: [0.75, 0.82, 0.79, 0.91, 0.88, 0.94, 0.96],
                borderColor: '#6366f1',
                backgroundColor: gradient,
                fill: true,
                tension: 0.5,
                borderWidth: 4,
                pointRadius: 0,
                pointHoverRadius: 8,
                pointHoverBackgroundColor: '#fff',
                pointHoverBorderColor: '#6366f1',
                pointHoverBorderWidth: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.02)' }, border: { display: false }, ticks: { color: '#64748b', font: { size: 10, weight: '700' } } },
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#64748b', font: { size: 10, weight: '700' } } }
            }
        }
    });
</script>

<?php require_once 'includes/footer.php'; ?>