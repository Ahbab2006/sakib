<?php
// backend_php/api/users.php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../classes/UserController.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

try {
    $user = Auth::requirePermission('manage_users');

    if ($method === 'GET') {
        $users = UserController::getAll();
        echo json_encode([
            'success' => true,
            'count' => count($users),
            'users' => $users,
            'availablePermissions' => Auth::PERMISSIONS
        ]);
        exit;
    }

    if ($method === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $newUser = UserController::create($body);
        http_response_code(201);
        echo json_encode(['success' => true, 'message' => 'User created successfully', 'user' => $newUser]);
        exit;
    }

    if ($method === 'PUT') {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'User ID is required']);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        if ($action === 'permissions') {
            $perms = $body['permissions'] ?? [];
            UserController::updatePermissions($id, $perms);
            echo json_encode(['success' => true, 'message' => 'Permissions updated successfully']);
            exit;
        }

        if ($action === 'status') {
            $status = $body['status'] ?? 'active';
            UserController::updateStatus($id, $status);
            echo json_encode(['success' => true, 'message' => "User status updated to $status"]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

