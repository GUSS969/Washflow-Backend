<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalCustomers = User::where('role', 'pelanggan')->count();
        $totalOrders = Order::count();
        $totalRevenue = Payment::sum('amount');
        $pendingOrders = Order::where('status', 'diterima')->count();

        return [
            Stat::make('Total Pelanggan', number_format($totalCustomers))
                ->description('Pelanggan terdaftar')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info')
                ->chart([7, 3, 4, 5, 6, 3, 5]),

            Stat::make('Total Pesanan', number_format($totalOrders))
                ->description('Semua pesanan')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('success')
                ->chart([3, 5, 7, 6, 3, 5, 4]),

            Stat::make('Total Pendapatan', 'Rp ' . number_format($totalRevenue, 0, ',', '.'))
                ->description('Pendapatan keseluruhan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning')
                ->chart([5, 4, 6, 8, 5, 7, 9]),

            Stat::make('Pesanan Pending', number_format($pendingOrders))
                ->description('Menunggu diproses')
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger')
                ->chart([2, 4, 3, 5, 2, 3, 4]),
        ];
    }
}
