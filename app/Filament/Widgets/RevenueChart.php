<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Pendapatan vs Pengeluaran';
    protected static ?int $sort = 3;
    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(function ($i) {
            return Carbon::now()->subMonths($i);
        });

        $revenues = $months->map(function ($month) {
            return Payment::whereYear('payment_date', $month->year)
                ->whereMonth('payment_date', $month->month)
                ->sum('amount');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Pendapatan (Rp)',
                    'data' => $revenues->toArray(),
                    'fill' => true,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                    'borderColor' => 'rgb(245, 158, 11)',
                    'tension' => 0.4,
                    'pointBackgroundColor' => 'rgb(245, 158, 11)',
                    'pointRadius' => 4,
                ],
            ],
            'labels' => $months->map(fn ($m) => $m->translatedFormat('M Y'))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
