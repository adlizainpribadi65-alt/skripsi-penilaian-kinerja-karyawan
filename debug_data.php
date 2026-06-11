<?php
require_once 'includes/db.php';
$criteria = $pdo->query("SELECT * FROM criteria")->fetchAll(PDO::FETCH_ASSOC);
echo "Criteria:\n";
print_r($criteria);

$scores = $pdo->query("SELECT s.*, e.name as emp_name, c.name as crit_name 
                      FROM scores s 
                      JOIN employees e ON s.employee_id = e.id 
                      JOIN criteria c ON s.criteria_id = c.id")->fetchAll(PDO::FETCH_ASSOC);
echo "\nScores:\n";
print_r($scores);
?>
