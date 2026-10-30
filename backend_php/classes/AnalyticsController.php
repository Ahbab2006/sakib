<?php
// backend_php/classes/AnalyticsController.php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

class AnalyticsController {

    public static function getDashboardOverview(): array {
        $mobiles = Database::executeQuery('mobiles', []);

        $totalModels = count($mobiles);
        $totalAvailableStock = 0;
        $totalSoldUnits = 0;
        $inventoryRetailValue = 0.0;
        $inventoryCostValue = 0.0;

        $inStockCount = 0;
        $lowStockCount = 0;
        $soldOutCount = 0;
        $soldOutItems = [];
        $lowStockItems = [];

        foreach ($mobiles as $m) {
            $stock = (int)($m['stock'] ?? 0);
            $sold = (int)($m['sold'] ?? 0);
            $price = (float)($m['price'] ?? 0);
            $cost = (float)($m['costPrice'] ?? ($price * 0.8));
            $minAlert = (int)($m['minStockAlert'] ?? 3);

            $totalAvailableStock += $stock;
            $totalSoldUnits += $sold;
            $inventoryRetailValue += ($stock * $price);
            $inventoryCostValue += ($stock * $cost);

            if ($stock <= 0) {
                $soldOutCount++;
                $soldOutItems[] = [
                    'id' => $m['_id'],
                    'brand' => $m['brand'] ?? '',
                    'model' => $m['model'] ?? '',
                    'variant' => $m['variant'] ?? '',
                    'sold' => $sold,
                    'price' => $price,
                    'lastUpdated' => $m['updatedAt'] ?? 'N/A'
                ];
            } elseif ($stock <= $minAlert) {
                $lowStockCount++;
                $lowStockItems[] = [
                    'id' => $m['_id'],
                    'brand' => $m['brand'] ?? '',
                    'model' => $m['model'] ?? '',
                    'variant' => $m['variant'] ?? '',
                    'stock' => $stock,
                    'price' => $price
                ];
            } else {
                $inStockCount++;
            }
        }

        // Aggregate Sales Metrics
        $sales = Database::executeQuery('sales', [], ['sort' => ['createdAt' => -1]]);
        $totalSalesCount = count($sales);
        $totalRevenue = 0.0;
        $totalProfit = 0.0;
        $todayRevenue = 0.0;
        $todayProfit = 0.0;
        $todayInvoices = 0;
        $todayDate = date('Y-m-d');

        $dailySalesHistory = [];
        for ($i = 6; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $dailySalesHistory[$d] = ['date' => $d, 'revenue' => 0.0, 'profit' => 0.0, 'count' => 0];
        }

        $brandSales = [];

        foreach ($sales as $sale) {
            $grand = (float)($sale['grandTotal'] ?? 0);
            $profit = (float)($sale['netProfit'] ?? 0);
            $saleDate = substr((string)($sale['date'] ?? ''), 0, 10);

            $totalRevenue += $grand;
            $totalProfit += $profit;

            if ($saleDate === $todayDate) {
                $todayRevenue += $grand;
                $todayProfit += $profit;
                $todayInvoices++;
            }

            if (isset($dailySalesHistory[$saleDate])) {
                $dailySalesHistory[$saleDate]['revenue'] += $grand;
                $dailySalesHistory[$saleDate]['profit'] += $profit;
                $dailySalesHistory[$saleDate]['count']++;
            }

            if (!empty($sale['items']) && is_array($sale['items'])) {
                foreach ($sale['items'] as $item) {
                    $b = $item['brand'] ?? 'Unknown';
                    if (!isset($brandSales[$b])) {
                        $brandSales[$b] = ['brand' => $b, 'units' => 0, 'revenue' => 0.0];
                    }
                    $brandSales[$b]['units'] += (int)($item['quantity'] ?? 1);
                    $brandSales[$b]['revenue'] += (float)($item['totalPrice'] ?? 0);
                }
            }
        }

        // Sort brand sales descending by revenue
        usort($brandSales, fn($a, $b) => $b['revenue'] <=> $a['revenue']);

        $recentSales = array_slice($sales, 0, 8);

        return [
            'kpis' => [
                'totalModels' => $totalModels,
                'totalAvailableStock' => $totalAvailableStock,
                'totalSoldUnits' => $totalSoldUnits,
                'inventoryRetailValue' => round($inventoryRetailValue, 2),
                'inventoryCostValue' => round($inventoryCostValue, 2),
                'potentialProfit' => round($inventoryRetailValue - $inventoryCostValue, 2),
                'totalSalesInvoices' => $totalSalesCount,
                'totalRevenue' => round($totalRevenue, 2),
                'totalProfit' => round($totalProfit, 2),
                'todayRevenue' => round($todayRevenue, 2),
                'todayProfit' => round($todayProfit, 2),
                'todayInvoices' => $todayInvoices,
            ],
            'stockStatus' => [
                'inStock' => $inStockCount,
                'lowStock' => $lowStockCount,
                'soldOut' => $soldOutCount,
                'soldOutItems' => $soldOutItems,
                'lowStockItems' => $lowStockItems
            ],
            'dailyTrends' => array_values($dailySalesHistory),
            'topBrands' => array_slice($brandSales, 0, 5),
            'recentSales' => $recentSales
        ];
    }
}

