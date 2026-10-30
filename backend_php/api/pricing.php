<?php
// backend_php/api/pricing.php
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

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'update' || $action === '')) {
        $user = Auth::requirePermission('manage_pricing');
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Device ID is required']);
            exit;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $result = MobileController::updatePrice($id, $body, $user);
        echo json_encode($result);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'history') {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Device ID is required']);
            exit;
        }

        $mobile = MobileController::getById($id);
        if (!$mobile) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Device not found']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'brand' => $mobile['brand'],
            'model' => $mobile['model'],
            'currentPrice' => $mobile['price'],
            'currentCost' => $mobile['costPrice'],
            'margin' => $mobile['profitMargin'],
            'priceHistory' => $mobile['priceHistory'] ?? []
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action for dynamic pricing']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

