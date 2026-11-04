<?php
// AutoCare жобасының MySQL дерекқорына PDO арқылы қосылуы.
$host = 'localhost';
$dbname = 'autocare_db';
$username = 'root';
$password = ''; // XAMPP-та әдепкі root паролі бос болады.

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Құпия сервер қатесін пайдаланушыға ашпаймыз.
    error_log($e->getMessage());
    http_response_code(500);
    exit('Дерекқорға қосылу мүмкін болмады. database.php және MySQL баптауларын тексеріңіз.');
}
