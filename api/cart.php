<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains"); // Força HTTPS
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
require '../bd.php';  

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'POST':
        try {
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Validação dos dados recebidos
            if (!isset($data['pacotes_id']) || !isset($data['users_id'])) {
                echo json_encode(["error" => "Dados inválidos. Pacote e usuário são obrigatórios."]);
                http_response_code(400);  // Bad Request
                exit();
            }

            $pacotes_id = (int)$data['pacotes_id']; // Garantir que o pacote seja um número
            $users_id = (int)$data['users_id']; // Garantir que o usuário seja um número
            $quantidade = isset($data['quantidade']) ? (int)$data['quantidade'] : 1; // Valor padrão de quantidade é 1

            // Verificar se a quantidade é válida
            if ($quantidade <= 0) {
                echo json_encode(["error" => "Quantidade deve ser maior que zero."]);
                http_response_code(400);  // Bad Request
                exit();
            }

            $stmt = $pdo->prepare("INSERT INTO carrinho (users_id, pacotes_id, quantidade) VALUES (?, ?, ?)");
            $stmt->execute([$users_id, $pacotes_id, $quantidade]);
            echo json_encode(["message" => "Item adicionado ao carrinho."]);
            http_response_code(201);  // Created
        } catch (Exception $e) {
            echo json_encode(["error" => "Erro ao adicionar item ao carrinho: " . $e->getMessage()]);
            http_response_code(500);  // Internal Server Error
        }
        break;

    case 'GET':
        try {
            if (!isset($_GET['users_id'])) {
                echo json_encode(["error" => "ID do usuário não fornecido."]);
                http_response_code(400);  // Bad Request
                exit();
            }

            $users_id = (int)$_GET['users_id']; // Garantir que o users_id seja um número
            $stmt = $pdo->prepare("SELECT * FROM carrinho WHERE users_id = ?");
            $stmt->execute([$users_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($items) {
                echo json_encode($items);
                http_response_code(200);  // OK
            } else {
                echo json_encode(["message" => "Carrinho vazio."]);
                http_response_code(200);  // OK
            }
        } catch (Exception $e) {
            echo json_encode(["error" => "Erro ao obter itens do carrinho: " . $e->getMessage()]);
            http_response_code(500);  // Internal Server Error
        }
        break;

    case 'DELETE':
        try {
            if (!isset($_GET['item_id'])) {
                echo json_encode(["error" => "ID do item não fornecido."]);
                http_response_code(400);  // Bad Request
                exit();
            }

            $item_id = (int)$_GET['item_id']; // Garantir que o item_id seja um número
            $stmt = $pdo->prepare("DELETE FROM carrinho WHERE id = ?");
            $stmt->execute([$item_id]);

            if ($stmt->rowCount() > 0) {
                echo json_encode(["message" => "Item removido do carrinho."]);
                http_response_code(200);  // OK
            } else {
                echo json_encode(["message" => "Item não encontrado no carrinho."]);
                http_response_code(404);  // Not Found
            }
        } catch (Exception $e) {
            echo json_encode(["error" => "Erro ao remover item do carrinho: " . $e->getMessage()]);
            http_response_code(500);  // Internal Server Error
        }
        break;

    default:
        echo json_encode(["error" => "Método não suportado."]);
        http_response_code(405);  // Method Not Allowed
}
?>
