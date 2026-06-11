<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';
checkLogin();

$type = $_GET['type'] ?? 'pdf';
$filter_week = $_GET['filter_week'] ?? date('Y-\WW');
if (strpos($filter_week, '-W') !== false) {
    list($year, $week) = explode('-W', $filter_week);
    $dto = new DateTime();
    $dto->setISODate((int)$year, (int)$week);
    $start_date = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $end_date = $dto->format('Y-m-d');
} else {

    $start_date = date('Y-m-d', strtotime('monday this week'));
    $end_date = date('Y-m-d', strtotime('sunday this week'));
}

// Fetch Data (Logic copied from scores.php for consistency)
$criteria_list = $pdo->query("SELECT id, name, weight, type FROM criteria ORDER BY id ASC")->fetchAll();
$stmt = $pdo->prepare("SELECT s.*, e.name as emp_name, c.name as crit_name 
                               FROM scores s 
                               JOIN employees e ON s.employee_id = e.id 
                               JOIN criteria c ON s.criteria_id = c.id 
                               WHERE DATE(s.created_at) BETWEEN ? AND ?
                               AND s.id IN (
                                  SELECT MAX(id) FROM scores GROUP BY employee_id, criteria_id, DATE(created_at)
                               )
                               ORDER BY e.name ASC, c.id ASC");
$stmt->execute([$start_date, $end_date]);
$all_scores_raw = $stmt->fetchAll();



$consolidated_scores = [];
foreach ($all_scores_raw as $s) {
    if (!isset($consolidated_scores[$s['employee_id']])) {
        $consolidated_scores[$s['employee_id']] = [
            'name' => $s['emp_name'],
            'scores' => []
        ];
    }
    $consolidated_scores[$s['employee_id']]['scores'][$s['criteria_id']] = $s['score'];
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
            if (isset($crit_stats[$c['id']])) {
                $max = $crit_stats[$c['id']]['max'];
                $min = $crit_stats[$c['id']]['min'];
                if ($c['type'] == 'benefit' && $max > 0) {
                    $r = $score / $max;
                } elseif ($c['type'] == 'cost' && $score > 0) {
                    $r = $min / $score;
                } else {
                    $r = 0;
                }
                $v_score += $weight * $r;
            }
        }
        $saw_scores[$emp_id] = $v_score;
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
        <p>Periode: <?= $filter_week ?> (<?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?>)</p>
        <p>Tanggal Cetak: <?= date('d/m/Y H:i:s') ?></p>
    </div>



    <table>
        <thead>
            <tr>
                <th class="name-col" rowspan="2">No</th>
                <th class="name-col" rowspan="2">Nama Karyawan</th>
                <th colspan="<?= count($criteria_list) ?>">Kriteria (Skor Mentah)</th>
                <th rowspan="2">Skor Akhir (V)</th>
            </tr>
            <tr>
                <?php foreach ($criteria_list as $c): ?>
                    <th title="<?= htmlspecialchars($c['name']) ?>">
                        <?= htmlspecialchars($c['name']) ?><br>
                        (<?= $c['type'] ?>)
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            foreach ($consolidated_scores as $emp_id => $data): ?>
                <tr>
                    <td><?= $no++ ?></td>
                    <td class="name-col"><?= htmlspecialchars($data['name']) ?></td>
                    <?php foreach ($criteria_list as $c): ?>
                        <td><?= isset($data['scores'][$c['id']]) ? (float)$data['scores'][$c['id']] : '-' ?></td>
                    <?php endforeach; ?>
                    <td class="total-col"><?= number_format($saw_scores[$emp_id] ?? 0, 4) ?></td>
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
