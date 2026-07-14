<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

// Array Random Index
$ri_values = [
    1 => 0.00, 2 => 0.00, 3 => 0.58, 4 => 0.90, 5 => 1.12,
    6 => 1.24, 7 => 1.32, 8 => 1.41, 9 => 1.45, 10 => 1.49
];

// Fetch Criteria
$stmt = $pdo->query("SELECT * FROM criteria ORDER BY id ASC");
$criteria = $stmt->fetchAll(PDO::FETCH_ASSOC);
$n = count($criteria);
$criteria_ids = array_column($criteria, 'id');
$id_to_name = array_column($criteria, 'name', 'id');
$id_to_index = array_flip($criteria_ids);

$message = "";
$messageType = "";

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_matrix'])) {
    
    // Save to DB
    $pdo->beginTransaction();
    try {
        $pdo->exec("DELETE FROM perbandingan_ahp");
        
        $insertStmt = $pdo->prepare("INSERT INTO perbandingan_ahp (id_kriteria_1, id_kriteria_2, nilai) VALUES (?, ?, ?)");
        
        foreach ($criteria as $c1) {
            foreach ($criteria as $c2) {
                if ($c1['id'] == $c2['id']) {
                    $val = 1;
                } else if (isset($_POST['val'][$c1['id']][$c2['id']])) {
                    $val = floatval($_POST['val'][$c1['id']][$c2['id']]);
                } else if (isset($_POST['val'][$c2['id']][$c1['id']])) {
                    $val = 1 / floatval($_POST['val'][$c2['id']][$c1['id']]);
                } else {
                    $val = 1; // default fallback
                }
                $insertStmt->execute([$c1['id'], $c2['id'], $val]);
            }
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Terjadi kesalahan database: " . $e->getMessage();
        $messageType = "danger";
    }
}

// Fetch values from DB
$matrix = [];
$stmt = $pdo->query("SELECT * FROM perbandingan_ahp");
$db_matrix = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(count($db_matrix) > 0) {
    foreach($db_matrix as $row) {
        $matrix[$row['id_kriteria_1']][$row['id_kriteria_2']] = floatval($row['nilai']);
    }
} else {
    // defaults
    foreach ($criteria_ids as $id1) {
        foreach ($criteria_ids as $id2) {
            $matrix[$id1][$id2] = 1;
        }
    }
}

// Calculations
$col_sums = [];
foreach ($criteria_ids as $id2) {
    $sum = 0;
    foreach ($criteria_ids as $id1) {
        $sum += isset($matrix[$id1][$id2]) ? $matrix[$id1][$id2] : 1;
    }
    $col_sums[$id2] = $sum;
}

$norm_matrix = [];
$priority_vector = [];
foreach ($criteria_ids as $id1) {
    $row_sum = 0;
    foreach ($criteria_ids as $id2) {
        $val = isset($matrix[$id1][$id2]) ? $matrix[$id1][$id2] : 1;
        $norm_val = $val / ($col_sums[$id2] == 0 ? 1 : $col_sums[$id2]);
        $norm_matrix[$id1][$id2] = $norm_val;
        $row_sum += $norm_val;
    }
    $priority_vector[$id1] = $row_sum / $n;
}

$lambda_max = 0;
foreach ($criteria_ids as $id) {
    $lambda_max += $col_sums[$id] * $priority_vector[$id];
}

$ci = ($n > 1) ? ($lambda_max - $n) / ($n - 1) : 0;
$ri = isset($ri_values[$n]) ? $ri_values[$n] : 1.49;
$cr = ($ri != 0) ? $ci / $ri : 0;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_matrix'])) {
    if ($cr > 0.1) {
        $message = "Data perbandingan tidak konsisten! (CR = ".number_format($cr, 3).")";
        $messageType = "danger";
    } else {
        $message = "Perhitungan Konsisten. Bobot berhasil disimpan.";
        $messageType = "success";
        
        $pdo->beginTransaction();
        try {
            $pdo->exec("DELETE FROM bobot_ahp");
            $insertBobot = $pdo->prepare("INSERT INTO bobot_ahp (id_kriteria, bobot, ci, cr) VALUES (?, ?, ?, ?)");
            $updateCriteria = $pdo->prepare("UPDATE criteria SET weight = ? WHERE id = ?");
            
            foreach ($criteria_ids as $id) {
                // Konversi bobot decimal ke persen (x 100)
                $b = $priority_vector[$id] * 100;
                $insertBobot->execute([$id, $b, $ci, $cr]);
                $updateCriteria->execute([$b, $id]);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Gagal menyimpan ke bobot_ahp: " . $e->getMessage();
            $messageType = "danger";
        }
    }
}

require_once 'includes/header.php';
?>
<div class="app-container" style="height: 100vh; overflow: hidden;">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main d-flex flex-column" style="height: 100vh; overflow: hidden; padding-bottom: 0;">
        <div class="header-section mb-2 flex-shrink-0 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="display-6 fw-bold text-white mb-1"><i class="fas fa-network-wired text-primary me-2"></i> Matriks AHP</h1>
                <p class="text-muted fs-6 mb-0">Input preferensi berskala Saaty (1-9) dan validasi rasio konsistensi.</p>
            </div>
            <?php if ($message): ?>
                 <div class="badge-glass <?= $messageType == 'success' ? 'badge-emerald' : 'badge-rose' ?> py-2 px-4 shadow-glow-mini fs-6">
                     <i class="fas <?= $messageType == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle' ?> me-2"></i> <?= $message ?>
                 </div>
            <?php endif; ?>
        </div>

        <div class="row g-3 flex-grow-1 min-vh-0 pb-3" style="min-height:0; margin-left: 0; margin-right: 0;">
            <!-- Left Panel: Matrix Input (form) -->
            <div class="col-lg-6 h-100 d-flex flex-column p-0 pe-2">
                <form method="POST" class="glass d-flex flex-column h-100 w-100 p-3 m-0 shadow-lg">
                    <div class="d-flex justify-content-between align-items-center mb-2 flex-shrink-0">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="text-white m-0 fw-bold">Pairwise Comparison</h5>
                            <span class="badge bg-primary bg-opacity-25 text-primary" style="font-size: 0.65rem;">Kiri = Baris, Kanan = Kolom</span>
                        </div>
                        <button type="submit" name="save_matrix" class="btn-premium px-3 py-1" style="font-size:0.75rem; border-radius: 6px;">
                            <i class="fas fa-save me-1"></i> HITUNG & SIMPAN
                        </button>
                    </div>
                    
                    <div class="premium-table-container flex-grow-1 overflow-auto mt-2" style="border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                        <table class="premium-table" style="font-size:0.75rem;">
                            <thead>
                                <tr>
                                    <th class="ps-3" style="background:#0f172a">Kriteria</th>
                                    <?php foreach ($criteria as $c): ?>
                                       <th class="text-center" style="background:#0f172a"><?= htmlspecialchars($c['name']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($criteria as $i => $c1): ?>
                                    <tr>
                                        <td class="fw-bold text-primary ps-3" style="background: rgba(255,255,255,0.02)"><?= htmlspecialchars($c1['name']) ?></td>
                                        <?php foreach ($criteria as $j => $c2): 
                                            if ($i < $j):
                                                $val = isset($matrix[$c1['id']][$c2['id']]) ? $matrix[$c1['id']][$c2['id']] : 1;
                                        ?>
                                        <td class="text-center px-1">
                                            <select name="val[<?= $c1['id'] ?>][<?= $c2['id'] ?>]" class="form-control-glass text-center fw-bold" style="font-size: 0.75rem; padding: 2px 4px; width: 100%; min-width: 50px; background: rgba(0,0,0,0.2)">
                                                <option value="9" <?= abs($val - 9) < 0.01 ? 'selected' : '' ?>>9</option>
                                                <option value="8" <?= abs($val - 8) < 0.01 ? 'selected' : '' ?>>8</option>
                                                <option value="7" <?= abs($val - 7) < 0.01 ? 'selected' : '' ?>>7</option>
                                                <option value="6" <?= abs($val - 6) < 0.01 ? 'selected' : '' ?>>6</option>
                                                <option value="5" <?= abs($val - 5) < 0.01 ? 'selected' : '' ?>>5</option>
                                                <option value="4" <?= abs($val - 4) < 0.01 ? 'selected' : '' ?>>4</option>
                                                <option value="3" <?= abs($val - 3) < 0.01 ? 'selected' : '' ?>>3</option>
                                                <option value="2" <?= abs($val - 2) < 0.01 ? 'selected' : '' ?>>2</option>
                                                <option value="1" <?= abs($val - 1) < 0.01 ? 'selected' : '' ?>>1</option>
                                                <option value="0.5" <?= abs($val - 0.5) < 0.01 ? 'selected' : '' ?>>1/2</option>
                                                <option value="0.333333333333" <?= abs($val - (1/3)) < 0.01 ? 'selected' : '' ?>>1/3</option>
                                                <option value="0.25" <?= abs($val - 0.25) < 0.01 ? 'selected' : '' ?>>1/4</option>
                                                <option value="0.2" <?= abs($val - 0.2) < 0.01 ? 'selected' : '' ?>>1/5</option>
                                                <option value="0.166666666667" <?= abs($val - (1/6)) < 0.01 ? 'selected' : '' ?>>1/6</option>
                                                <option value="0.142857142857" <?= abs($val - (1/7)) < 0.01 ? 'selected' : '' ?>>1/7</option>
                                                <option value="0.125" <?= abs($val - 0.125) < 0.01 ? 'selected' : '' ?>>1/8</option>
                                                <option value="0.111111111111" <?= abs($val - (1/9)) < 0.01 ? 'selected' : '' ?>>1/9</option>
                                            </select>
                                        </td>
                                        <?php elseif ($i == $j): ?>
                                        <td class="text-center text-muted fw-bold">1.00</td>
                                        <?php else: ?>
                                        <td class="text-center text-muted" style="font-size:0.75rem opacity: 0.6;">
                                            <?= number_format(isset($matrix[$c1['id']][$c2['id']]) ? $matrix[$c1['id']][$c2['id']] : 1, 2) ?>
                                        </td>
                                        <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <tr style="background: rgba(255,255,255,0.03)">
                                    <td class="fw-bold text-white ps-3">Jumlah Kolom</td>
                                    <?php foreach ($criteria as $c): ?>
                                       <td class="text-center fw-bold text-accent"><?= number_format($col_sums[$c['id']], 2) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>

            <!-- Right Panel: Results -->
            <div class="col-lg-6 h-100 d-flex flex-column gap-3 p-0 ps-2" style="min-height:0">
                <!-- 4 Metrics -->
                <div class="row g-2 flex-shrink-0">
                    <div class="col-3">
                        <div class="glass p-2 px-3 text-center h-100 d-flex flex-column justify-content-center border-start border-2 border-primary">
                             <div class="text-muted font-monospace tracking-widest" style="font-size: 0.55rem; letter-spacing: 1px;">LAMBDA MAX</div>
                             <div class="fw-bold text-white fs-5"><?= number_format($lambda_max, 3) ?></div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="glass p-2 px-3 text-center h-100 d-flex flex-column justify-content-center">
                             <div class="text-muted font-monospace tracking-widest" style="font-size: 0.55rem; letter-spacing: 1px;">CONS. INDEX</div>
                             <div class="fw-bold text-white fs-5"><?= number_format($ci, 3) ?></div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="glass p-2 px-3 text-center h-100 d-flex flex-column justify-content-center">
                             <div class="text-muted font-monospace tracking-widest" style="font-size: 0.55rem; letter-spacing: 1px;">RANDOM INDEX</div>
                             <div class="fw-bold text-white fs-5"><?= number_format($ri, 2) ?></div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="glass p-2 px-3 text-center h-100 d-flex flex-column justify-content-center position-relative <?= $cr > 0.1 ? 'border-danger box-shadow-danger' : 'border-emerald' ?>" style="border-width: 2px; border-style: solid;">
                             <div class="text-muted font-monospace tracking-widest" style="font-size: 0.55rem; letter-spacing: 1px;">CONS. RATIO</div>
                             <div class="fw-bold <?= $cr > 0.1 ? 'text-danger' : 'text-emerald' ?> fs-5"><?= number_format($cr, 3) ?></div>
                             <div class="<?= $cr > 0.1 ? 'text-danger' : 'text-emerald' ?> fw-bold" style="font-size: 0.5rem; position: absolute; bottom: 2px; right: 4px;"><?= $cr > 0.1 ? 'FAIL' : 'OK' ?></div>
                        </div>
                    </div>
                </div>

                <!-- Priority Vector -->
                <div class="glass p-3 flex-shrink-0 animate-fadeIn stagger-1">
                     <h6 class="text-white mb-2 fw-bold" style="font-size: 0.85rem;"><i class="fas fa-chart-pie text-primary me-2"></i> Priority Vector (Bobot Akhir)</h6>
                     <div class="row g-2">
                     <?php foreach ($criteria as $c): ?>
                          <div class="col-6">
                              <div class="d-flex justify-content-between align-items-center p-2 px-3" style="background:rgba(255,255,255,0.05); border-radius: 6px;">
                                    <div class="small fw-bold text-white text-truncate" style="max-width: 70%;"><?= htmlspecialchars($c['name']) ?></div>
                                    <div class="text-primary fw-bold font-monospace"><?= number_format($priority_vector[$c['id']] * 100, 1) ?>%</div>
                              </div>
                          </div>
                     <?php endforeach; ?>
                     </div>
                </div>

                <!-- Normalization Matrix -->
                <div class="glass p-3 flex-grow-1 d-flex flex-column min-vh-0 animate-fadeIn stagger-2 shadow-lg">
                     <h6 class="text-white mb-2 flex-shrink-0 fw-bold" style="font-size: 0.85rem;"><i class="fas fa-border-all text-primary me-2"></i> Matriks Normalisasi</h6>
                     <div class="premium-table-container flex-grow-1 overflow-auto" style="border-radius: 8px; border: 1px solid rgba(255,255,255,0.05);">
                           <table class="premium-table" style="font-size:0.75rem;">
                               <thead>
                                   <tr>
                                       <th class="ps-3" style="background:#0f172a">Kriteria</th>
                                       <?php foreach ($criteria as $c): ?>
                                          <th class="text-center" style="background:#0f172a" title="<?= htmlspecialchars($c['name']) ?>">
                                              <?= substr(htmlspecialchars($c['name']), 0, 8) ?>.
                                          </th>
                                       <?php endforeach; ?>
                                   </tr>
                               </thead>
                               <tbody>
                                   <?php foreach ($criteria as $c1): ?>
                                   <tr>
                                       <td class="text-primary fw-bold ps-3" style="background: rgba(255,255,255,0.02)"><?= htmlspecialchars($c1['name']) ?></td>
                                       <?php foreach ($criteria as $c2): ?>
                                           <td class="text-center fw-medium text-white opacity-75"><?= number_format($norm_matrix[$c1['id']][$c2['id']], 3) ?></td>
                                       <?php endforeach; ?>
                                   </tr>
                                   <?php endforeach; ?>
                               </tbody>
                           </table>
                     </div>
                </div>
            </div>
        </div>
    </main>
</div>

<style>
    body, html {
        overflow: hidden !important;
    }
    /* override padding of table cells specifically for this dense view */
    .premium-table th, .premium-table td {
        padding: 6px 8px !important;
        vertical-align: middle;
    }
    .box-shadow-danger {
        box-shadow: 0 0 10px rgba(239, 68, 68, 0.3) !important;
    }
</style>

<?php require_once 'includes/footer.php'; ?>
