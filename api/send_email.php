<?php
require_once __DIR__ . '/../vendor/autoload.php';


header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
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
    die("Conexão falhou: " . $conn->connect_error);
}
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

function sendVerificationEmail($email, $nome_completo, $verification_link) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.seuprovedor.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'seu-email@seuprovedor.com';
        $mail->Password = 'sua-senha';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('no-reply@seusite.com', 'Nome do Site');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'Confirmação de E-mail';
        $mail->Body = "<p>Olá $nome_completo,</p><p>Por favor, confirme seu e-mail clicando no link abaixo:</p><p><a href='$verification_link'>$verification_link</a></p>";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
$conn->close();
?>