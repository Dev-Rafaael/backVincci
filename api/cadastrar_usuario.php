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


$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

$conn = new mysqli($servername, $username, $password, $dbname);

// Verifica conexão com o banco de dados
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Conexão falhou: ' . $conn->connect_error]);
    http_response_code(500);  // Internal Server Error
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    // Verifica se o JSON é válido
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Dados JSON inválidos.']);
        http_response_code(400);  // Bad Request
        exit;
    }

    // Validação dos dados
    $cpf = isset($data['cpf']) ? filter_var($data['cpf'], FILTER_SANITIZE_STRING) : null;
    $nome_completo = isset($data['nome_completo']) ? filter_var($data['nome_completo'], FILTER_SANITIZE_STRING) : null;
    $email = isset($data['email']) ? filter_var($data['email'], FILTER_VALIDATE_EMAIL) : null;
    $senha = isset($data['senha']) ? $data['senha'] : null;
    $sexo = isset($data['sexo']) ? filter_var($data['sexo'], FILTER_SANITIZE_STRING) : null;
    $telefone = isset($data['telefone']) ? filter_var($data['telefone'], FILTER_SANITIZE_STRING) : null;
    $data_nascimento = isset($data['data_nascimento']) ? filter_var($data['data_nascimento'], FILTER_SANITIZE_STRING) : null;
    $rua = isset($data['rua']) ? filter_var($data['rua'], FILTER_SANITIZE_STRING) : null;
    $numero_endereco = isset($data['numero_endereco']) ? filter_var($data['numero_endereco'], FILTER_SANITIZE_NUMBER_INT) : null;
    $cep = isset($data['cep']) ? filter_var($data['cep'], FILTER_SANITIZE_STRING) : null;
    $bairro = isset($data['bairro']) ? filter_var($data['bairro'], FILTER_SANITIZE_STRING) : null;
    $cidade = isset($data['cidade']) ? filter_var($data['cidade'], FILTER_SANITIZE_STRING) : null;
    $estado = isset($data['estado']) ? filter_var($data['estado'], FILTER_SANITIZE_STRING) : null;

    // Verifica se todos os dados obrigatórios foram fornecidos
    if (!$cpf || !$nome_completo || !$email || !$senha || !$sexo || !$telefone || !$data_nascimento || !$rua || !$numero_endereco || !$cep || !$bairro || !$cidade || !$estado) {
        echo json_encode(['success' => false, 'message' => 'Dados obrigatórios ausentes.']);
        http_response_code(400);  // Bad Request
        exit;
    }

    // Valida o comprimento da senha
    if (strlen($senha) < 6) {
        echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 6 caracteres.']);
        http_response_code(400);  // Bad Request
        exit;
    }

    // Gera o hash da senha
    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

    // Prepara e executa a consulta SQL para inserir o usuário
    $stmt = $conn->prepare("INSERT INTO usuarios (cpf, nome_completo, email, senha, sexo, telefone, data_nascimento, rua, numero_endereco, cep, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt) {
        $stmt->bind_param("sssssssssssss", $cpf, $nome_completo, $email, $senha_hash, $sexo, $telefone, $data_nascimento, $rua, $numero_endereco, $cep, $bairro, $cidade, $estado);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Usuário cadastrado com sucesso!']);
            http_response_code(201);  // Created
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar: ' . $stmt->error]);
            http_response_code(500);  // Internal Server Error
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao preparar a consulta: ' . $conn->error]);
        http_response_code(500);  // Internal Server Error
    }
}

$conn->close();
?>
