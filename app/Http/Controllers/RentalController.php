<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Booking;
use App\Models\Category;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RentalController extends Controller
{


    public function index(Request $request)
    {
        $query = Product::where('is_available', true)
            ->with(['category'])
            ->withCount('bookings');

        // Filter berdasarkan kategori jika ada dan tidak kosong
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter berdasarkan search query
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('nama', 'like', '%' . $request->search . '%')
                ->orWhere('deskripsi', 'like', '%' . $request->search . '%');
            });
        }

        $products = $query->get();

        // Ambil semua kategori untuk ditampilkan sebagai filter
        $categories = Category::select('id', 'nama')->get();

        return Inertia::render('Dashboard', [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'category_id' => $request->category_id ?? '',
                'search' => $request->search ?? '',
            ],
        ]);
    }
    public function show(Product $product)
    {
        $bookedDates = $product->getBookedDates();

        return Inertia::render('Rental/Show', [
            'product' => $product,
            'bookedDates' => $bookedDates,
        ]);
    }

    public function checkAvailability(Request $request, Product $product)
    {
        $request->validate([
            'tanggal_mulai' => 'required|date|after_or_equal:today',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        ]);

        $available = $product->isAvailableOnDate(
            $request->tanggal_mulai,
            $request->tanggal_selesai,
        );

        if ($available) {
            $days = \Carbon\Carbon::parse($request->tanggal_mulai)
                ->diffInDays(\Carbon\Carbon::parse($request->tanggal_selesai)) + 1;

            $totalPrice = $product->harga_sewa_perhari * $days;

            return response()->json([
                'available' => true,
                'days' => $days,
                'total_price' => $totalPrice,
                'price_formatted' => 'Rp ' . number_format($totalPrice, 0, ',', '.'),
            ]);
        }

        return response()->json([
            'available' => false,
            'message' => 'Produk sudah dibooking pada tanggal tersebut',
        ]);
    }

    public function bookingstore(Request $request, Product $product)
{
    $validated = $request->validate([
        'nama_pemesan' => 'required|string|max:255',
        'nomor_wa' => 'required|string|max:20',
        'tanggal_mulai' => 'required|date|after_or_equal:today',
        'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
        'jumlah' => 'required|integer|min:1',
        'catatan' => 'nullable|string',
    ]);

    if (!$product->isAvailableOnDate($request->tanggal_mulai, $request->tanggal_selesai)) {
        return back()->with('error', 'Produk sudah dibooking pada tanggal tersebut');
    }

    $days = \Carbon\Carbon::parse($request->tanggal_mulai)
        ->diffInDays(\Carbon\Carbon::parse($request->tanggal_selesai)) + 1;

    $totalPrice = $product->harga_sewa_perhari * $days * $request->jumlah;

    try {

        /* =============================
           GENERATE BOOKING CODE
        ==============================*/
        $last = Booking::orderBy('id', 'desc')->first();
        $nextNumber = $last ? $last->id + 1 : 1;
        $bookingCode = 'BK-' . now()->format('Ymd') . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);


        /* =============================
           SIMPAN BOOKING
        ==============================*/
        $booking = Booking::create([
            'product_id' => $product->id,
            'booking_code' => $bookingCode,
            'nama_pemesan' => $request->nama_pemesan,
            'nomor_wa' => $request->nomor_wa,
            'tanggal_mulai' => $request->tanggal_mulai,
            'tanggal_selesai' => $request->tanggal_selesai,
            'jumlah' => $request->jumlah,
            'durasi_hari' => $days,
            'status' => 'pending',
            'total_harga' => $totalPrice,
            'catatan' => $request->catatan,
        ]);


        /* =============================
           FORMAT WHATSAPP MESSAGE
        ==============================*/

        $adminPhone = '62895381587961';

        $message = "🎯 *BOOKING BARU*\n";
        $message .= "━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "📦 *Produk:* {$product->nama}\n";
        $message .= "👤 *Nama:* {$request->nama_pemesan}\n";
        $message .= "📱 *WA:* {$request->nomor_wa}\n\n";

        $message .= "📅 *Tanggal Sewa:* " .
            \Carbon\Carbon::parse($request->tanggal_mulai)->isoFormat('D MMM YYYY') .
            " - " .
            \Carbon\Carbon::parse($request->tanggal_selesai)->isoFormat('D MMM YYYY') . "\n";

        $message .= "⏱️ Durasi: {$days} hari\n";
        $message .= "🔢 Jumlah: {$request->jumlah} unit\n\n";

        $message .= "💰 *Total:* Rp " . number_format($totalPrice, 0, ',', '.') . "\n\n";
        $message .= "🆔 *Kode Booking:* {$bookingCode}\n\n";

        if ($request->catatan) {
            $message .= "📝 Catatan:\n{$request->catatan}\n\n";
        }

        $message .= "━━━━━━━━━━━━━━━━━━\n";
        $message .= "_Mohon konfirmasi dengan menyebutkan kode booking_";

        $whatsappUrl = "https://wa.me/{$adminPhone}?text=" . urlencode($message);


        /* =============================
           SIMPAN KE SESSION UNTUK HALAMAN SUKSES
        ==============================*/
        session([
            'booking_success' => [
                'booking_code' => $bookingCode,
                'booking_id' => $booking->id,
                'product_name' => $product->nama,
                'total' => $totalPrice,
                'whatsapp_url' => $whatsappUrl,
                'customer_name' => $request->nama_pemesan,
                'customer_wa' => $request->nomor_wa,
            ]
        ]);

        /* =============================
           REDIRECT KE HALAMAN SUKSES
        ==============================*/
        return redirect()->route('rental.success');

    } catch (\Exception $e) {
        return back()->with('error', 'Terjadi kesalahan: '.$e->getMessage());
    }
}


    public function success()
    {
        $data = session('booking_success');

        if (!$data) {
            return redirect()->route('home');
        }

        return Inertia::render('Booking/Success', [
            'booking' => $data
        ]);
    }


    public function cartCheckout(Request $request)
    {
        $validated = $request->validate([
            'nama_pemesan' => 'required|string|max:255',
            'nomor_wa' => 'required|string|max:20',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.id' => 'required|integer|exists:products,id',
            'cart_items.*.jumlah' => 'required|integer|min:1',
            'cart_items.*.tanggal_mulai' => 'required|date',
            'cart_items.*.tanggal_selesai' => 'required|date',
            'catatan' => 'nullable|string',
        ]);

        try {
            $totalHargaKeseluruhan = 0;
            $bookings = [];
            $productDetails = [];

            // LOOP tiap item cart
            foreach ($request->cart_items as $item) {
                $product = Product::findOrFail($item['id']);

                // Cek ketersediaan
                if (!$product->isAvailableOnDate($item['tanggal_mulai'], $item['tanggal_selesai'])) {
                    return back()->with('error', "Produk {$product->nama} sudah dibooking pada tanggal tersebut");
                }

                $days = Carbon::parse($item['tanggal_mulai'])
                    ->diffInDays(Carbon::parse($item['tanggal_selesai'])) + 1;

                $totalPrice = $product->harga_sewa_perhari * $days * $item['jumlah'];
                $totalHargaKeseluruhan += $totalPrice;

                // SIMPAN BOOKING
                $booking = Booking::create([
                    'product_id' => $product->id,
                    'nama_pemesan' => $request->nama_pemesan,
                    'nomor_wa' => $request->nomor_wa,
                    'tanggal_mulai' => $item['tanggal_mulai'],
                    'tanggal_selesai' => $item['tanggal_selesai'],
                    'jumlah' => $item['jumlah'],
                    'durasi_hari' => $days,
                    'status' => 'pending',
                    'total_harga' => $totalPrice,
                    'catatan' => $request->catatan ?? null,
                ]);

                $bookings[] = $booking;

                // SIMPAN DETAIL PRODUK + BOOKING CODE
                $productDetails[] = [
                    'nama' => $product->nama,
                    'harga' => $product->harga_sewa_perhari,
                    'jumlah' => $item['jumlah'],
                    'tanggal_mulai' => $item['tanggal_mulai'],
                    'tanggal_selesai' => $item['tanggal_selesai'],
                    'durasi' => $days,
                    'total' => $totalPrice,
                    'booking_code' => $booking->booking_code   // ← penting
                ];
            }

            // ==========================
            // FORMAT PESAN WHATSAPP
            // ==========================

            $adminPhone = '62895381587961';
            $message = "🎯 *BOOKING KERANJANG*\n";
            $message .= "━━━━━━━━━━━━━━━━━━\n\n";

            // DAFTAR BOOKING CODE
            $message .= "🆔 *Kode Booking:*\n";
            foreach ($bookings as $b) {
                $message .= "   • *{$b->booking_code}*\n";
            }
            $message .= "\n";

            // DATA CUSTOMER
            $message .= "👤 *Nama Pemesan:* {$request->nama_pemesan}\n";
            $message .= "📱 *No. WA:* {$request->nomor_wa}\n\n";

            $message .= "📦 *DAFTAR PRODUK:*\n";
            $message .= "━━━━━━━━━━━━━━━━━━\n";

            // PRODUK PER BOOKING
            foreach ($productDetails as $index => $detail) {
                $message .= "\n";
                $message .= "   🔑 *Kode Booking: {$detail['booking_code']}*\n";
                $message .= "*" . ($index + 1) . ". {$detail['nama']}*\n";
                $message .= "   🔢 Jumlah: {$detail['jumlah']} unit\n";
                $message .= "   📅 Mulai: " . Carbon::parse($detail['tanggal_mulai'])->isoFormat('D MMM YYYY') . "\n";
                $message .= "   📅 Selesai: " . Carbon::parse($detail['tanggal_selesai'])->isoFormat('D MMM YYYY') . "\n";
                $message .= "   ⏱ Durasi: {$detail['durasi']} hari\n";
                $message .= "   💰 Harga: Rp " . number_format($detail['harga'], 0, ',', '.') . "/hari\n";
                $message .= "   💵 Subtotal: Rp " . number_format($detail['total'], 0, ',', '.') . "\n";
            }

            // TOTAL
            $message .= "\n━━━━━━━━━━━━━━━━━━\n";
            $message .= "💰 *TOTAL PEMBAYARAN:* ";
            $message .= "*Rp " . number_format($totalHargaKeseluruhan, 0, ',', '.') . "*\n\n";

            if ($request->catatan) {
                $message .= "📝 *Catatan:*\n   {$request->catatan}\n\n";
            }

            $message .= "⏰ *Waktu Order:* " . now()->isoFormat('D MMM YYYY, HH:mm') . "\n\n";
            $message .= "_Mohon konfirmasi dengan menyebutkan kode booking di atas_";

            $encodedMessage = urlencode($message);
            $whatsappUrl = "https://wa.me/{$adminPhone}?text={$encodedMessage}";

            // SIMPAN SESSION
            session([
                'booking_success' => [
                    'booking_codes' => array_map(fn($b) => $b->booking_code, $bookings),
                    'booking_ids' => array_map(fn($b) => $b->id, $bookings),
                    'total' => $totalHargaKeseluruhan,
                    'whatsapp_url' => $whatsappUrl,
                    'product_details' => $productDetails,
                    'customer_name' => $request->nama_pemesan,
                    'customer_wa' => $request->nomor_wa
                ]
            ]);

            return redirect()->route('rental.success');

        } catch (\Exception $e) {
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}
