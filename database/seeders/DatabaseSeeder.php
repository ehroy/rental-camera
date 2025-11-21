<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Setting;
use App\Models\SocialLink;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('🚀 Starting database seeding...');

        // ==========================================
        // 1. ROLES & PERMISSIONS
        // ==========================================
        $this->command->info('👥 Creating roles & permissions...');
        
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        Permission::firstOrCreate(['name' => 'manage users', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage products', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage bookings', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'manage categories', 'guard_name' => 'web']);

        $adminRole->givePermissionTo(['manage users', 'manage products', 'manage bookings', 'manage categories']);

        // ==========================================
        // 2. ADMIN USER
        // ==========================================
        $this->command->info('👤 Creating admin user...');
        
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
        ]);

        $admin->assignRole($adminRole);

        // ==========================================
        // 3. CATEGORIES
        // ==========================================
        $this->command->info('📂 Creating categories...');
        
        $this->call(CategorySeeder::class);

        $lensa = Category::where('nama', 'Lensa')->first();
        $bodyOnly = Category::where('nama', 'Body Only')->first();
        $flash = Category::where('nama', 'Flash')->first();
        $aksesoris = Category::where('nama', 'Aksesoris')->first();

        // ==========================================
        // 4. PRODUCTS
        // ==========================================
        $this->command->info('📦 Creating products...');
        
        $products = [
            // Body Only
            [
                'nama' => 'Kamera Sony A7 III',
                'deskripsi' => 'Kamera full-frame 24.2MP dengan 4K video, hybrid AF, dan stabilisasi 5-axis. Cocok untuk foto dan video profesional.',
                'gambar' => null,
                'harga_sewa_perhari' => 150000,
                'is_available' => true,
                'category_id' => $bodyOnly?->id,
            ],
            [
                'nama' => 'Canon 5D Mark IV',
                'deskripsi' => 'Kamera DSLR full-frame 30.4MP dengan dual pixel AF, 4K video, dan touchscreen LCD. Ideal untuk produksi foto & video.',
                'gambar' => null,
                'harga_sewa_perhari' => 170000,
                'is_available' => true,
                'category_id' => $bodyOnly?->id,
            ],
            [
                'nama' => 'Fujifilm X-T4',
                'deskripsi' => 'Mirrorless APS-C 26.1MP dengan stabilisasi IBIS, video 4K 60fps, dan desain weather-sealed. Perfect untuk traveling.',
                'gambar' => null,
                'harga_sewa_perhari' => 120000,
                'is_available' => true,
                'category_id' => $bodyOnly?->id,
            ],
            [
                'nama' => 'Nikon Z6 II',
                'deskripsi' => 'Full-frame mirrorless 24.5MP dengan dual processor, 4K 60fps, dan autofocus cepat. Versatile untuk berbagai kondisi.',
                'gambar' => null,
                'harga_sewa_perhari' => 140000,
                'is_available' => false,
                'category_id' => $bodyOnly?->id,
            ],

            // Lensa
            [
                'nama' => 'Lensa Sony 24-70mm f/2.8 GM',
                'deskripsi' => 'Lensa zoom versatile dengan aperture konstan f/2.8, desain G Master berkualitas tinggi. Untuk berbagai kebutuhan fotografi.',
                'gambar' => null,
                'harga_sewa_perhari' => 90000,
                'is_available' => true,
                'category_id' => $lensa?->id,
            ],
            [
                'nama' => 'Canon EF 70-200mm f/2.8L IS III',
                'deskripsi' => 'Lensa telephoto profesional dengan image stabilization, bokeh indah, dan build quality tinggi. Cocok untuk portrait & event.',
                'gambar' => null,
                'harga_sewa_perhari' => 100000,
                'is_available' => true,
                'category_id' => $lensa?->id,
            ],
            [
                'nama' => 'Sigma 35mm f/1.4 Art',
                'deskripsi' => 'Lensa prime dengan aperture lebar f/1.4, ketajaman luar biasa, dan bokeh creamy. Ideal untuk street & landscape photography.',
                'gambar' => null,
                'harga_sewa_perhari' => 60000,
                'is_available' => true,
                'category_id' => $lensa?->id,
            ],
            [
                'nama' => 'Tamron 150-600mm f/5-6.3 G2',
                'deskripsi' => 'Lensa super telephoto untuk wildlife dan sports photography. Dilengkapi VC (Vibration Compensation) untuk hasil tajam.',
                'gambar' => null,
                'harga_sewa_perhari' => 85000,
                'is_available' => true,
                'category_id' => $lensa?->id,
            ],

            // Flash
            [
                'nama' => 'Godox V1 Flash',
                'deskripsi' => 'Flash bulat dengan HSS, TTL, modeling lamp LED, dan baterai lithium rechargeable. Compatible dengan Sony, Canon, Nikon.',
                'gambar' => null,
                'harga_sewa_perhari' => 45000,
                'is_available' => true,
                'category_id' => $flash?->id,
            ],
            [
                'nama' => 'Profoto B10 Plus',
                'deskripsi' => 'Studio flash portable 500Ws dengan baterai built-in, Bluetooth control, dan modeling light LED. Professional lighting solution.',
                'gambar' => null,
                'harga_sewa_perhari' => 200000,
                'is_available' => true,
                'category_id' => $flash?->id,
            ],

            // Aksesoris
            [
                'nama' => 'DJI Ronin SC Gimbal',
                'deskripsi' => 'Gimbal 3-axis untuk mirrorless camera, stabilisasi smooth, mode sport, dan kontrol via app. Payload hingga 2kg.',
                'gambar' => null,
                'harga_sewa_perhari' => 75000,
                'is_available' => true,
                'category_id' => $aksesoris?->id,
            ],
            [
                'nama' => 'Manfrotto 055 Tripod + Ball Head',
                'deskripsi' => 'Tripod aluminium profesional dengan ball head, maximum height 170cm, load capacity 9kg. Stabil untuk berbagai kondisi.',
                'gambar' => null,
                'harga_sewa_perhari' => 40000,
                'is_available' => true,
                'category_id' => $aksesoris?->id,
            ],
        ];

        foreach ($products as $product) {
            $product['slug'] = Str::slug($product['nama']);
            Product::create($product);
        }

        // ==========================================
        // 5. BOOKINGS (Dengan Booking Code Manual)
        // ==========================================
        $this->command->info('📝 Creating bookings with booking codes...');
        
        $bookingData = [
            // Booking 1 - Approved (Sedang Berjalan)
            [
                'booking_code' => $this->generateUniqueBookingCode(),
                'product_id' => 1,
                'nama_pemesan' => 'Budi Santoso',
                'nomor_wa' => '081234567890',
                'tanggal_mulai' => now()->subDays(1)->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(2)->format('Y-m-d'),
                'jumlah' => 1,
                'durasi_hari' => 3,
                'status' => 'approved',
                'total_harga' => 150000 * 3,
                'catatan' => 'Untuk event wedding di Bali. Butuh extra battery juga.',
            ],

            // Booking 2 - Pending (Menunggu Konfirmasi)
            [
                'booking_code' => $this->generateUniqueBookingCode(),
                'product_id' => 2,
                'nama_pemesan' => 'Sinta Dewi',
                'nomor_wa' => '089876543210',
                'tanggal_mulai' => now()->addDays(5)->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(7)->format('Y-m-d'),
                'jumlah' => 1,
                'durasi_hari' => 2,
                'status' => 'pending',
                'total_harga' => 170000 * 2,
                'catatan' => 'Butuh untuk photoshoot produk fashion.',
            ],

            // Booking 3 - Completed (Sudah Selesai)
            [
                'booking_code' => $this->generateUniqueBookingCode(),
                'product_id' => 5,
                'nama_pemesan' => 'Ahmad Rizki',
                'nomor_wa' => '081298765432',
                'tanggal_mulai' => now()->subDays(10)->format('Y-m-d'),
                'tanggal_selesai' => now()->subDays(8)->format('Y-m-d'),
                'jumlah' => 1,
                'durasi_hari' => 2,
                'status' => 'completed',
                'total_harga' => 90000 * 2,
                'catatan' => 'Terima kasih, pelayanan memuaskan!',
            ],

            // Booking 4 - Multiple Items Part 1
            [
                'booking_code' => $this->generateUniqueBookingCode(),
                'product_id' => 3,
                'nama_pemesan' => 'Rina Puspita',
                'nomor_wa' => '082134567890',
                'tanggal_mulai' => now()->addDays(3)->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(6)->format('Y-m-d'),
                'jumlah' => 1,
                'durasi_hari' => 3,
                'status' => 'pending',
                'total_harga' => 120000 * 3,
                'catatan' => 'Untuk liburan ke Jogja, butuh kamera ringan.',
            ],

            // Booking 5 - Multiple Items Part 2 (Same customer)
            [
                'booking_code' => $this->generateUniqueBookingCode(),
                'product_id' => 7,
                'nama_pemesan' => 'Rina Puspita',
                'nomor_wa' => '082134567890',
                'tanggal_mulai' => now()->addDays(3)->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(6)->format('Y-m-d'),
                'jumlah' => 1,
                'durasi_hari' => 3,
                'status' => 'pending',
                'total_harga' => 60000 * 3,
                'catatan' => 'Untuk liburan ke Jogja, butuh kamera ringan.',
            ],

            // Booking 6 - Rejected
            [
                'booking_code' => $this->generateUniqueBookingCode(),
                'product_id' => 4,
                'nama_pemesan' => 'Doni Prasetyo',
                'nomor_wa' => '085678912345',
                'tanggal_mulai' => now()->addDays(1)->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(2)->format('Y-m-d'),
                'jumlah' => 1,
                'durasi_hari' => 1,
                'status' => 'rejected',
                'total_harga' => 140000 * 1,
                'catatan' => 'Maaf, produk sedang dalam maintenance.',
            ],

            // Booking 7 - Cancelled
            [
                'booking_code' => $this->generateUniqueBookingCode(),
                'product_id' => 9,
                'nama_pemesan' => 'Lisa Anggraini',
                'nomor_wa' => '081567894321',
                'tanggal_mulai' => now()->addDays(10)->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(11)->format('Y-m-d'),
                'jumlah' => 2,
                'durasi_hari' => 1,
                'status' => 'cancelled',
                'total_harga' => 45000 * 2 * 1,
                'catatan' => 'Dibatalkan oleh customer karena perubahan jadwal.',
            ],

            // Booking 8 - Approved dengan multiple qty
            [
                'booking_code' => $this->generateUniqueBookingCode(),
                'product_id' => 11,
                'nama_pemesan' => 'Andi Wijaya',
                'nomor_wa' => '081234567899',
                'tanggal_mulai' => now()->addDays(7)->format('Y-m-d'),
                'tanggal_selesai' => now()->addDays(9)->format('Y-m-d'),
                'jumlah' => 3,
                'durasi_hari' => 2,
                'status' => 'approved',
                'total_harga' => 75000 * 3 * 2,
                'catatan' => 'Untuk workshop photography, butuh 3 unit gimbal.',
            ],
        ];

        foreach ($bookingData as $booking) {
            $created = Booking::create($booking);
            $this->command->info("  ✅ Booking #{$created->id} - Code: {$created->booking_code} - {$created->nama_pemesan}");
        }

        // ==========================================
        // 6. SETTINGS
        // ==========================================
        $this->command->info('⚙️  Creating settings...');
        
        Setting::create([
            'domain' => '127.0.0.1:8000',
            'name' => 'Kamera Rental Pro',
            'tag' => 'Sewa Kamera & Peralatan Fotografi Profesional',
            'icon' => '',
            'logo' => '',
            'meta_author' => '@kamerarentalpro',
            'meta_description' => 'Layanan sewa kamera, lensa, dan aksesoris fotografi berkualitas tinggi untuk kebutuhan profesional dan personal.',
            'meta_keywords' => 'sewa kamera, rental kamera, sewa lensa, rental fotografi, kamera profesional, sewa sony, sewa canon',
        ]);

        // ==========================================
        // 7. SOCIAL LINKS
        // ==========================================
        $this->command->info('🔗 Creating social links...');
        
        SocialLink::insert([
            [
                'icon' => 'mdi mdi-instagram',
                'name' => 'Instagram',
                'link' => 'https://instagram.com/kamerarentalpro',
                'sort' => 1,
            ],
            [
                'icon' => 'mdi mdi-facebook',
                'name' => 'Facebook',
                'link' => 'https://facebook.com/kamerarentalpro',
                'sort' => 2,
            ],
            [
                'icon' => 'mdi mdi-whatsapp',
                'name' => 'WhatsApp',
                'link' => 'https://wa.me/62895381587961',
                'sort' => 3,
            ],
            [
                'icon' => 'mdi mdi-email',
                'name' => 'Email',
                'link' => 'mailto:info@kamerarentalpro.com',
                'sort' => 4,
            ],
        ]);

        // ==========================================
        // SUMMARY
        // ==========================================
        $this->command->newLine();
        $this->command->info('✅ Database seeded successfully!');
        $this->command->newLine();
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('📊 SEEDING SUMMARY');
        $this->command->info('═══════════════════════════════════════');
        $this->command->info('👥 Users: ' . User::count());
        $this->command->info('📦 Products: ' . Product::count());
        $this->command->info('📝 Bookings: ' . Booking::count());
        $this->command->info('📂 Categories: ' . Category::count());
        $this->command->info('⚙️  Settings: ' . Setting::count());
        $this->command->info('🔗 Social Links: ' . SocialLink::count());
        $this->command->info('═══════════════════════════════════════');
        $this->command->newLine();
        $this->command->info('🔐 LOGIN CREDENTIALS:');
        $this->command->info('📧 Email: admin@gmail.com');
        $this->command->info('🔑 Password: password');
        $this->command->newLine();
    }

    /**
     * Generate unique booking code
     * Format: BK-YYYYMMDD-XXXX
     */
    private function generateUniqueBookingCode(): string
    {
        do {
            $code = 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}