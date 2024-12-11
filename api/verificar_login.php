<?php
require '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, DELETE, OPTIONS, PUT");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");


header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self';");



$servername = $_ENV['DB_HOST'];
$username = $_ENV['DB_USER'];
$password = $_ENV['DB_PASS'];
$dbname = $_ENV['DB_NAME'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Falha na conexão com o banco de dados: ' . $conn->connect_error]));
}

$method = $_SERVER['REQUEST_METHOD'];

// Função para validar email
function validarEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Função para validar CPF (apenas como exemplo, pode ser mais complexo dependendo do formato esperado)
function validarCPF($cpf) {
    return preg_match("/^\d{11}$/", $cpf);
}

// Função para sanitizar dados de texto
function sanitizarTexto($texto) {
    // Remove tags HTML e converte caracteres especiais em entidades HTML
    return htmlspecialchars(trim($texto), ENT_QUOTES, 'UTF-8');
}

// Função para sanitizar números (como telefone e CPF)
function sanitizarNumero($numero) {
    // Remove caracteres não numéricos
    return preg_replace('/[^0-9]/', '', $numero);
}

// Cadastro de Usuário (POST)
if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->email) || empty($data->senha) || empty($data->nomeCompleto) || empty($data->telefone) || empty($data->dataNascimento)) {
        echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos']);
        exit;
    }

    // Sanitização de dados
    $email = sanitizarTexto($data->email);
    $senha = sanitizarTexto($data->senha);
    $nomeCompleto = sanitizarTexto($data->nomeCompleto);
    $telefone = sanitizarNumero($data->telefone);
    $dataNascimento = sanitizarTexto($data->dataNascimento);

    if (!validarEmail($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email inválido']);
        exit;
    }

    // Hash da senha antes de salvar no banco de dados
    $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

    $sql = "INSERT INTO usuarios (nome_completo, email, senha, telefone, data_nascimento) VALUES (?, ?, ?, ?, ?)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssss", $nomeCompleto, $email, $senhaHash, $telefone, $dataNascimento);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Usuário cadastrado com sucesso']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao cadastrar o usuário: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro na preparação da consulta: ' . $conn->error]);
    }
}

// Login de Usuário (POST)
else if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->email) || empty($data->senha)) {
        echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos']);
        exit;
    }

    // Sanitização de dados
    $email = sanitizarTexto($data->email);
    $senha = sanitizarTexto($data->senha);

    if (!validarEmail($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email inválido']);
        exit;
    }

    $sql = "SELECT nome_completo, senha, sexo, email, telefone, data_nascimento FROM usuarios WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if (password_verify($senha, $user['senha'])) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'Login bem-sucedido',
                        'user' => [
                            'email' => $email,
                            'telefone' => $user['telefone'],
                            'nome_completo' => $user['nome_completo'],
                            'data_nascimento' => $user['data_nascimento'],
                            'sexo' => $user['sexo'],
                        ]
                    ]);
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Email ou senha incorretos']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Email ou senha incorretos']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao executar a consulta: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro na preparação da consulta: ' . $conn->error]);
    }
}

// Exclusão de Conta (DELETE)
else if ($method === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"));

    if (empty($data->email) || empty($data->senha)) {
        echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos']);
        exit;
    }

    // Sanitização de dados
    $email = sanitizarTexto($data->email);
    $senha = sanitizarTexto($data->senha);

    if (!validarEmail($email)) {
        echo json_encode(['status' => 'error', 'message' => 'Email inválido']);
        exit;
    }

    $sql = "SELECT senha FROM usuarios WHERE email = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $email);

        if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();

                if (password_verify($senha, $user['senha'])) {
                    $deleteSql = "DELETE FROM usuarios WHERE email = ?";
                    if ($deleteStmt = $conn->prepare($deleteSql)) {
                        $deleteStmt->bind_param("s", $email);
                        if ($deleteStmt->execute()) {
                            echo json_encode(['status' => 'success', 'message' => 'Conta deletada com sucesso']);
                        } else {
                            echo json_encode(['status' => 'error', 'message' => 'Erro ao deletar a conta: ' . $deleteStmt->error]);
                        }
                        $deleteStmt->close();
                    }
                } else {
                    echo json_encode(['status' => 'error', 'message' => 'Email ou senha incorretos']);
                }
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Email ou senha incorretos']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro ao executar a consulta: ' . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Erro na preparação da consulta: ' . $conn->error]);
    }
}

// Atualização de Dados de Usuário (PUT)
else if ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->update) && $data->update === true) {
        $nomeCompleto = $data->nomeCompleto ?? null;
        $email = $data->email ?? null;
        $sexo = $data->sexo ?? null;
        $dataNascimento = $data->dataNascimento ?? null;
        $telefone = $data->telefone ?? null;

        if (!$nomeCompleto || !$email || !$sexo || !$dataNascimento || !$telefone) {
            echo json_encode(['status' => 'error', 'message' => 'Preencha todos os campos']);
            exit;
        }

        // Sanitização de dados
        $nomeCompleto = sanitizarTexto($nomeCompleto);
        $email = sanitizarTexto($email);
        $sexo = sanitizarTexto($sexo);
        $dataNascimento = sanitizarTexto($dataNascimento);
        $telefone = sanitizarNumero($telefone);

        $sql = "UPDATE usuarios SET nome_completo = ?, sexo = ?, data_nascimento = ?, telefone = ? WHERE email = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $nomeCompleto, $sexo, $dataNascimento, $telefone, $email);

            if ($stmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => 'Conta atualizada com sucesso!']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Erro ao atualizar a conta: ' . $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Erro na preparação da consulta: ' . $conn->error]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Atualização não solicitada']);
    }
}

$conn->close();
?>
