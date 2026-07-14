<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Sinkronisasi data ke mesin SAW
syncAttendanceToSAW($pdo);
syncProductionToSAW($pdo);

// 1. Statistik SAW & Personel
$total_employees = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$performance_avg = $pdo->query("SELECT AVG(score) FROM scores")->fetchColumn() ?? 0;

// 2. Statistik Presensi
$hadir_today = $pdo->query("SELECT COUNT(DISTINCT employee_id) FROM attendance WHERE date = CURDATE() AND status IN ('Present', 'Late')")->fetchColumn() ?? 0;
$attendance_rate = ($total_employees > 0) ? ($hadir_today / $total_employees) * 100 : 0;

// 3. Produksi
$total_production = $pdo->query("SELECT SUM(quantity) FROM production_logs")->fetchColumn() ?? 0;

require_once 'includes/header.php';
?>
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-3 d-flex justify-content-between align-items-end animate-fadeIn">
            <div class="d-flex align-items-center gap-3">
                <div class="dashboard-logo-ring" style="border: 1px solid #e2e8f0; box-shadow: none;">
                    <img src="assets/img/logo_ry.png" alt="Reegyoella" class="dashboard-logo-img">
                </div>
                <div>
                    <h1 class="display-6 fw-bold text-dark mb-1">Halaman Kinerja</h1>
                    <p class="text-muted fs-6 mb-0">Analisis terintegrasi antara Kehadiran, Produksi, dan Kinerja Personel
                        teragregasi.</p>
                </div>
            </div>
        </div>

        <div class="bento-grid">
            <!-- Row 1: Key Metrics -->
            <div class="span-4">
                <div class="card shadow-sm border-0 h-100 p-3 stagger-1"
                    style="border-radius: 12px; border-left: 5px solid #10b981 !important;">
                    <div class="text-secondary fw-bold small mb-2 text-uppercase tracking-widest"><i
                            class="fas fa-users-viewfinder"></i> Personel Aktif</div>
                    <div class="display-5 fw-bold text-dark mb-2" style="line-height: 1;"><?= $total_employees ?></div>
                    <div class="d-flex align-items-center gap-2 mt-auto">
                        <div class="status-dot-blink bg-success" style="width: 8px; height: 8px;"></div>
                        <div class="text-secondary small fw-bold tracking-tighter"><?= $hadir_today ?> HADIR HARI INI
                        </div>
                    </div>
                </div>
            </div>

            <div class="span-4">
                <div class="card shadow-sm border-0 h-100 p-3 stagger-2"
                    style="border-radius: 12px; border-left: 5px solid #3b82f6 !important;">
                    <div class="text-secondary fw-bold small mb-2 text-uppercase tracking-widest"><i
                            class="fas fa-fingerprint"></i> Laju Presensi</div>
                    <div class="display-5 fw-bold text-primary mb-2" style="line-height: 1;">
                        <?= number_format($attendance_rate, 1) ?>%</div>
                    <div class="progress mt-auto bg-light" style="height: 6px; border-radius: 10px;">
                        <div class="progress-bar bg-primary rounded-pill"
                            style="width: <?= $attendance_rate ?>%; transition: width 1.5s ease;"></div>
                    </div>
                </div>
            </div>

            <div class="span-4">
                <div class="card shadow-sm border-0 h-100 p-3 stagger-3"
                    style="border-radius: 12px; border-left: 5px solid #8b5cf6 !important;">
                    <div class="text-secondary fw-bold small mb-2 text-uppercase tracking-widest"><i
                            class="fas fa-gears"></i> Total Produksi</div>
                    <div class="display-5 fw-bold" style="line-height: 1; color: #8b5cf6;">
                        <?= number_format($total_production) ?></div>
                </div>
            </div>

            <!-- Row 2: Analytics & High-End Controls -->
            <div class="span-8">
                <div class="card shadow-sm border-0 rounded-4 overflow-hidden bg-white animate-fadeIn stagger-2"
                    style="min-height: 280px;">
                    <div
                        class="d-flex justify-content-between align-items-center mb-2 p-3 border-bottom border-light border-2">
                        <div class="text-dark fw-bold m-0 fs-6"><i class="fas fa-chart-line text-primary me-2"></i>
                            Indeks Performa Karyawan (Rata-rata Ekosistem)</div>
                    </div>
                    <div style="height: 230px; padding: 10px 20px;">
                        <canvas id="mainPerformanceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="span-4">
                <div class="h-100 animate-fadeIn stagger-3 d-flex flex-column gap-2">
                    <a href="calculate.php"
                        class="btn btn-primary d-flex justify-content-center align-items-center py-3 rounded-4 shadow-sm fw-bold">
                        <i class="fas fa-calculator me-2"></i> Jalankan Mesin SAW
                    </a>
                    <a href="production.php"
                        class="btn btn-light d-flex justify-content-center align-items-center py-3 rounded-4 shadow-sm fw-bold border-0 text-dark">
                        <i class="fas fa-industry me-2"></i> Operasi Produksi
                    </a>

                    <div
                        class="card border-0 shadow-sm rounded-4 p-3 text-center mt-2 d-flex justify-content-center flex-grow-1 bg-white">
                        <div class="text-primary fw-bold tiny tracking-widest mb-1 opacity-75">SKOR PERFORMA GLOBAL
                        </div>
                        <div class="display-4 fw-bold text-dark mb-1 brand-font">
                            <?= number_format($performance_avg, 2) ?>
                        </div>
                        <div class="badge rounded-pill bg-primary bg-opacity-10 text-primary mx-auto py-1 px-3 fw-bold align-self-center mt-auto" style="font-size: 0.75rem;">
                            Indeks Terstandarisasi</div>
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

    .dashboard-logo-ring {
        width: 60px;
        height: 60px;
        min-width: 60px;
        border-radius: 50%;
        background: white;
        overflow: hidden;
        box-shadow: 0 0 30px var(--primary-glow), 0 8px 25px rgba(0, 0, 0, 0.2);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    }

    .dashboard-logo-ring:hover {
        transform: scale(1.08) rotate(3deg);
    }

    .dashboard-logo-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
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