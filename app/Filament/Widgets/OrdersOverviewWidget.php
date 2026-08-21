<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrdersOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalRevenue = Order::where('is_paid', true)->sum('total_price');
        $totalOrders = Order::count();
        $pendingOrders = Order::whereIn('order_status', ['pending', 'confirmed'])->count();
        $lowStockProducts = Product::where('stock', '<=', 10)->count();

        return [
            Stat::make('Total Revenue', '₹' . number_format($totalRevenue, 2))
                ->description('Total completed sales')
                ->descriptionIcon('heroicon-m-currency-rupee')
                ->color('success'),

            Stat::make('Total Orders', $totalOrders)
                ->description('All time customer orders')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('primary'),

            Stat::make('Orders to Fulfill', $pendingOrders)
                ->description('Pending & Confirmed orders')
                ->descriptionIcon('heroicon-m-clock')
                ->color($pendingOrders > 0 ? 'warning' : 'success'),

            Stat::make('Low Stock Alerts', $lowStockProducts)
                ->description('Products with <= 10 items')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($lowStockProducts > 0 ? 'danger' : 'success'),
        ];
    }
}
