<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Booking;
use Illuminate\Support\Carbon;

class BookingChart extends ChartWidget
{
    protected static ?string $heading = 'Trend Booking 12 Bulan Terakhir';
    protected static ?int $sort = 2;
    protected static string $color = 'info';
    
    protected function getData(): array
    {
        $data = $this->getBookingsPerMonth();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Booking',
                    'data' => $data['bookings'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Revenue (Juta Rp)',
                    'data' => $data['revenue'],
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                    'borderColor' => 'rgb(16, 185, 129)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $data['months'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'position' => 'left',
                ],
                'y1' => [
                    'beginAtZero' => true,
                    'position' => 'right',
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }

    private function getBookingsPerMonth(): array
    {
        $months = [];
        $bookings = [];
        $revenue = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $months[] = $date->translatedFormat('M Y');
            
            $count = Booking::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->count();
            
            $rev = Booking::whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->whereIn('status', ['approved', 'completed'])
                ->sum('total_harga');
                
            $bookings[] = $count;
            $revenue[] = round($rev / 1000000, 2); // Convert ke juta
        }

        return [
            'months' => $months,
            'bookings' => $bookings,
            'revenue' => $revenue,
        ];
    }
}