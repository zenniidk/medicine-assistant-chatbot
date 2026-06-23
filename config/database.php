<?php
// Configuración local para XAMPP.
$host = 'localhost';
$database = 'medicinescan';
$username = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$database;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $error) {
    exit('No se pudo conectar con la base de datos. Importa database/medicinescan.sql en phpMyAdmin.');
}
