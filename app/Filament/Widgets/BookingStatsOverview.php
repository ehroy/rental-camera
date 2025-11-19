<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Booking;
use Illuminate\Support\Carbon;

class BookingStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected static ?string $pollingInterval = '15s';
    
    protected function getStats(): array
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        
        // Total booking bulan ini
        $bookingsThisMonth = Booking::where('created_at', '>=', $thisMonth)->count();
        $bookingsLastMonth = Booking::whereBetween('created_at', [
            $lastMonth, 
            $lastMonth->copy()->endOfMonth()
        ])->count();
        
        $bookingChange = $bookingsLastMonth > 0 
            ? (($bookingsThisMonth - $bookingsLastMonth) / $bookingsLastMonth) * 100 
            : 0;
        
        // Booking hari ini
        $bookingsToday = Booking::whereDate('created_at', $today)->count();
        
        // Booking yang sedang aktif (approved dan belum selesai)
        $activeRentals = Booking::where('status', 'approved')
            ->where('tanggal_selesai', '>=', $today)
            ->count();
        
        // Revenue bulan ini (hanya yang approved dan completed)
        $revenueThisMonth = Booking::where('created_at', '>=', $thisMonth)
            ->whereIn('status', ['approved', 'completed'])
            ->sum('total_harga');
        
        $revenueLastMonth = Booking::whereBetween('created_at', [
            $lastMonth, 
            $lastMonth->copy()->endOfMonth()
        ])
        ->whereIn('status', ['approved', 'completed'])
        ->sum('total_harga');
        
        $revenueChange = $revenueLastMonth > 0 
            ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100 
            : 0;

        return [
            Stat::make('Booking Bulan Ini', number_format($bookingsThisMonth))
                ->description(
                    $bookingChange >= 0 
                        ? sprintf('+%.1f%% dari bulan lalu', abs($bookingChange))
                        : sprintf('-%.1f%% dari bulan lalu', abs($bookingChange))
                )
                ->descriptionIcon($bookingChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($bookingChange >= 0 ? 'success' : 'danger')
                ->chart($this->getWeeklyChart())
                ->icon('heroicon-o-calendar'),
            
            Stat::make('Booking Hari Ini', number_format($bookingsToday))
                ->description('Request booking hari ini')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info')
                ->icon('heroicon-o-bell-alert'),
            
            Stat::make('Sedang Disewa', number_format($activeRentals))
                ->description('Product yang sedang disewa')
                ->descriptionIcon('heroicon-m-camera')
                ->color('warning')
                ->icon('heroicon-o-shopping-cart'),
            
            Stat::make('Revenue Bulan Ini', 'Rp ' . number_format($revenueThisMonth, 0, ',', '.'))
                ->description(
                    $revenueChange >= 0 
                        ? sprintf('+%.1f%% dari bulan lalu', abs($revenueChange))
                        : sprintf('-%.1f%% dari bulan lalu', abs($revenueChange))
                )
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->icon('heroicon-o-banknotes'),
        ];
    }
    
    protected function getWeeklyChart(): array
    {
        $data = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $count = Booking::whereDate('created_at', $date)->count();
            $data[] = $count;
        }
        
        return $data;
    }
}