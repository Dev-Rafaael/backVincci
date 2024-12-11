<?php
require '../vendor/autoload.php'; // Certifique-se de que o autoload do Composer esteja carregado

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Cabeçalhos de segurança
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Força HTTPS
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");

// Verificação de CSRF
session_start();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token'])) {
    echo json_encode(['error' => 'Token CSRF inválido.']);
    exit;
}

$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Conexão falhou: ' . $conn->connect_error]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

// Verificação e validação dos campos obrigatórios
$required_fields = ['cpf', 'nomeCompleto', 'email', 'senha', 'sexo', 'telefone', 'dataNascimento', 'horario', 'bartenders', 'convidados', 'valorTotalFormatado', 'img', 'title', 'description', 'rua', 'numeroEndereco', 'cep', 'bairro', 'cidade', 'estado'];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        echo json_encode(['error' => 'Campo ausente ou vazio: ' . $field]);
        exit;
    }
}

// Sanitize and validate input data
$cpf = $conn->real_escape_string($data['cpf']);
$nomeCompleto = htmlspecialchars($conn->real_escape_string($data['nomeCompleto']), ENT_QUOTES, 'UTF-8'); // Protege contra XSS
$email = filter_var($conn->real_escape_string($data['email']), FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['error' => 'Email inválido']);
    exit;
}
$senha = password_hash($conn->real_escape_string($data['senha']), PASSWORD_BCRYPT); // Hash da senha
$sexo = htmlspecialchars($conn->real_escape_string($data['sexo']), ENT_QUOTES, 'UTF-8');
$telefone = $conn->real_escape_string($data['telefone']);
$dataNascimento = $conn->real_escape_string($data['dataNascimento']);
$horario = $conn->real_escape_string($data['horario']);
$bartenders = (int)$conn->real_escape_string($data['bartenders']);
$convidados = (int)$conn->real_escape_string($data['convidados']);
$valorTotalFormatado = (float)$conn->real_escape_string($data['valorTotalFormatado']);
$img = $conn->real_escape_string($data['img']);
$title = htmlspecialchars($conn->real_escape_string($data['title']), ENT_QUOTES, 'UTF-8');
$description = htmlspecialchars($conn->real_escape_string($data['description']), ENT_QUOTES, 'UTF-8');
$rua = htmlspecialchars($conn->real_escape_string($data['rua']), ENT_QUOTES, 'UTF-8');
$numeroEndereco = (int)$conn->real_escape_string($data['numeroEndereco']);
$cep = $conn->real_escape_string($data['cep']);
$bairro = htmlspecialchars($conn->real_escape_string($data['bairro']), ENT_QUOTES, 'UTF-8');
$cidade = htmlspecialchars($conn->real_escape_string($data['cidade']), ENT_QUOTES, 'UTF-8');
$estado = htmlspecialchars($conn->real_escape_string($data['estado']), ENT_QUOTES, 'UTF-8');

$stmt = $conn->prepare("INSERT INTO usuarios (cpf, nome_completo, email, senha, sexo, telefone, data_nascimento, horario, bartenders, convidados, valor_total_formatado, img, title, description, rua, numero_endereco, cep, bairro, cidade, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssiiisssssssss", $cpf, $nomeCompleto, $email, $senha, $sexo, $telefone, $dataNascimento, $horario, $bartenders, $convidados, $valorTotalFormatado, $img, $title, $description, $rua, $numeroEndereco, $cep, $bairro, $cidade, $estado);

if ($stmt->execute()) {

    // MercadoPago API
    MercadoPago\SDK::setAccessToken($_ENV['MERCADO_PAGO_ACCESS_TOKEN']);

    $preference = new MercadoPago\Preference();

    $item = new MercadoPago\Item();
    $item->title = $title;
    $item->quantity = 1;
    $item->unit_price = $valorTotalFormatado;
    $preference->items = array($item);

    $payer = new MercadoPago\Payer();
    $payer->name = $nomeCompleto;
    $payer->email = $email;
    $payer->identification = array("type" => "CPF", "number" => $cpf);
    $payer->address = array(
        "street_name" => $rua,
        "street_number" => $numeroEndereco,
        "zip_code" => $cep,
        "neighborhood" => $bairro,
        "city" => $cidade,
        "state" => $estado,
        "country" => "BR"
    );

    $preference->payer = $payer;

    $preference->back_urls = array(
        "success" => "http://localhost:5173/",
        "failure" => "http://localhost:5173/Pacotes",
        "pending" => "http://localhost:5173/Pacotes"
    );

    $preference->auto_return = "approved";

    $preference->payment_methods = array(
        "excluded_payment_types" => array(),
        "installments" => 12,
        "default_payment_method_id" => null,
        "included_payment_methods" => array(
            array("id" => "pix"),
            array("id" => "credit_card"),
            array("id" => "debit_card"),
            array("id" => "ticket")
        )
    );

    $preference->save();

    echo json_encode([
        'success' => 'Dados inseridos com sucesso',
        'preferenceId' => $preference->id,
        'init_point' => $preference->init_point,
        'sandbox_init_point' => $preference->sandbox_init_point,
        'payer' => array(
            'name' => $nomeCompleto,
            'address' => array(
                'street_name' => $rua,
                'street_number' => $numeroEndereco,
                'zip_code' => $cep,
                'neighborhood' => $bairro,
                'city' => $cidade,
                'state' => $estado
            )
        )
    ]);
} else {
    echo json_encode(['error' => 'Erro ao inserir dados: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
