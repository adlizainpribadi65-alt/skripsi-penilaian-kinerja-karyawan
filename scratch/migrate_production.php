<?php
$pdo = new PDO('mysql:host=localhost;dbname=dss_saw', 'root', '');
$sql = "CREATE TABLE IF NOT EXISTS production_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    quantity INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id) ON DELETE CASCADE,
    UNIQUE KEY (employee_id, date)
)";
$pdo->exec($sql);
echo "Database migration successful: production_logs table created.";
?>
