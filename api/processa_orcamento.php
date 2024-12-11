<?php
require_once __DIR__ . '/../vendor/autoload.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

header("Content-Type: application/json; charset=UTF-8");

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];



$conn = new mysqli($servername, $username, $password, $dbname);


if ($conn->connect_error) {
    echo json_encode(['error' => 'Falha na conexão: ' . $conn->connect_error]);
    exit;
}


if (!isset($_POST['horario']) || !isset($_POST['convidados']) || !isset($_POST['bartenders']) || !isset($_POST['valorTotal'])) {
    echo json_encode(['error' => 'Dados incompletos']);
    exit;
}

$horario = $_POST['horario'];
$convidados = $_POST['convidados'];
$bartenders = $_POST['bartenders'];
$valorTotal = $_POST['valorTotal'];



if (!is_numeric($convidados) || !is_numeric($bartenders) || !is_numeric($valorTotal)) {
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Insere os dados no banco de dados
$sql = "INSERT INTO orcamentos (horario, convidados, bartenders, valorTotal) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("siid", $horario, $convidados, $bartenders, $valorTotal);

if ($stmt->execute()) {
    echo json_encode(['success' => 'Dados inseridos com sucesso!']);
} else {
    echo json_encode(['error' => 'Erro ao inserir dados: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
