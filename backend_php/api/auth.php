<?php
// backend_php/api/auth.php
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
require_once __DIR__ . '/../classes/UserController.php';

$action = $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $email = $body['email'] ?? '';
        $password = $body['password'] ?? '';

        $result = UserController::login($email, $password);

        // Also start session if web client
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['user'] = $result['user'];
        $_SESSION['token'] = $result['token'];

        echo json_encode($result);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'me') {
        $currentUser = Auth::getCurrentUser();
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }
        echo json_encode(['success' => true, 'user' => $currentUser]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'permissions') {
        echo json_encode([
            'success' => true,
            'permissions' => Auth::PERMISSIONS
        ]);
        exit;
    }

    if ($action === 'logout') {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        session_destroy();
        echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

