<?php

declare(strict_types=1);

$host = 'localhost';
$database = 'medicinescan';
$username = 'root';
$password = '';

$pdo = new PDO(
    "mysql:host=$host;dbname=$database;charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);
