<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'product_id',
        'nama_pemesan',
        'nomor_wa',
        'tanggal_mulai',
        'tanggal_selesai',
        'jumlah',
        'durasi_hari',
        'status',
        'total_harga',
        'catatan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'total_harga' => 'decimal:2',
    ];

    /**
     * ✅ Boot method untuk auto-generate booking code
     * Method ini akan otomatis dipanggil saat model di-boot
     */
    protected static function boot()
    {
        parent::boot();

        // Event saat SEBELUM data disimpan ke database
        static::creating(function ($booking) {
            // Hanya generate jika booking_code masih kosong
            if (empty($booking->booking_code)) {
                $booking->booking_code = self::generateBookingCode();
            }
        });
    }

    /**
     * ✅ Generate unique booking code
     * Format: BK-YYYYMMDD-XXXX
     * Contoh: BK-20241117-A1B2
     */
    public static function generateBookingCode()
    {
        do {
            // Buat kode dengan format: BK-YYYYMMDD-XXXX
            $code = 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
            
            // Loop sampai dapat kode yang unik
        } while (self::where('booking_code', $code)->exists());

        return $code;
    }

    /**
     * Scope untuk pencarian booking code
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('booking_code', $code);
    }

    /**
     * Relasi ke Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Accessor untuk format harga
     */
    public function getFormattedPriceAttribute()
    {
        return 'Rp ' . number_format($this->total_harga, 0, ',', '.');
    }

    /**
     * Accessor untuk status badge color
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'yellow',
            'approved' => 'green',
            'completed' => 'blue',
            'rejected' => 'red',
            'cancelled' => 'gray',
            default => 'gray'
        };
    }

    /**
     * Accessor untuk status label Indonesia
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Menunggu Konfirmasi',
            'approved' => 'Disetujui',
            'completed' => 'Selesai',
            'rejected' => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            default => 'Unknown'
        };
    }
}