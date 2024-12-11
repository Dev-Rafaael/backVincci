<?php
require_once __DIR__ . '/../vendor/autoload.php';
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, GET, OPTIONS"); 
header("Access-Control-Allow-Headers: Content-Type"); 
header("Content-Type: application/json; charset=UTF-8");



// Cabeçalhos de segurança adicionais
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self';");


if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

// Estabelecendo a conexão com o banco de dados usando MySQLi
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Conexão falhou: ' . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents("php://input"));
$searchTerm = $data->searchTerm ?? '';

// Consulta SQL
$sql = "
    SELECT 
        p.id AS pacote_id, 
        p.title, 
        p.description AS pacote_description, 
        p.info, 
        p.price, 
        p.img,
        d.nome AS drink_name,
        d.image AS drink_image,
        d.description AS drink_description
    FROM pacotes p
    LEFT JOIN drinks d ON p.id = d.pacote_id
    WHERE p.title LIKE ?
";

// Preparando a consulta
$stmt = $conn->prepare($sql);

// Associando o parâmetro
$searchTermLike = '%' . $searchTerm . '%';
$stmt->bind_param("s", $searchTermLike);

// Executando a consulta
$stmt->execute();
$result = $stmt->get_result();

$pacotes = [];

while ($row = $result->fetch_assoc()) {
    $pacote_id = $row['pacote_id'];

    if (!isset($pacotes[$pacote_id])) {
        $pacotes[$pacote_id] = [
            "id" => $pacote_id,
            "title" => $row['title'],
            "description" => $row['pacote_description'],
            "info" => $row['info'],
            "price" => $row['price'],
            "img" => $row['img'],
            "drinks" => []
        ];
    }

    if ($row['drink_name']) {
        $pacotes[$pacote_id]['drinks'][] = [
            "name" => $row['drink_name'],
            "image" => $row['drink_image'],
            "description" => $row['drink_description']
        ];
    }
}

$pacotes = array_values($pacotes);

echo json_encode($pacotes);
?>
