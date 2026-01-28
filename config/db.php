<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Dhaka');

$dbHost = '127.0.0.1';
$dbName = 'lifex';
$dbUser = 'root';
$dbPass = ''; // XAMPP default

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
  http_response_code(500);
  echo "Database connection failed.";
  exit;
}
