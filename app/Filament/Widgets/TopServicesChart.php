<?php

namespace App\Filament\Widgets;

use App\Models\Service;
use App\Models\OrderDetail;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopServicesChart extends ChartWidget
{
    protected static ?string $heading = 'Top Layanan Terpopuler';
    protected static ?int $sort = 5;
    protected static ?string $maxHeight = '300px';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        // Get top services by number of order details
        $topServices = OrderDetail::select('service_id', DB::raw('COUNT(*) as total'))
            ->groupBy('service_id')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $labels = [];
        $data = [];
        $colors = [
            'rgba(59, 130, 246, 0.8)',
            'rgba(16, 185, 129, 0.8)',
            'rgba(245, 158, 11, 0.8)',
            'rgba(239, 68, 68, 0.8)',
            'rgba(139, 92, 246, 0.8)',
        ];

        foreach ($topServices as $index => $item) {
            $service = Service::find($item->service_id);
            $labels[] = $service ? $service->service_name : 'Layanan #' . $item->service_id;
            $data[] = $item->total;
        }

        // If no data, show placeholder
        if (empty($data)) {
            $services = Service::limit(5)->get();
            $labels = $services->pluck('service_name')->toArray();
            $data = array_fill(0, count($labels), 0);

            if (empty($labels)) {
                $labels = ['Belum ada layanan'];
                $data = [0];
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Pesanan',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'borderColor' => $colors,
                    'borderWidth' => 1,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
