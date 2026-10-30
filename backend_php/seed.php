<?php
// backend_php/seed.php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Auth.php';

echo "=== Mobile Shop Management System Database Seeder ===\n";

// 1. Seed Users
$usersCol = 'users';
$existingAdmin = Database::executeFindOne($usersCol, ['email' => 'admin@mobileshop.com']);
if (!$existingAdmin) {
    Database::executeInsert($usersCol, [
        'name' => 'System Administrator',
        'email' => 'admin@mobileshop.com',
        'password' => Auth::hashPassword('admin123'),
        'role' => 'admin',
        'permissions' => array_keys(Auth::PERMISSIONS),
        'status' => 'active',
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
        'updatedAt' => new MongoDB\BSON\UTCDateTime()
    ]);
    echo "[+] Created default Admin: admin@mobileshop.com (Pass: admin123)\n";
} else {
    echo "[*] Admin user already exists.\n";
}

$existingSubAdmin = Database::executeFindOne($usersCol, ['email' => 'subadmin@mobileshop.com']);
if (!$existingSubAdmin) {
    Database::executeInsert($usersCol, [
        'name' => 'Rahim Chowdhury (Operations Sub-Admin)',
        'email' => 'subadmin@mobileshop.com',
        'password' => Auth::hashPassword('subadmin123'),
        'role' => 'sub_admin',
        'permissions' => ['record_sales', 'view_sales_daily', 'manage_pricing', 'manage_inventory'],
        'status' => 'active',
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
        'updatedAt' => new MongoDB\BSON\UTCDateTime()
    ]);
    echo "[+] Created Sub-Admin: subadmin@mobileshop.com (Pass: subadmin123)\n";
} else {
    echo "[*] Sub-Admin user already exists.\n";
}

$existingCashier = Database::executeFindOne($usersCol, ['email' => 'cashier@mobileshop.com']);
if (!$existingCashier) {
    Database::executeInsert($usersCol, [
        'name' => 'Farhana Akter (Sales Cashier)',
        'email' => 'cashier@mobileshop.com',
        'password' => Auth::hashPassword('cashier123'),
        'role' => 'cashier',
        'permissions' => ['record_sales', 'view_sales_daily'],
        'status' => 'active',
        'createdAt' => new MongoDB\BSON\UTCDateTime(),
        'updatedAt' => new MongoDB\BSON\UTCDateTime()
    ]);
    echo "[+] Created Cashier: cashier@mobileshop.com (Pass: cashier123)\n";
} else {
    echo "[*] Cashier user already exists.\n";
}

// 2. Enrich Existing Mobiles & Add Sample Catalog Devices
$mobiles = Database::executeQuery('mobiles', []);
echo "[*] Found " . count($mobiles) . " existing mobile documents.\n";

foreach ($mobiles as $m) {
    $updates = [];
    $price = (float)($m['price'] ?? 10000);
    if (!isset($m['costPrice'])) {
        $updates['costPrice'] = round($price * 0.82, 2);
    }
    if (!isset($m['minStockAlert'])) {
        $updates['minStockAlert'] = 3;
    }
    if (empty($m['priceHistory'])) {
        $cost = $updates['costPrice'] ?? (float)($m['costPrice'] ?? ($price * 0.82));
        $updates['priceHistory'] = [
            [
                'date' => date('Y-m-d H:i:s', strtotime('-10 days')),
                'oldPrice' => $price * 1.05,
                'newPrice' => $price,
                'oldCost' => $cost * 1.05,
                'newCost' => $cost,
                'margin' => round((($price - $cost) / $price) * 100, 2),
                'changedBy' => 'Admin',
                'reason' => 'Dynamic pricing adjustment'
            ]
        ];
    }
    if (!empty($updates)) {
        Database::executeUpdate('mobiles', ['_id' => $m['_id']], ['$set' => $updates]);
    }
}

// Additional high-demand mobile devices to showcase sold-out and low-stock states
$sampleDevices = [
    [
        'brand' => 'Apple',
        'model' => 'iPhone 15 Pro Max',
        'variant' => '256GB Natural Titanium',
        'price' => 165000,
        'costPrice' => 142000,
        'stock' => 8,
        'sold' => 14,
        'minStockAlert' => 3,
        'imageUrl' => 'https://images.unsplash.com/photo-1695048133142-1a20484d2569?w=400&q=80',
        'priceHistory' => [
            [
                'date' => date('Y-m-d H:i:s', strtotime('-25 days')),
                'oldPrice' => 172000,
                'newPrice' => 165000,
                'oldCost' => 145000,
                'newCost' => 142000,
                'margin' => 13.94,
                'changedBy' => 'Admin',
                'reason' => 'New import batch cost adjustment'
            ]
        ]
    ],
    [
        'brand' => 'Samsung',
        'model' => 'Galaxy S24 Ultra',
        'variant' => '512GB Titanium Gray',
        'price' => 155000,
        'costPrice' => 133000,
        'stock' => 2, // LOW STOCK
        'sold' => 9,
        'minStockAlert' => 3,
        'imageUrl' => 'https://images.unsplash.com/photo-1610945415295-d9bbf067e59c?w=400&q=80',
        'priceHistory' => [
            [
                'date' => date('Y-m-d H:i:s', strtotime('-15 days')),
                'oldPrice' => 158000,
                'newPrice' => 155000,
                'oldCost' => 135000,
                'newCost' => 133000,
                'margin' => 14.19,
                'changedBy' => 'Rahim (Sub-Admin)',
                'reason' => 'Flash weekend promotion'
            ]
        ]
    ],
    [
        'brand' => 'Google',
        'model' => 'Pixel 9 Pro XL',
        'variant' => '256GB Obsidian',
        'price' => 138000,
        'costPrice' => 118000,
        'stock' => 0, // SOLD OUT!
        'sold' => 11,
        'minStockAlert' => 3,
        'imageUrl' => 'https://images.unsplash.com/photo-1598327105666-5b89351aff97?w=400&q=80',
        'priceHistory' => [
            [
                'date' => date('Y-m-d H:i:s', strtotime('-5 days')),
                'oldPrice' => 135000,
                'newPrice' => 138000,
                'oldCost' => 118000,
                'newCost' => 118000,
                'margin' => 14.49,
                'changedBy' => 'Admin',
                'reason' => 'High demand market price surge'
            ]
        ]
    ],
    [
        'brand' => 'Xiaomi',
        'model' => '14 Ultra Leica Optics',
        'variant' => '512GB Black Ceramic',
        'price' => 115000,
        'costPrice' => 96000,
        'stock' => 0, // SOLD OUT!
        'sold' => 8,
        'minStockAlert' => 3,
        'imageUrl' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=400&q=80',
        'priceHistory' => [
            [
                'date' => date('Y-m-d H:i:s', strtotime('-12 days')),
                'oldPrice' => 120000,
                'newPrice' => 115000,
                'oldCost' => 98000,
                'newCost' => 96000,
                'margin' => 16.52,
                'changedBy' => 'Admin',
                'reason' => 'Clearance pricing before new flagship release'
            ]
        ]
    ],
    [
        'brand' => 'OnePlus',
        'model' => 'OnePlus 12 5G',
        'variant' => '256GB Silky Black',
        'price' => 78000,
        'costPrice' => 66000,
        'stock' => 6,
        'sold' => 15,
        'minStockAlert' => 3,
        'imageUrl' => 'https://images.unsplash.com/photo-1580910051074-3eb694886505?w=400&q=80',
        'priceHistory' => [
            [
                'date' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'oldPrice' => 82000,
                'newPrice' => 78000,
                'oldCost' => 68000,
                'newCost' => 66000,
                'margin' => 15.38,
                'changedBy' => 'Rahim (Sub-Admin)',
                'reason' => 'Festival dynamic discount'
            ]
        ]
    ]
];

foreach ($sampleDevices as $dev) {
    $exists = Database::executeFindOne('mobiles', ['brand' => $dev['brand'], 'model' => $dev['model']]);
    if (!$exists) {
        Database::executeInsert('mobiles', $dev);
        echo "[+] Added showcase device: {$dev['brand']} {$dev['model']} (Stock: {$dev['stock']})\n";
    }
}

// 3. Seed Sample Sales Records if collection is empty
$salesCount = Database::executeCount('sales');
if ($salesCount === 0) {
    echo "[*] Seeding realistic sales transactions for analytics...\n";
    $mobilesList = Database::executeQuery('mobiles', []);

    $paymentMethods = ['Cash', 'Card', 'bKash', 'Bank Transfer'];
    $customers = [
        ['name' => 'Tanvir Hasan', 'phone' => '01711223344'],
        ['name' => 'Mehedi Zaman', 'phone' => '01819887766'],
        ['name' => 'Sadia Islam', 'phone' => '01912345678'],
        ['name' => 'Nusrat Jahan', 'phone' => '01552334455'],
        ['name' => 'Kamal Hossain', 'phone' => '01671122445'],
        ['name' => 'Arifur Rahman', 'phone' => '01311002233']
    ];

    for ($day = 6; $day >= 0; $day--) {
        $txDate = date('Y-m-d H:i:s', strtotime("-$day days " . rand(9, 20) . ":" . rand(10, 59) . ":" . rand(10, 59)));
        $numSalesPerDay = rand(2, 4);

        for ($s = 0; $s < $numSalesPerDay; $s++) {
            $randomDev = $mobilesList[array_rand($mobilesList)];
            $cust = $customers[array_rand($customers)];
            $pay = $paymentMethods[array_rand($paymentMethods)];
            $qty = 1;
            $unitPrice = (float)($randomDev['price'] ?? 50000);
            $costPrice = (float)($randomDev['costPrice'] ?? ($unitPrice * 0.8));
            $subtotal = $unitPrice * $qty;
            $discount = rand(0, 5) > 3 ? 1000.0 : 0.0;
            $grand = $subtotal - $discount;
            $profit = $grand - ($costPrice * $qty);

            $saleDoc = [
                'invoiceNumber' => 'INV-' . date('Ymd', strtotime("-$day days")) . '-' . strtoupper(substr(uniqid(), -4)),
                'customerName' => $cust['name'],
                'customerPhone' => $cust['phone'],
                'paymentMethod' => $pay,
                'items' => [
                    [
                        'mobileId' => $randomDev['_id'],
                        'brand' => $randomDev['brand'],
                        'model' => $randomDev['model'],
                        'variant' => $randomDev['variant'] ?? '',
                        'quantity' => $qty,
                        'unitPrice' => $unitPrice,
                        'costPrice' => $costPrice,
                        'totalPrice' => $subtotal,
                        'profit' => $profit,
                        'imei' => '35' . rand(1000000000000, 9999999999999)
                    ]
                ],
                'subtotal' => $subtotal,
                'discount' => $discount,
                'grandTotal' => $grand,
                'totalCost' => $costPrice * $qty,
                'netProfit' => $profit,
                'soldBy' => [
                    'userId' => 'subadmin-seed',
                    'name' => rand(0, 1) ? 'Rahim Chowdhury (Operations Sub-Admin)' : 'Farhana Akter (Sales Cashier)',
                    'role' => rand(0, 1) ? 'sub_admin' : 'cashier'
                ],
                'date' => $txDate,
                'status' => 'completed',
                'createdAt' => new MongoDB\BSON\UTCDateTime(strtotime($txDate) * 1000),
                'updatedAt' => new MongoDB\BSON\UTCDateTime(strtotime($txDate) * 1000)
            ];

            Database::executeInsert('sales', $saleDoc);
        }
    }
    echo "[+] Seeded realistic sales history across the past 7 days.\n";
} else {
    echo "[*] Sales collection already contains $salesCount records.\n";
}

echo "=== Seeding Complete! ===\n";

