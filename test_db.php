<?php
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=autocare_db;charset=utf8mb4",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Database connection successful!";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo "<pre>";
    print_r($tables);
    echo "</pre>";

} catch (PDOException $e) {
    echo "Database error: " . htmlspecialchars($e->getMessage());
}
