<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

$type = $_GET['type'] ?? 'pdf';

$filter_type = $_GET['filter_type'] ?? 'weekly';
$filter_date = $_GET['filter_date'] ?? date('Y-m-d');
$filter_week = $_GET['filter_week'] ?? date('Y-\WW');
$filter_month = $_GET['filter_month'] ?? date('Y-m');

$start_date = '';
$end_date = '';
$display_period = '';

if ($filter_type === 'daily') {
    $start_date = $filter_date;
    $end_date = $filter_date;
    $display_period = "Harian ($filter_date)";
} elseif ($filter_type === 'monthly') {
    $start_date = $filter_month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $display_period = "Bulanan ($filter_month)";
} else {
    $filter_type = 'weekly';
    if (strpos($filter_week, '-W') !== false) {
        list($year, $week) = explode('-W', $filter_week);
        $dto = new DateTime();
        $dto->setISODate((int)$year, (int)$week);
        $dto->modify('-1 day');
        $start_date = $dto->format('Y-m-d');
        $dto->modify('+6 days');
        $end_date = $dto->format('Y-m-d');
    } else {
        $day_of_week = date('w');
        $start_date = date('Y-m-d', strtotime("-$day_of_week days"));
        $end_date = date('Y-m-d', strtotime($start_date . ' +6 days'));
    }
    $display_period = "Mingguan ($filter_week)";
}

// Fetch Data (Logic copied from scores.php for consistency)
$criteria_list = $pdo->query("SELECT id, name, weight, type FROM criteria ORDER BY id ASC")->fetchAll();
// Fetch All Employees chronologically
$employees_raw = $pdo->query("SELECT id, name FROM employees ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT s.* FROM scores s");
$stmt->execute();
$all_scores_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$consolidated_scores = [];
// Initialize base to ensure ALL employees are shown in chronological order
foreach ($employees_raw as $emp) {
    $consolidated_scores[$emp['id']] = [
        'name' => $emp['name'],
        'scores' => []
    ];
}

foreach ($all_scores_raw as $s) {
    if (isset($consolidated_scores[$s['employee_id']])) {
        $consolidated_scores[$s['employee_id']]['scores'][$s['criteria_id']] = $s['score'];
    }
}

// Calculate SAW Result for preview
$saw_scores = [];
if (!empty($consolidated_scores) && !empty($criteria_list)) {
    $crit_stats = [];
    foreach ($criteria_list as $c) {
        $scores_for_crit = array_column(array_column($consolidated_scores, 'scores'), $c['id']);
        if (!empty($scores_for_crit)) {
            $crit_stats[$c['id']] = [
                'max' => max($scores_for_crit),
                'min' => min($scores_for_crit)
            ];
        }
    }

    foreach ($consolidated_scores as $emp_id => $data) {
        $v_score = 0;
        foreach ($criteria_list as $c) {
            $score = $data['scores'][$c['id']] ?? 0;
            $weight = $c['weight'] / 100;
            if (isset($data['scores'][$c['id']])) {
                $v_score += $score * $weight;
            }
        }
        $saw_scores[$emp_id] = $v_score;
    }
    
    arsort($saw_scores);
    $ranks = [];
    $current_rank = 1;
    foreach ($saw_scores as $e_id => $sc) {
        $ranks[$e_id] = $current_rank++;
    }
}

if ($type == 'excel') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=Rekap_Skor_SAW_" . date('Ymd') . ".xls");
    header("Pragma: no-cache");
    header("Expires: 0");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekapitulasi Skor Gabungan - SAW System</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: center; font-size: 11px; }
        th { background-color: #f2f2f2; font-weight: bold; }
        .name-col { text-align: left; }
        .total-col { font-weight: bold; background-color: #f9f9f9; }
        @media print {
            .no-print { display: none; }
            @page { size: landscape; margin: 1cm; }
        }
    </style>
</head>
<body <?= $type == 'pdf' ? 'onload="window.print()"' : '' ?>>
    <div class="header">
        <h2>REKAPITULASI SKOR GABUNGAN (MATRIX KEPUTUSAN)</h2>
        <p>Sistem Pendukung Keputusan Penilaian Kinerja Karyawan - Metode SAW</p>
        <p>Periode: <?= htmlspecialchars($display_period) ?> (<?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>)</p>
        <p>Tanggal Cetak: <?= date('d/m/Y H:i:s') ?></p>
    </div>



    <table>
            <tr>
                <th class="name-col">Informasi Personel</th>
                <?php foreach ($criteria_list as $c): ?>
                    <th style="background:#eef2ff; font-size:10px; color: #4338ca;" title="<?= htmlspecialchars($c['name']) ?>">
                        <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['weight']) ?>%)
                    </th>
                <?php endforeach; ?>
                <th>Jumlah</th>
                <th>Target Kinerja</th>
                <th>Keterangan</th>
                <th>SAW (Perangkingan)</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            // Iterate using the chronological employees list
            foreach ($employees_raw as $emp): 
                $emp_id = $emp['id'];
                $data = $consolidated_scores[$emp_id];
                $rank_number = $ranks[$emp_id] ?? '-';
            ?>
                <tr>
                    <td class="name-col"><?= htmlspecialchars($data['name']) ?></td>
                    <?php foreach ($criteria_list as $c): ?>
                        <td style="color:#059669; font-weight:bold;">
                            <?php 
                                if (isset($data['scores'][$c['id']])) {
                                    $val = (float)$data['scores'][$c['id']];
                                    $wdt = $val * ($c['weight'] / 100);
                                    echo rtrim(rtrim(number_format($wdt, 2, ',', ''), '0'), ',');
                                } else {
                                    echo '-';
                                }
                            ?>
                        </td>
                    <?php endforeach; ?>
                    <?php
                        $score_raw = $saw_scores[$emp_id] ?? 0;
                        $score100 = round($score_raw, 2);
                        if ($score100 > 70) $ket = 'DIGAJI';
                        elseif ($score100 == 70) $ket = 'DIBINA';
                        else $ket = 'DIKELUARKAN';
                    ?>
                    <td class="total-col"><?= str_replace('.00', '', number_format($score100, 2, ',', '')) ?></td>
                    <td class="total-col" style="color: blue;">70</td>
                    <td class="total-col" style="color: <?= $score100 > 70 ? 'green' : ($score100 == 70 ? 'orange' : 'red') ?>;">
                        <?= strtolower($ket) ?>
                    </td>
                    <td class="total-col" style="font-weight: bold;"><?= $rank_number ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($type == 'pdf'): ?>
        <div style="margin-top: 50px; float: right; text-align: center; width: 250px;">
            <p>Dicetak pada: <?= date('d/m/Y') ?></p>
            <br><br><br>
            <p><b>Administrasi Sistem</b></p>
        </div>
    <?php endif; ?>
</body>
</html>
