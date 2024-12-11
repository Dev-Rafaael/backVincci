<?php
require_once __DIR__ . '/../vendor/autoload.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json; charset=UTF-8");

// Cabeçalhos de segurança adicionais
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self';");

// Carregando variáveis do ambiente
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Conexão com o banco de dados
$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

$conn = new mysqli($servername, $username, $password, $dbname);

// Verificando falha de conexão
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Falha na conexão com o banco de dados."]));
}

// Consulta SQL utilizando JOINs
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
        d.description AS drink_description,
        ci.image_name AS carousel_image
    FROM pacotes p
    LEFT JOIN drinks d ON p.id = d.pacote_id
    LEFT JOIN carousel_images ci ON p.id = ci.pacote_id
";
$result = $conn->query($sql);

// Verificando se a consulta retornou resultados
$pacotes = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pacote_id = $row['pacote_id'];

        // Garantindo que cada pacote seja único
        if (!isset($pacotes[$pacote_id])) {
            $pacotes[$pacote_id] = [
                "id" => $pacote_id,
                "title" => $row['title'],
                "description" => $row['pacote_description'],
                "info" => $row['info'],
                "price" => $row['price'],
                "img" => $row['img'],
                "drinks" => [],
                "carousel_images" => []
            ];
        }

        // Adicionando bebidas ao pacote (verificação para evitar duplicação)
        if ($row['drink_name'] && !in_array($row['drink_name'], array_column($pacotes[$pacote_id]['drinks'], 'name'))) {
            $pacotes[$pacote_id]['drinks'][] = [
                "name" => $row['drink_name'],
                "image" => $row['drink_image'],
                "description" => $row['drink_description']
            ];
        }

        // Adicionando imagens ao carrossel do pacote (verificação para evitar duplicação)
        if ($row['carousel_image'] && !in_array($row['carousel_image'], $pacotes[$pacote_id]['carousel_images'])) {
            $pacotes[$pacote_id]['carousel_images'][] = $row['carousel_image'];
        }
    }

    // Reindexando o array de pacotes
    $pacotes = array_values($pacotes);
} else {
    // Caso nenhum pacote seja encontrado, retorna uma mensagem
    echo json_encode(["mensagem" => "Nenhum pacote encontrado."]);
    exit;
}

// Enviando a resposta JSON
header('Content-Type: application/json');
echo json_encode($pacotes);

// Fechando a conexão com o banco
$conn->close();
?>
