<?php

namespace App\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Models\Booking;

class LatestBookings extends BaseWidget
{
    protected static ?string $heading = 'Booking Terbaru';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Booking::query()
                    ->with(['product'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('booking_code')
                    ->label('Kode Booking')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable()
                    ->icon('heroicon-m-ticket'),
                
                Tables\Columns\TextColumn::make('nama_pemesan')
                    ->label('Nama Pemesan')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user'),
                
                Tables\Columns\TextColumn::make('product.nama')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->icon('heroicon-m-camera'),
                
                Tables\Columns\TextColumn::make('tanggal_mulai')
                    ->label('Mulai')
                    ->date('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('tanggal_selesai')
                    ->label('Selesai')
                    ->date('d M Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('durasi_hari')
                    ->label('Durasi')
                    ->suffix(' hari')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),
                
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'completed',
                        'gray' => 'cancelled',
                    ])
                    ->icons([
                        'heroicon-o-clock' => 'pending',
                        'heroicon-o-check-circle' => 'approved',
                        'heroicon-o-x-circle' => 'rejected',
                        'heroicon-o-check-badge' => 'completed',
                        'heroicon-o-no-symbol' => 'cancelled',
                    ]),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc');
    }
}