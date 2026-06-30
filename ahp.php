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
<div class="app-container">
    <?php require_once 'includes/sidebar.php'; ?>

    <main class="content-main">
        <div class="header-section mb-5">
            <div>
                <h1 class="display-5 fw-bold text-white mb-2">Matriks Perbandingan AHP</h1>
                <p class="text-muted fs-5">Tentukan nilai preferensi antar kriteria menggunakan skala Saaty (1-9).</p>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="glass d-flex align-items-center p-4 mb-5 border-start border-4 <?= $messageType == 'success' ? 'border-primary' : 'border-danger' ?>">
                <i class="fas <?= $messageType == 'success' ? 'fa-check-circle text-primary' : 'fa-exclamation-triangle text-danger' ?> me-3 fs-3"></i>
                <div class="fw-medium text-white"><?= $message ?></div>
            </div>
        <?php endif; ?>

        <div class="span-12 mb-5">
             <div class="glass p-4">
                 <h4 class="text-white mb-4"><i class="fas fa-balance-scale"></i> Input Pairwise Comparison</h4>
                 <div class="alert border border-primary bg-primary bg-opacity-10 text-white mb-4 p-3" style="font-size: 0.85rem;">
                    <i class="fas fa-info-circle text-primary me-2"></i> <b>Skala Saaty:</b> 
                    <span class="ms-2"><b>1</b>=Sama, <b>3</b>=Sedikit Lebih, <b>5</b>=Lebih, <b>7</b>=Sangat, <b>9</b>=Mutlak (Kiri lebih penting). Angka Pecahan (1/3, 1/5) adalah kebalikannya.</span>
                 </div>
                 <form method="POST">
                     <div class="premium-table-container mb-4" style="overflow-x: auto; width: 100%;">
                        <table class="premium-table">
                            <thead>
                                <tr>
                                    <th>Kriteria</th>
                                    <?php foreach ($criteria as $c): ?>
                                       <th class="text-center"><?= htmlspecialchars($c['name']) ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($criteria as $i => $c1): ?>
                                    <tr>
                                        <td class="fw-bold text-primary"><?= htmlspecialchars($c1['name']) ?></td>
                                        <?php foreach ($criteria as $j => $c2): 
                                            // Hanya render input dropdown di matriks segitiga atas
                                            if ($i < $j):
                                                $key = $c1['id'] . '_' . $c2['id'];
                                                $val = isset($matrix[$c1['id']][$c2['id']]) ? $matrix[$c1['id']][$c2['id']] : 1;
                                                $rev_val = isset($matrix[$c2['id']][$c1['id']]) ? $matrix[$c2['id']][$c1['id']] : 1;
                                                
                                                // Jika val < 1, berarti yang sebenarnya > 1 adalah kebalikannya
                                                // Untuk kemudahan form HTML, kita gunakan val jika >= 1, dan pecahan string jika < 1
                                                // Solusi yang lebih sederhana: Dropdown dengan opsi 1/9, 1/8... 1 ... 8, 9
                                        ?>
                                        <td class="text-center px-1">
                                            <select name="val[<?= $c1['id'] ?>][<?= $c2['id'] ?>]" class="form-control-glass text-center fw-bold" style="font-size: 0.85rem; padding: 4px; width: 100%; min-width: 65px;">
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
                                        <td class="text-center text-muted">1.00</td>
                                        <?php else: ?>
                                        <td class="text-center text-muted" style="font-size:0.85rem">
                                            <?= number_format(isset($matrix[$c1['id']][$c2['id']]) ? $matrix[$c1['id']][$c2['id']] : 1, 3) ?>
                                        </td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                                
                                <tr style="background: rgba(255,255,255,0.05)">
                                    <td class="fw-bold text-white">Jumlah Kolom</td>
                                    <?php foreach ($criteria as $c): ?>
                                       <td class="text-center fw-bold text-accent"><?= number_format($col_sums[$c['id']], 3) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            </tbody>
                        </table>
                     </div>
                     <button type="submit" name="save_matrix" class="btn-premium px-5 py-3">
                        <i class="fas fa-calculator me-2"></i> Hitung & Validasi AHP
                     </button>
                 </form>
             </div>
        </div>
        
        <hr class="border-secondary my-5 opacity-25">

        <h3 class="text-white mb-4"><i class="fas fa-square-root-variable text-primary me-2"></i> Hasil & Validasi AHP</h3>
        
        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="glass p-4 text-center h-100">
                    <div class="text-muted small uppercase tracking-widest mb-2">Lambda Max</div>
                    <div class="fs-2 text-primary fw-bold font-monospace"><?= number_format($lambda_max, 4) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass p-4 text-center h-100">
                    <div class="text-muted small uppercase tracking-widest mb-2">Consistency Index (CI)</div>
                    <div class="fs-2 text-primary fw-bold font-monospace"><?= number_format($ci, 4) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass p-4 text-center h-100">
                    <div class="text-muted small uppercase tracking-widest mb-2">Random Index (RI)</div>
                    <div class="fs-2 text-white fw-bold font-monospace"><?= number_format($ri, 2) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass p-4 text-center h-100 position-relative <?= $cr > 0.1 ? 'border border-danger' : 'border border-success' ?>">
                    <div class="text-muted small uppercase tracking-widest mb-2">Consistency Ratio (CR)</div>
                    <div class="fs-2 <?= $cr > 0.1 ? 'text-danger' : 'text-success' ?> fw-bold font-monospace"><?= number_format($cr, 4) ?></div>
                    <div class="mt-2 badge-glass <?= $cr > 0.1 ? 'badge-rose' : 'badge-emerald' ?>">
                        <?= $cr > 0.1 ? 'TIDAK KONSISTEN' : 'KONSISTEN' ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Normalization Matrix -->
            <div class="col-md-8">
                <div class="glass p-4">
                    <h5 class="text-white mb-3">Matriks Normalisasi</h5>
                    <div class="premium-table-container">
                        <table class="premium-table" style="font-size:0.85rem">
                            <thead>
                                <tr>
                                    <th>Kriteria</th>
                                    <?php foreach ($criteria as $c): ?>
                                       <th class="text-center"><?= $c['name'] ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($criteria as $c1): ?>
                                <tr>
                                    <td class="text-primary fw-bold"><?= $c1['name'] ?></td>
                                    <?php foreach ($criteria as $c2): ?>
                                        <td class="text-center text-muted"><?= number_format($norm_matrix[$c1['id']][$c2['id']], 4) ?></td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Priority Vector (Weights) -->
            <div class="col-md-4">
                <div class="glass p-4 h-100 border-start border-4 border-primary">
                    <h5 class="text-white mb-3">Priority Vector (Bobot)</h5>
                    <?php foreach ($criteria as $c): ?>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-white small fw-bold uppercase"><?= $c['name'] ?></span>
                            <span class="text-primary fw-bold font-monospace"><?= number_format($priority_vector[$c['id']] * 100, 2) ?>%</span>
                        </div>
                        <div class="progress mb-4" style="height:4px; background: rgba(255,255,255,0.05)">
                            <div class="progress-bar bg-primary shadow-glow" style="width: <?= $priority_vector[$c['id']] * 100 ?>%"></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <br>
    </main>
</div>
<style>
    .span-12 { grid-column: span 12; }
</style>
<?php require_once 'includes/footer.php'; ?>
