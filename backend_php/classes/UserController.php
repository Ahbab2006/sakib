<?php
// backend_php/classes/UserController.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';

class UserController {

    public static function getAll(): array {
        $users = Database::executeQuery('users', [], ['sort' => ['createdAt' => -1]]);
        // Strip hashed passwords from output
        foreach ($users as &$u) {
            unset($u['password']);
        }
        return $users;
    }

    public static function getById(string $id): ?array {
        $user = Database::executeFindOne('users', ['_id' => $id]);
        if ($user) {
            unset($user['password']);
        }
        return $user;
    }

    public static function create(array $data): array {
        $name = trim($data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'sub_admin';
        $permissions = $data['permissions'] ?? [];

        if (empty($name) || empty($email) || empty($password)) {
            throw new InvalidArgumentException("Name, email, and password are required.");
        }

        // Check if user already exists
        $existing = Database::executeFindOne('users', ['email' => $email]);
        if ($existing) {
            throw new Exception("A user with this email already exists.");
        }

        // If admin role, grant all permissions
        if ($role === 'admin') {
            $permissions = array_keys(Auth::PERMISSIONS);
        }

        $document = [
            'name' => $name,
            'email' => $email,
            'password' => Auth::hashPassword($password),
            'role' => $role,
            'permissions' => array_values(array_unique($permissions)),
            'status' => 'active',
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ];

        $id = Database::executeInsert('users', $document);
        $document['_id'] = $id;
        unset($document['password']);
        return $document;
    }

    public static function updatePermissions(string $id, array $permissions): bool {
        $existing = Database::executeFindOne('users', ['_id' => $id]);
        if (!$existing) {
            throw new Exception("User not found.");
        }

        // Validate permissions
        $validKeys = array_keys(Auth::PERMISSIONS);
        $filtered = array_values(array_intersect($permissions, $validKeys));

        Database::executeUpdate('users', ['_id' => $id], [
            '$set' => [
                'permissions' => $filtered,
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ]
        ]);
        return true;
    }

    public static function updateStatus(string $id, string $status): bool {
        if (!in_array($status, ['active', 'suspended'], true)) {
            throw new InvalidArgumentException("Invalid status.");
        }
        return Database::executeUpdate('users', ['_id' => $id], [
            '$set' => ['status' => $status]
        ]) > 0;
    }

    public static function login(string $email, string $password): array {
        $email = strtolower(trim($email));
        $user = Database::executeFindOne('users', ['email' => $email]);
        if (!$user) {
            throw new Exception("Invalid email or password.");
        }

        if (($user['status'] ?? 'active') !== 'active') {
            throw new Exception("This account has been deactivated. Please contact the administrator.");
        }

        if (!Auth::verifyPassword($password, $user['password'] ?? '')) {
            throw new Exception("Invalid email or password.");
        }

        $token = Auth::createToken($user);
        unset($user['password']);

        return [
            'success' => true,
            'token' => $token,
            'user' => $user
        ];
    }
}

