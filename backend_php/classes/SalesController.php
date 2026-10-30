<?php
// backend_php/classes/SalesController.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/Auth.php';

class SalesController {

    public static function createSale(array $data, array $user): array {
        $items = $data['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            throw new InvalidArgumentException("At least one sale item is required.");
        }

        $customerName = trim($data['customerName'] ?? 'Walk-in Customer');
        $customerPhone = trim($data['customerPhone'] ?? '');
        $paymentMethod = trim($data['paymentMethod'] ?? 'Cash');
        $discountOverall = (float)($data['discount'] ?? 0);

        $processedItems = [];
        $subtotal = 0.0;
        $totalCost = 0.0;

        foreach ($items as $item) {
            $mobileId = $item['mobileId'] ?? null;
            $qty = (int)($item['quantity'] ?? 1);
            if (!$mobileId || $qty <= 0) {
                throw new InvalidArgumentException("Invalid item ID or quantity.");
            }

            $mobile = Database::executeFindOne('mobiles', ['_id' => $mobileId]);
            if (!$mobile) {
                throw new Exception("Mobile device ID $mobileId not found.");
            }

            $availableStock = (int)($mobile['stock'] ?? 0);
            if ($availableStock < $qty) {
                throw new Exception("Insufficient stock for {$mobile['brand']} {$mobile['model']}. Available: $availableStock, Requested: $qty");
            }

            $unitPrice = isset($item['unitPrice']) && (float)$item['unitPrice'] > 0 
                ? (float)$item['unitPrice'] 
                : (float)($mobile['discountPrice'] ?? $mobile['price']);
            $costPrice = (float)($mobile['costPrice'] ?? ($unitPrice * 0.8));

            $itemTotal = round($unitPrice * $qty, 2);
            $itemCost = round($costPrice * $qty, 2);
            $itemProfit = round($itemTotal - $itemCost, 2);

            $subtotal += $itemTotal;
            $totalCost += $itemCost;

            $processedItems[] = [
                'mobileId' => $mobileId,
                'brand' => $mobile['brand'],
                'model' => $mobile['model'],
                'variant' => $mobile['variant'] ?? '',
                'quantity' => $qty,
                'unitPrice' => $unitPrice,
                'costPrice' => $costPrice,
                'totalPrice' => $itemTotal,
                'profit' => $itemProfit,
                'imei' => $item['imei'] ?? 'N/A'
            ];

            // Decrement stock and increment sold in mobiles collection
            Database::executeUpdate('mobiles', ['_id' => $mobileId], [
                '$inc' => [
                    'stock' => -$qty,
                    'sold' => $qty
                ]
            ]);
        }

        $grandTotal = max(0.0, round($subtotal - $discountOverall, 2));
        $netProfit = round($grandTotal - $totalCost, 2);

        // Generate Invoice Number
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

        $saleDocument = [
            'invoiceNumber' => $invoiceNumber,
            'customerName' => $customerName,
            'customerPhone' => $customerPhone,
            'paymentMethod' => $paymentMethod,
            'items' => $processedItems,
            'subtotal' => $subtotal,
            'discount' => $discountOverall,
            'grandTotal' => $grandTotal,
            'totalCost' => $totalCost,
            'netProfit' => $netProfit,
            'soldBy' => [
                'userId' => $user['userId'] ?? (string)($user['_id'] ?? ''),
                'name' => $user['name'] ?? 'Admin',
                'role' => $user['role'] ?? 'admin'
            ],
            'date' => date('Y-m-d H:i:s'),
            'status' => 'completed',
            'createdAt' => new MongoDB\BSON\UTCDateTime(),
            'updatedAt' => new MongoDB\BSON\UTCDateTime()
        ];

        $saleId = Database::executeInsert('sales', $saleDocument);
        $saleDocument['_id'] = $saleId;

        return [
            'success' => true,
            'message' => 'Sale processed successfully.',
            'sale' => $saleDocument
        ];
    }

    public static function getSales(array $filters = [], ?array $user = null): array {
        $mongoFilter = [];

        // Date filter
        if (!empty($filters['date'])) {
            // E.g. '2026-10-30'
            $regex = new MongoDB\BSON\Regex('^' . preg_quote($filters['date']), 'i');
            $mongoFilter['date'] = $regex;
        } elseif (!empty($filters['today'])) {
            $today = date('Y-m-d');
            $regex = new MongoDB\BSON\Regex('^' . preg_quote($today), 'i');
            $mongoFilter['date'] = $regex;
        }

        // Search by invoice or customer
        if (!empty($filters['search'])) {
            $regex = new MongoDB\BSON\Regex($filters['search'], 'i');
            $mongoFilter['$or'] = [
                ['invoiceNumber' => $regex],
                ['customerName' => $regex],
                ['customerPhone' => $regex]
            ];
        }

        // Sub-admin restricted view: If user does not have view_analytics and is cashier/sub_admin, only show their sales or daily sales
        if ($user && $user['role'] !== 'admin' && !in_array('view_analytics', $user['permissions'] ?? [])) {
            // If they only have view_sales_daily, default to today's sales
            if (empty($filters['date']) && in_array('view_sales_daily', $user['permissions'] ?? [])) {
                $today = date('Y-m-d');
                $mongoFilter['date'] = new MongoDB\BSON\Regex('^' . preg_quote($today), 'i');
            }
        }

        $options = ['sort' => ['createdAt' => -1], 'limit' => (int)($filters['limit'] ?? 100)];
        return Database::executeQuery('sales', $mongoFilter, $options);
    }

    public static function getDailyOperationsSummary(?string $targetDate = null): array {
        $date = $targetDate ?: date('Y-m-d');
        $regex = new MongoDB\BSON\Regex('^' . preg_quote($date), 'i');

        $sales = Database::executeQuery('sales', ['date' => $regex]);

        $totalRevenue = 0.0;
        $totalProfit = 0.0;
        $totalItemsSold = 0;
        $paymentBreakdown = ['Cash' => 0.0, 'Card' => 0.0, 'bKash' => 0.0, 'Other' => 0.0];
        $sellerBreakdown = [];

        foreach ($sales as $sale) {
            $rev = (float)($sale['grandTotal'] ?? 0);
            $profit = (float)($sale['netProfit'] ?? 0);
            $method = $sale['paymentMethod'] ?? 'Other';
            $seller = $sale['soldBy']['name'] ?? 'Unknown';

            $totalRevenue += $rev;
            $totalProfit += $profit;

            if (isset($paymentBreakdown[$method])) {
                $paymentBreakdown[$method] += $rev;
            } else {
                $paymentBreakdown['Other'] += $rev;
            }

            if (!isset($sellerBreakdown[$seller])) {
                $sellerBreakdown[$seller] = ['invoices' => 0, 'revenue' => 0.0];
            }
            $sellerBreakdown[$seller]['invoices']++;
            $sellerBreakdown[$seller]['revenue'] += $rev;

            if (!empty($sale['items']) && is_array($sale['items'])) {
                foreach ($sale['items'] as $item) {
                    $totalItemsSold += (int)($item['quantity'] ?? 1);
                }
            }
        }

        return [
            'date' => $date,
            'totalInvoices' => count($sales),
            'totalItemsSold' => $totalItemsSold,
            'totalRevenue' => round($totalRevenue, 2),
            'totalProfit' => round($totalProfit, 2),
            'paymentBreakdown' => $paymentBreakdown,
            'sellerBreakdown' => $sellerBreakdown,
            'transactions' => $sales
        ];
    }
}

