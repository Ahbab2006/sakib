<?php
// backend_php/api/mobiles.php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/MobileController.php';

$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;
$action = $_GET['action'] ?? null;

try {
    if ($method === 'GET') {
        if ($id) {
            $mobile = MobileController::getById($id);
            if (!$mobile) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Device not found']);
                exit;
            }
            echo json_encode(['success' => true, 'mobile' => $mobile]);
            exit;
        }

        $filters = [
            'search' => $_GET['search'] ?? null,
            'brand'  => $_GET['brand'] ?? null,
            'status' => $_GET['status'] ?? null, // 'all', 'in_stock', 'low_stock', 'sold_out'
        ];
        $mobiles = MobileController::getAll($filters);
        echo json_encode(['success' => true, 'count' => count($mobiles), 'mobiles' => $mobiles]);
        exit;
    }

    if ($method === 'POST') {
        $user = Auth::requirePermission('manage_inventory');
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;

        if ($action === 'restock' && $id) {
            $quantity = (int)($body['quantity'] ?? 0);
            $success = MobileController::restock($id, $quantity, $user);
            echo json_encode(['success' => $success, 'message' => "Restocked $quantity units successfully"]);
            exit;
        }

        $mobile = MobileController::create($body, $user);
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'Device created successfully', 'mobile' => $mobile]);
        exit;
    }

    if ($method === 'PUT') {
        $user = Auth::requirePermission('manage_inventory');
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Device ID required for update']);
            exit;
        }
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $success = MobileController::update($id, $body);
        echo json_encode(['success' => $success, 'message' => 'Device updated successfully']);
        exit;
    }

    if ($method === 'DELETE') {
        $user = Auth::requirePermission('manage_inventory');
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Device ID required for deletion']);
            exit;
        }
        $success = MobileController::delete($id);
        echo json_encode(['success' => $success, 'message' => 'Device deleted successfully']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

