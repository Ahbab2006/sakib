<?php
// backend_php/classes/MobileController.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';

class MobileController {

    public static function getAll(array $filters = []): array {
        $mongoFilter = [];

        // Search by brand or model
        if (!empty($filters['search'])) {
            $regex = new MongoDB\BSON\Regex($filters['search'], 'i');
            $mongoFilter['$or'] = [
                ['brand' => $regex],
                ['model' => $regex],
                ['variant' => $regex]
            ];
        }

        // Filter by brand
        if (!empty($filters['brand'])) {
            $mongoFilter['brand'] = $filters['brand'];
        }

        // Filter by stock status
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'sold_out') {
                $mongoFilter['stock'] = ['$lte' => 0];
            } elseif ($filters['status'] === 'low_stock') {
                $mongoFilter['$and'] = [
                    ['stock' => ['$gt' => 0]],
                    ['stock' => ['$lte' => 3]]
                ];
            } elseif ($filters['status'] === 'in_stock') {
                $mongoFilter['stock'] = ['$gt' => 3];
            }
        }

        $options = ['sort' => ['updatedAt' => -1]];
        $mobiles = Database::executeQuery('mobiles', $mongoFilter, $options);

        // Compute status for each mobile
        foreach ($mobiles as &$mobile) {
            $stock = (int)($mobile['stock'] ?? 0);
            $minAlert = (int)($mobile['minStockAlert'] ?? 3);
            if ($stock <= 0) {
                $mobile['status'] = 'sold_out';
            } elseif ($stock <= $minAlert) {
                $mobile['status'] = 'low_stock';
            } else {
                $mobile['status'] = 'in_stock';
            }

            // Calculate profit margin %
            $sellingPrice = (float)($mobile['price'] ?? 0);
            $costPrice = (float)($mobile['costPrice'] ?? ($sellingPrice * 0.8));
            $mobile['costPrice'] = $costPrice;
            $mobile['profitMargin'] = $sellingPrice > 0 ? round((($sellingPrice - $costPrice) / $sellingPrice) * 100, 2) : 0;
        }

        return $mobiles;
    }

    public static function getById(string $id): ?array {
        $mobile = Database::executeFindOne('mobiles', ['_id' => $id]);
        if ($mobile) {
            $stock = (int)($mobile['stock'] ?? 0);
            $minAlert = (int)($mobile['minStockAlert'] ?? 3);
            $mobile['status'] = $stock <= 0 ? 'sold_out' : ($stock <= $minAlert ? 'low_stock' : 'in_stock');
            $sellingPrice = (float)($mobile['price'] ?? 0);
            $costPrice = (float)($mobile['costPrice'] ?? ($sellingPrice * 0.8));
            $mobile['costPrice'] = $costPrice;
            $mobile['profitMargin'] = $sellingPrice > 0 ? round((($sellingPrice - $costPrice) / $sellingPrice) * 100, 2) : 0;
        }
        return $mobile;
    }

    public static function create(array $data, array $user): array {
        $brand = trim($data['brand'] ?? '');
        $model = trim($data['model'] ?? '');
        $variant = trim($data['variant'] ?? '');
        $price = (float)($data['price'] ?? 0);
        $costPrice = isset($data['costPrice']) ? (float)$data['costPrice'] : round($price * 0.8, 2);
        $stock = (int)($data['stock'] ?? 0);
        $minStockAlert = (int)($data['minStockAlert'] ?? 3);
        $imageUrl = trim($data['imageUrl'] ?? '');

        if (empty($brand) || empty($model) || $price <= 0) {
            throw new InvalidArgumentException('Brand, model, and positive price are required.');
        }

        $document = [
            'brand' => $brand,
            'model' => $model,
            'variant' => $variant,
            'price' => $price,
            'costPrice' => $costPrice,
            'stock' => $stock,
            'sold' => 0,
            'minStockAlert' => $minStockAlert,
            'imageUrl' => $imageUrl,
            'priceHistory' => [
                [
                    'date' => date('Y-m-d H:i:s'),
                    'oldPrice' => 0,
                    'newPrice' => $price,
                    'costPrice' => $costPrice,
                    'margin' => $price > 0 ? round((($price - $costPrice) / $price) * 100, 2) : 0,
                    'changedBy' => $user['name'] ?? 'Admin',
                    'reason' => 'Initial catalog creation'
                ]
            ],
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ];

        $id = Database::executeInsert('mobiles', $document);
        return array_merge(['_id' => $id], $document);
    }

    public static function update(string $id, array $data): bool {
        $existing = Database::executeFindOne('mobiles', ['_id' => $id]);
        if (!$existing) {
            return false;
        }

        $set = [];
        if (isset($data['brand'])) $set['brand'] = trim((string)$data['brand']);
        if (isset($data['model'])) $set['model'] = trim((string)$data['model']);
        if (isset($data['variant'])) $set['variant'] = trim((string)$data['variant']);
        if (isset($data['imageUrl'])) $set['imageUrl'] = trim((string)$data['imageUrl']);
        if (isset($data['minStockAlert'])) $set['minStockAlert'] = (int)$data['minStockAlert'];
        if (isset($data['stock'])) $set['stock'] = (int)$data['stock'];

        if (empty($set)) {
            return true;
        }

        Database::executeUpdate('mobiles', ['_id' => $id], ['$set' => $set]);
        return true;
    }

    /**
     * Dynamic Pricing Module:
     * Seamlessly updates mobile price, cost price, discounts, and logs full audit trail.
     */
    public static function updatePrice(string $id, array $data, array $user): array {
        $existing = Database::executeFindOne('mobiles', ['_id' => $id]);
        if (!$existing) {
            throw new Exception("Mobile device not found.");
        }

        $oldPrice = (float)($existing['price'] ?? 0);
        $newPrice = isset($data['price']) ? (float)$data['price'] : $oldPrice;
        $oldCost = (float)($existing['costPrice'] ?? ($oldPrice * 0.8));
        $newCost = isset($data['costPrice']) ? (float)$data['costPrice'] : $oldCost;
        $reason = trim($data['reason'] ?? 'Dynamic market price adjustment');
        $discountPrice = isset($data['discountPrice']) ? (float)$data['discountPrice'] : null;

        if ($newPrice <= 0) {
            throw new InvalidArgumentException("Price must be greater than zero.");
        }

        $margin = round((($newPrice - $newCost) / $newPrice) * 100, 2);

        $historyEntry = [
            'date' => date('Y-m-d H:i:s'),
            'oldPrice' => $oldPrice,
            'newPrice' => $newPrice,
            'oldCost' => $oldCost,
            'newCost' => $newCost,
            'margin' => $margin,
            'discountPrice' => $discountPrice,
            'changedBy' => $user['name'] ?? 'Admin',
            'changedByRole' => $user['role'] ?? 'admin',
            'reason' => $reason
        ];

        $update = [
            '$set' => [
                'price' => $newPrice,
                'costPrice' => $newCost,
                'discountPrice' => $discountPrice,
                'updatedAt' => new MongoDB\BSON\UTCDateTime()
            ],
            '$push' => [
                'priceHistory' => $historyEntry
            ]
        ];

        Database::executeUpdate('mobiles', ['_id' => $id], $update);

        return [
            'success' => true,
            'id' => $id,
            'oldPrice' => $oldPrice,
            'newPrice' => $newPrice,
            'newCost' => $newCost,
            'margin' => $margin,
            'historyEntry' => $historyEntry
        ];
    }

    public static function restock(string $id, int $quantityToAdd, array $user): bool {
        if ($quantityToAdd <= 0) {
            throw new InvalidArgumentException("Restock quantity must be positive.");
        }
        $existing = Database::executeFindOne('mobiles', ['_id' => $id]);
        if (!$existing) {
            return false;
        }

        Database::executeUpdate('mobiles', ['_id' => $id], [
            '$inc' => ['stock' => $quantityToAdd]
        ]);
        return true;
    }

    public static function delete(string $id): bool {
        return Database::executeDelete('mobiles', ['_id' => $id]) > 0;
    }
}

