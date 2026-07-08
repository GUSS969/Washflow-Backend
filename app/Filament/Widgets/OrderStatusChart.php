<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class OrderStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Status Pesanan';
    protected static ?int $sort = 4;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $pending = Order::where('status', 'pending')->count();
        $proses = Order::where('status', 'proses')->count();
        $selesai = Order::where('status', 'selesai')->count();
        $diambil = Order::where('status', 'diambil')->count();

        return [
            'datasets' => [
                [
                    'data' => [$pending, $proses, $selesai, $diambil],
                    'backgroundColor' => [
                        'rgb(245, 158, 11)',  // warning - kuning
                        'rgb(59, 130, 246)',   // info - biru
                        'rgb(16, 185, 129)',   // success - hijau
                        'rgb(156, 163, 175)',  // gray
                    ],
                    'hoverOffset' => 4,
                ],
            ],
            'labels' => ['Pending', 'Proses', 'Selesai', 'Diambil'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
