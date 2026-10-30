<?php
// backend_php/api/sales.php
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
require_once __DIR__ . '/../classes/SalesController.php';

$action = $_GET['action'] ?? '';

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $user = Auth::requirePermission('record_sales');
        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $result = SalesController::createSale($body, $user);
        http_response_code(201);
        echo json_encode($result);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Daily Sales Operations tracker
        if ($action === 'daily') {
            $user = Auth::requirePermission('view_sales_daily');
            $date = $_GET['date'] ?? null;
            $summary = SalesController::getDailyOperationsSummary($date);
            echo json_encode(['success' => true, 'data' => $summary]);
            exit;
        }

        // List Sales
        $user = Auth::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Authentication required']);
            exit;
        }

        $filters = [
            'search' => $_GET['search'] ?? null,
            'date'   => $_GET['date'] ?? null,
            'today'  => isset($_GET['today']),
            'limit'  => (int)($_GET['limit'] ?? 100)
        ];

        $sales = SalesController::getSales($filters, $user);
        echo json_encode(['success' => true, 'count' => count($sales), 'sales' => $sales]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

