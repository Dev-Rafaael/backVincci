<?php
// Conexão com o banco de dados
$host = "localhost";
$dbname = "vinccipub";
$username = "vincciBD";
$password = "v*incci***P*u**B2024";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados: " . $e->getMessage());
}
?>
