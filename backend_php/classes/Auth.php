<?php
// backend_php/classes/Auth.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class Auth {
    public const PERMISSIONS = [
        'manage_inventory' => 'Manage Inventory (Add, Edit, Restock Devices)',
        'manage_pricing'   => 'Dynamic Pricing (Update Prices, Discounts, Margins)',
        'record_sales'     => 'Record Sales (POS Terminal, Checkout Invoices)',
        'view_sales_daily' => 'Track Daily Sales Operations & Transactions',
        'view_analytics'   => 'View Centralized Analytics, Profit & Sold Out Items',
        'manage_users'     => 'Manage Sub-Admins & Role Permissions'
    ];

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public static function createToken(array $user): string {
        $payload = [
            'userId' => $user['_id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'role' => $user['role'],
            'permissions' => $user['permissions'] ?? [],
            'exp' => time() + (86400 * 7) // 7 days
        ];
        $json = json_encode($payload);
        return base64_encode($json) . '.' . hash_hmac('sha256', $json, 'mobile-shop-secret-key-2026');
    }

    public static function verifyToken(?string $authHeader): ?array {
        if (!$authHeader) {
            // Check session fallback if in web interface
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            if (isset($_SESSION['user'])) {
                return $_SESSION['user'];
            }
            return null;
        }

        $parts = explode(' ', $authHeader);
        $token = count($parts) === 2 ? $parts[1] : $parts[0];
        $tokenParts = explode('.', $token);
        if (count($tokenParts) !== 2) {
            return null;
        }

        $json = base64_decode($tokenParts[0]);
        $signature = $tokenParts[1];
        if (hash_hmac('sha256', $json, 'mobile-shop-secret-key-2026') !== $signature) {
            return null;
        }

        $payload = json_decode($json, true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    public static function getCurrentUser(): ?array {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? null);
        return self::verifyToken($authHeader);
    }

    public static function requirePermission(string $permission): array {
        $user = self::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Authentication required. Please login.']);
            exit;
        }

        if ($user['role'] === 'admin') {
            return $user; // Admins have full access
        }

        $userPerms = $user['permissions'] ?? [];
        if (!in_array($permission, $userPerms, true)) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => "Access denied. Missing required permission: '$permission'."
            ]);
            exit;
        }

        return $user;
    }

    public static function requireAdmin(): array {
        $user = self::getCurrentUser();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Authentication required. Please login.']);
            exit;
        }

        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Admin privilege required for this action.']);
            exit;
        }

        return $user;
    }
}

