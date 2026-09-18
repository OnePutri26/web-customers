<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "users";

$conn = new mysqli(
    $host,
    $user,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Koneksi database gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");


echo "<pre>";

echo "DATABASE AKTIF:\n";

$result = $conn->query("SELECT DATABASE() AS db");
$row = $result->fetch_assoc();

echo $row['db'];

echo "\n\nKOLOM TABEL CUSTOMERS:\n";

$result = $conn->query("SHOW COLUMNS FROM customers");

while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "\nKOLOM TABEL USERS:\n";

$result = $conn->query("SHOW COLUMNS FROM users");

while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . "\n";
}

echo "</pre>";

exit;