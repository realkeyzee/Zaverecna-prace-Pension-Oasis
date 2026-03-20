<?php

header('Content-Type: application/json');

// ========= NASTAVENÍ DB (ANONYMIZOVÁNO) =========
$db_host = 'localhost';
$db_name = 'database_name';
$db_user = 'database_user'; 
$db_pass = 'database_password';    
$db_charset = 'utf8mb4';

$dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Chyba DB']);
    exit();
}

$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';

if (empty($check_in) || empty($check_out)) {
    echo json_encode(['status' => 'no_date']);
    exit();
}

$sql = "SELECT DISTINCT room_id FROM reservations 
        WHERE status != 'cancelled' 
        AND (check_in < ? AND check_out > ?)";

$stmt = $pdo->prepare($sql);
$stmt->execute([$check_out, $check_in]);
$occupied_rooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['occupied_ids' => $occupied_rooms]);