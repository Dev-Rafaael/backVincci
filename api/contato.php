<?php
require '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Variáveis de ambiente para banco de dados
$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

// Conexão com o banco de dados
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Falha na conexão com o banco de dados."]));
}

// Pegando os dados JSON enviados
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validando os dados
if (empty($data['nome']) || empty($data['email']) || empty($data['telefone']) || empty($data['mensagem'])) {
    die(json_encode(["status" => "error", "message" => "Todos os campos são obrigatórios."]));
}

$nome = $data['nome'];
$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL) ? $data['email'] : null;
$telefone = $data['telefone'];
$mensagem = $data['mensagem'];

// Validando o e-mail
if (!$email) {
    die(json_encode(["status" => "error", "message" => "E-mail inválido."]));
}

// Inserindo dados no banco
$sql = $conn->prepare("INSERT INTO tabela_contato (nome, email, telefone, mensagem) VALUES (?, ?, ?, ?)");
$sql->bind_param("ssss", $nome, $email, $telefone, $mensagem);

$response = [];

if ($sql->execute()) {
    $response['message'] = 'Dados inseridos com sucesso';

    // Envio de e-mail
    $mailResponse = enviarEmail($nome, $email, $telefone, $mensagem);
    $response['email'] = $mailResponse['status'];
    
    if ($mailResponse['status'] === 'error') {
        $response['email_error'] = $mailResponse['message'];
    }

} else {
    $response['message'] = 'Erro ao inserir dados: ' . $conn->error;
}

echo json_encode($response);

// Fechar conexão com o banco
$conn->close();

// Função para envio de e-mail
function enviarEmail($nome, $email, $telefone, $mensagem) {
    $mail = new PHPMailer(true);
    $response = ['status' => 'success'];

    try {
        // Configurações do servidor de e-mail
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST']; 
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USER']; 
        $mail->Password = $_ENV['SMTP_PASS']; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $_ENV['SMTP_PORT']; 

        // Destinatário
        $mail->setFrom($_ENV['SMTP_USER'], 'Fale Conosco'); 
        $mail->addAddress('devrafaael@gmail.com', 'Seu Nome'); 

        // Conteúdo do e-mail
        $mail->isHTML(true);
        $mail->Subject = 'Nova Mensagem do Fale Conosco';
        $mail->Body = "
            <h3>Nova Mensagem Recebida</h3>
            <p><strong>Nome:</strong> $nome</p>
            <p><strong>E-mail:</strong> $email</p>
            <p><strong>Telefone:</strong> $telefone</p>
            <p><strong>Mensagem:</strong></p>
            <p>$mensagem</p>
        ";

        $mail->send();
    } catch (Exception $e) {
        $response['status'] = 'error';
        $response['message'] = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
    }

    return $response;
}
?>
