<script setup>
import { ref, computed, watch, onMounted, inject } from "vue";
import { Link, usePage, router, Head } from "@inertiajs/vue3";
import Navbar from "@/Components/Navbar.vue";
import { useHead } from "@vueuse/head";
const { props } = usePage();
const helpers = inject("helpers");
const product = ref(props.product);
useHead({
    script: [
        {
            type: "application/ld+json",
            children: JSON.stringify({
                "@context": "https://schema.org/",
                "@type": "Product",
                name: props.product.nama,
                image: props.product.gambar,
                description: props.product.deskripsi,
                sku: props.product.id,
                brand: {
                    "@type": "Brand",
                    name: "Quick Rental Kamera Jepara",
                },
                offers: {
                    "@type": "Offer",
                    url: `https://quickrental.my.id/product/${props.product.slug}`,
                    priceCurrency: "IDR",
                    price: props.product.harga_sewa_perhari,
                    availability: "http://schema.org/InStock",
                },
            }),
        },
    ],
});
const showBookerInfo = ref(false);
const bookedDates = ref(props.bookedDates || []); // Array of {start, end, status}
const normalizeDate = (iso) => iso.split("T")[0];

bookedDates.value = bookedDates.value.map((b) => ({
    start: normalizeDate(b.start),
    end: normalizeDate(b.end),
    status: b.status,
}));
// Form data
const rentalForm = ref({
    tanggal_mulai: "",
    tanggal_selesai: "",
    catatan: "",
    gambar: "",
    nama_pemesan: "",
    nomor_wa: "",
    jumlah: 1,
});
const handleSubmit = () => {
    if (!showBookerInfo.value) {
        // Tampilkan form info pemesan
        showBookerInfo.value = true;
    } else {
        // Submit booking
        submitBooking();
    }
};
const isFormValidForBooking = computed(() => {
    if (!showBookerInfo.value) {
        return (
            rentalForm.value.tanggal_mulai &&
            rentalForm.value.tanggal_selesai &&
            !props.hasBookedDatesInRange
        );
    }
    return (
        rentalForm.value.nama_pemesan &&
        rentalForm.value.nomor_wa &&
        rentalForm.value.nomor_wa.length >= 10 &&
        rentalForm.value.tanggal_mulai &&
        rentalForm.value.tanggal_selesai &&
        !props.hasBookedDatesInRange
    );
});
const isFormValidForCart = computed(() => {
    return (
        rentalForm.value.tanggal_mulai &&
        rentalForm.value.tanggal_selesai &&
        !props.hasBookedDatesInRange
    );
});

const selectedImage = ref(0);
const quantity = ref(1);
const showDateWarning = ref(false);
const showDescription = ref(false);
const showTerms = ref(false);
const showHours = ref(false);
// Sample images
const productImages = ref([
    product.value.gambar,
    product.value.gambar,
    product.value.gambar,
]);

// Check if date is booked and get its status
const getDateStatus = (date) => {
    for (const booking of bookedDates.value) {
        if (date >= booking.start && date <= booking.end) {
            return booking.status;
        }
    }
    return null;
};

// Check if date range contains booked dates (approved only)
const hasBookedDatesInRange = computed(() => {
    if (!rentalForm.value.tanggal_mulai || !rentalForm.value.tanggal_selesai) {
        return false;
    }

    const start = rentalForm.value.tanggal_mulai; // sudah string "YYYY-MM-DD"
    const end = rentalForm.value.tanggal_selesai;

    // Loop menggunakan string comparison
    const startDate = new Date(start + "T00:00:00"); // Force local timezone
    const endDate = new Date(end + "T00:00:00");

    const currentDate = new Date(startDate);

    while (currentDate <= endDate) {
        const year = currentDate.getFullYear();
        const month = String(currentDate.getMonth() + 1).padStart(2, "0");
        const day = String(currentDate.getDate()).padStart(2, "0");
        const dateString = `${year}-${month}-${day}`;

        const status = getDateStatus(dateString);

        if (status === "approved") {
            return true;
        }

        currentDate.setDate(currentDate.getDate() + 1);
    }

    return false;
});

// Watch for date changes to show warnings
watch(
    [
        () => rentalForm.value.tanggal_mulai,
        () => rentalForm.value.tanggal_selesai,
    ],
    () => {
        if (hasBookedDatesInRange.value) {
            showDateWarning.value = true;
        } else {
            showDateWarning.value = false;
        }
    }
);

// Calculate rental duration
const rentalDuration = computed(() => {
    if (rentalForm.value.tanggal_mulai && rentalForm.value.tanggal_selesai) {
        const start = new Date(rentalForm.value.tanggal_mulai);
        const end = new Date(rentalForm.value.tanggal_selesai);
        // Tambah 1 hari agar tanggal yang sama dihitung sebagai 1 hari
        // 15-15 = 0 + 1 = 1 hari
        // 15-16 = 1 + 1 = 2 hari
        const days = Math.ceil((end - start) / (1000 * 60 * 60 * 24)) + 1;
        return days > 0 ? days : 0;
    }
    return 0;
});

// Calculate total price
const totalPrice = computed(() => {
    return (
        product.value.harga_sewa_perhari * rentalDuration.value * quantity.value
    );
});

// Check if form is valid
const isFormValid = computed(() => {
    return (
        rentalForm.value.tanggal_mulai &&
        rentalForm.value.tanggal_selesai &&
        rentalForm.value.nama_pemesan.trim() !== "" &&
        rentalForm.value.nomor_wa.trim() !== "" &&
        rentalForm.value.nomor_wa.length >= 10 &&
        !hasBookedDatesInRange.value &&
        rentalDuration.value > 0
    );
});
const formatCurrency = (value) => {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString("id-ID", {
        weekday: "long",
        year: "numeric",
        month: "long",
        day: "numeric",
    });
};

const submitBooking = () => {
    if (!isFormValid.value) {
        return;
    }

    // Format nomor WA: tambahkan +62 jika diawali 0
    let formattedPhone = rentalForm.value.nomor_wa.trim();
    if (formattedPhone.startsWith("0")) {
        formattedPhone = "+62" + formattedPhone.substring(1);
    } else if (!formattedPhone.startsWith("+")) {
        formattedPhone = "+62" + formattedPhone;
    }

    const payload = {
        tanggal_mulai: rentalForm.value.tanggal_mulai,
        tanggal_selesai: rentalForm.value.tanggal_selesai,
        jumlah: quantity.value,
        image: rentalForm.value.gambar,
        catatan: rentalForm.value.catatan,
        nama_pemesan: rentalForm.value.nama_pemesan.trim(),
        nomor_wa: formattedPhone,
    };

    router.post(`/product/${product.value.id}/booking`, payload, {
        onSuccess: (page) => {
            if (page.props.whatsappUrl) {
                window.location.href = page.props.whatsappUrl;
            }
        },
        onError: (errors) => {
            console.error("Booking failed:", errors);
        },
    });
};
const cart = ref([]);
const cartCount = ref(0);

// Load cart dari localStorage saat component mounted
onMounted(() => {
    loadCart();
});

// Function untuk load cart
const loadCart = () => {
    const savedCart = localStorage.getItem("cart");
    if (savedCart) {
        cart.value = JSON.parse(savedCart);
        cartCount.value = cart.value.length;
    }
};

// Function untuk save cart
const saveCart = () => {
    localStorage.setItem("cart", JSON.stringify(cart.value));
    cartCount.value = cart.value.length;
};
const addToCart = () => {
    // Buat cart item
    const cartItem = {
        id: product.value.id, // Unique ID untuk cart item
        product_id: Date.now(),
        product_nama: product.value.nama,
        product_gambar: product.value.gambar,
        product_harga: product.value.harga_sewa_perhari,
        tanggal_mulai: rentalForm.value.tanggal_mulai,
        tanggal_selesai: rentalForm.value.tanggal_selesai,
        jumlah: quantity.value,
        durasi: rentalDuration.value,
        total_harga: totalPrice.value,
        catatan: rentalForm.value.catatan,
        created_at: new Date().toISOString(),
    };

    // Cek apakah produk sudah ada di cart dengan tanggal yang sama
    const existingIndex = cart.value.findIndex(
        (item) =>
            item.product_id === cartItem.product_id &&
            item.tanggal_mulai === cartItem.tanggal_mulai &&
            item.tanggal_selesai === cartItem.tanggal_selesai
    );

    if (existingIndex !== -1) {
        // Update jumlah jika sudah ada
        cart.value[existingIndex].jumlah += cartItem.jumlah;
        cart.value[existingIndex].total_harga =
            cart.value[existingIndex].jumlah *
            cart.value[existingIndex].product_harga *
            cart.value[existingIndex].durasi;
    } else {
        // Tambah item baru
        cart.value.push(cartItem);
    }

    // Save ke localStorage
    saveCart();

    // Show success notification
    alert(`✅ ${product.value.nama} berhasil ditambahkan ke keranjang!`);

    // Optional: Reset form
    // rentalForm.value.tanggal_mulai = "";
    // rentalForm.value.tanggal_selesai = "";
    // rentalForm.value.catatan = "";
    // quantity.value = 1;
};
// Get minimum date (today)
const minDate = new Date().toISOString().split("T")[0];

// Format booked dates untuk ditampilkan
const formatBookedDatesDisplay = computed(() => {
    if (bookedDates.value.length === 0) return [];

    // Group consecutive dates
    const grouped = [];
    let currentGroup = [];

    const sortedDates = [...bookedDates.value].sort();

    sortedDates.forEach((dateStr, index) => {
        if (index === 0) {
            currentGroup.push(dateStr);
        } else {
            const prevDate = new Date(sortedDates[index - 1]);
            const currDate = new Date(dateStr);
            const diffDays = (currDate - prevDate) / (1000 * 60 * 60 * 24);

            if (diffDays === 1) {
                currentGroup.push(dateStr);
            } else {
                grouped.push([...currentGroup]);
                currentGroup = [dateStr];
            }
        }
    });

    if (currentGroup.length > 0) {
        grouped.push(currentGroup);
    }

    return grouped.map((group) => {
        if (group.length === 1) {
            return formatDate(group[0]);
        } else {
            return `${formatDate(group[0])} - ${formatDate(
                group[group.length - 1]
            )}`;
        }
    });
});

// Calendar functionality
const currentMonth = ref(new Date());
const showCalendar = ref(true);

const calendarDays = computed(() => {
    const year = currentMonth.value.getFullYear();
    const month = currentMonth.value.getMonth();

    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDay = firstDay.getDay();

    const days = [];

    // Add empty cells for days before month starts
    for (let i = 0; i < startDay; i++) {
        days.push(null);
    }

    // Add all days of the month
    for (let day = 1; day <= lastDay.getDate(); day++) {
        // ✅ Buat date string langsung tanpa timezone issue
        const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(
            day
        ).padStart(2, "0")}`;

        const status = getDateStatus(dateStr);

        // Untuk isPast dan isToday, gunakan date string comparison
        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(
            today.getMonth() + 1
        ).padStart(2, "0")}-${String(today.getDate()).padStart(2, "0")}`;

        const isPast = dateStr < todayStr;
        const isToday = dateStr === todayStr;

        days.push({
            day,
            date: dateStr,
            status,
            isPast,
            isToday,
        });
    }

    return days;
});

const monthNames = [
    "Januari",
    "Februari",
    "Maret",
    "April",
    "Mei",
    "Juni",
    "Juli",
    "Agustus",
    "September",
    "Oktober",
    "November",
    "Desember",
];
const dayNames = ["Min", "Sen", "Sel", "Rab", "Kam", "Jum", "Sab"];

const currentMonthName = computed(() => {
    return `${
        monthNames[currentMonth.value.getMonth()]
    } ${currentMonth.value.getFullYear()}`;
});

const previousMonth = () => {
    currentMonth.value = new Date(
        currentMonth.value.getFullYear(),
        currentMonth.value.getMonth() - 1,
        1
    );
};

const nextMonth = () => {
    currentMonth.value = new Date(
        currentMonth.value.getFullYear(),
        currentMonth.value.getMonth() + 1,
        1
    );
};
</script>

<template>
    <Head>
        <title>{{ product.nama }}</title>
        <meta name="description" content="{{ product.deskripsi }}" />
        <meta name="keywords" content="{{ product.slug }}" />
        <meta property="og:title" content="{{ title }}" />
        <meta property="og:description" content="{{ product.deskripsi }}" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://quickrental.my.id" />
    </Head>
    <div class="min-h-screen bg-gray-50">
        <!-- Navbar -->
        <Navbar />

        <!-- Breadcrumb -->
        <div class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <Link href="/" class="hover:text-blue-600">Beranda</Link>

                    <span>/</span>
                    <span class="text-gray-900 font-medium">{{
                        product.nama
                    }}</span>
                </div>
            </div>
        </div>

        <!-- Product Detail -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-12">
                <!-- Left: Images -->
                <div>
                    <!-- Main Image -->
                    <div
                        class="bg-white rounded-2xl shadow-lg overflow-hidden mb-4"
                    >
                        <img
                            :src="
                                helpers.imageUrl(productImages[selectedImage])
                            "
                            :alt="product.nama"
                            class="w-full h-96 object-cover"
                        />
                    </div>

                    <!-- Calendar Availability -->
                    <div class="bg-white rounded-2xl shadow-lg p-6 mt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-bold text-gray-700">
                                📅 Ketersediaan
                            </h3>
                            <button
                                @click="showCalendar = !showCalendar"
                                class="text-sm text-gray-600 hover:text-gray-700 font-medium"
                            >
                                {{ showCalendar ? "Sembunyikan" : "Tampilkan" }}
                            </button>
                        </div>

                        <div v-show="showCalendar">
                            <!-- Calendar Header -->
                            <div
                                class="flex items-center justify-between mb-4 text-gray-600"
                            >
                                <button
                                    @click="previousMonth"
                                    class="p-2 hover:bg-gray-100 rounded-lg transition"
                                >
                                    ◀
                                </button>
                                <h4 class="font-bold text-gray-600">
                                    {{ currentMonthName }}
                                </h4>
                                <button
                                    @click="nextMonth"
                                    class="p-2 hover:bg-gray-100 rounded-lg transition"
                                >
                                    ▶
                                </button>
                            </div>

                            <!-- Day names -->
                            <div class="grid grid-cols-7 gap-1 mb-2">
                                <div
                                    v-for="dayName in dayNames"
                                    :key="dayName"
                                    class="text-center text-xs font-semibold text-gray-600 py-2"
                                >
                                    {{ dayName }}
                                </div>
                            </div>

                            <!-- Calendar days -->
                            <div class="grid grid-cols-7 gap-1">
                                <div
                                    v-for="(dayData, index) in calendarDays"
                                    :key="index"
                                    :class="[
                                        'aspect-square flex items-center justify-center text-sm rounded-lg transition relative',
                                        !dayData ? 'invisible' : '',
                                        dayData && dayData.isPast
                                            ? 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                            : '',
                                        dayData &&
                                        dayData.status === 'approved' &&
                                        !dayData.isPast
                                            ? 'bg-red-500 text-white font-bold cursor-not-allowed'
                                            : '',
                                        dayData &&
                                        dayData.status === 'pending' &&
                                        !dayData.isPast
                                            ? 'bg-orange-100 text-orange-700 font-bold'
                                            : '',
                                        dayData &&
                                        !dayData.status &&
                                        !dayData.isPast
                                            ? 'bg-green-50 text-green-700 hover:bg-green-100 cursor-pointer font-medium'
                                            : '',
                                        dayData && dayData.isToday
                                            ? 'ring-2 ring-blue-500'
                                            : '',
                                    ]"
                                    :title="
                                        dayData && dayData.status
                                            ? `Status: ${dayData.status}`
                                            : ''
                                    "
                                >
                                    <span v-if="dayData">{{
                                        dayData.day
                                    }}</span>
                                    <!-- Status indicator badge -->
                                    <span
                                        v-if="
                                            dayData &&
                                            dayData.status === 'pending'
                                        "
                                        class="absolute top-0 right-0 w-2 h-2 bg-orange-500 rounded-full"
                                    ></span>
                                    <span
                                        v-if="
                                            dayData &&
                                            dayData.status === 'approved'
                                        "
                                        class="absolute top-0 right-0 w-2 h-2 bg-red-700 rounded-full"
                                    ></span>
                                </div>
                            </div>

                            <!-- Legend -->
                            <div class="mt-4 pt-4 border-t space-y-2">
                                <div
                                    class="flex items-center space-x-2 text-sm"
                                >
                                    <div
                                        class="w-4 h-4 bg-green-50 border border-green-200 rounded"
                                    ></div>
                                    <span class="text-gray-600"
                                        >Tersedia untuk booking</span
                                    >
                                </div>
                                <div
                                    class="flex items-center space-x-2 text-sm"
                                >
                                    <div
                                        class="w-4 h-4 bg-orange-100 border border-orange-300 rounded"
                                    ></div>
                                    <span class="text-gray-600"
                                        >Pending (Menunggu konfirmasi)</span
                                    >
                                </div>
                                <div
                                    class="flex items-center space-x-2 text-sm"
                                >
                                    <div
                                        class="w-4 h-4 bg-red-500 rounded"
                                    ></div>
                                    <span class="text-gray-600"
                                        >Approved (Sudah dibooking)</span
                                    >
                                </div>
                                <div
                                    class="flex items-center space-x-2 text-sm"
                                >
                                    <div
                                        class="w-4 h-4 bg-gray-100 border border-gray-300 rounded"
                                    ></div>
                                    <span class="text-gray-600"
                                        >Tanggal lampau</span
                                    >
                                </div>
                                <div
                                    class="flex items-center space-x-2 text-sm"
                                >
                                    <div
                                        class="w-4 h-4 bg-white border-2 border-blue-500 rounded"
                                    ></div>
                                    <span class="text-gray-600">Hari ini</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Details & Booking -->
                <div>
                    <div class="bg-white rounded-2xl shadow-lg p-8">
                        <!-- Title & Rating -->
                        <div class="mb-6">
                            <h1 class="text-3xl font-bold text-gray-700 mb-3">
                                {{ product.nama }}
                            </h1>
                            <div class="flex items-center space-x-4 mb-3">
                                <div class="flex items-center text-yellow-500">
                                    ⭐⭐⭐⭐⭐
                                    <span class="text-gray-600 ml-2">4.9</span>
                                </div>
                                <span class="text-gray-500">|</span>
                                <span class="text-gray-600">128 Ulasan</span>
                                <span class="text-gray-500">|</span>
                                <span class="text-green-600 font-medium"
                                    >✓ Tersedia</span
                                >
                            </div>
                            <div class="flex items-baseline space-x-2">
                                <span class="text-4xl font-bold text-gray-700">
                                    {{
                                        formatCurrency(
                                            product.harga_sewa_perhari
                                        )
                                    }}
                                </span>
                                <span class="text-gray-500">/ hari</span>
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="mb-6 pb-6 border-b text-gray-700">
                            <!-- Toggle Header -->
                            <div
                                class="flex items-center justify-between cursor-pointer"
                                @click="showDescription = !showDescription"
                            >
                                <h3 class="font-bold text-lg">Deskripsi</h3>
                                <span>
                                    <i
                                        :class="
                                            showDescription
                                                ? 'mdi mdi-chevron-up'
                                                : 'mdi mdi-chevron-down'
                                        "
                                        class="text-xl"
                                    ></i>
                                </span>
                            </div>

                            <!-- Description Content -->
                            <div v-if="showDescription" class="mt-3">
                                <p class="text-gray-600 leading-relaxed">
                                    {{ product.deskripsi }}
                                </p>
                            </div>
                        </div>

                        <!-- SYARAT & KETENTUAN -->
                        <div class="mb-6 pb-6 border-b text-gray-700">
                            <div
                                class="flex items-center justify-between cursor-pointer"
                                @click="showTerms = !showTerms"
                            >
                                <h3 class="font-bold text-lg">
                                    Syarat & Ketentuan Penyewaan
                                </h3>
                                <span>
                                    <i
                                        :class="
                                            showTerms
                                                ? 'mdi mdi-chevron-up'
                                                : 'mdi mdi-chevron-down'
                                        "
                                        class="text-xl"
                                    ></i>
                                </span>
                            </div>

                            <div
                                v-if="showTerms"
                                class="mt-3 text-gray-600 leading-relaxed space-y-2"
                            >
                                <p>
                                    • Wajib menyerahkan identitas asli (KTP/SIM)
                                    saat pengambilan unit.
                                </p>
                                <p>
                                    • Penyewa bertanggung jawab penuh atas
                                    kerusakan/hilangnya unit.
                                </p>
                                <p>
                                    • Pembatalan H-1 dikenakan biaya 20% dari
                                    total sewa.
                                </p>
                                <p>
                                    • Jika booking dilakukan lebih dari
                                    <strong>H+7</strong> sebelum hari sewa,
                                    wajib DP <strong>10%</strong> dari total
                                    penyewaan.
                                </p>
                                <p>
                                    • Harga belum termasuk biaya antar-jemput
                                    unit.
                                </p>
                                <p>
                                    • Dilarang menggunakan unit untuk kegiatan
                                    ilegal.
                                </p>
                            </div>
                        </div>

                        <!-- JAM OPERASIONAL -->
                        <div class="mb-6 pb-6 border-b text-gray-700">
                            <div
                                class="flex items-center justify-between cursor-pointer"
                                @click="showHours = !showHours"
                            >
                                <h3 class="font-bold text-lg">
                                    Jam Operasional
                                </h3>
                                <span>
                                    <i
                                        :class="
                                            showHours
                                                ? 'mdi mdi-chevron-up'
                                                : 'mdi mdi-chevron-down'
                                        "
                                        class="text-xl"
                                    ></i>
                                </span>
                            </div>

                            <div
                                v-if="showHours"
                                class="mt-3 text-gray-600 leading-relaxed"
                            >
                                <p>
                                    Buka setiap hari pukul
                                    <strong>07:00 — 23:00 WIB</strong>.
                                </p>
                                <p>
                                    Pemesanan di luar jam operasional akan
                                    diproses pada jam kerja.
                                </p>
                            </div>
                        </div>

                        <!-- Date Warning Alert -->
                        <div v-if="showDateWarning">
                            <transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                                leave-active-class="transition duration-200 ease-in"
                                leave-from-class="opacity-100 scale-100"
                                leave-to-class="opacity-0 scale-95"
                            >
                                <div
                                    v-if="hasBookedDatesInRange"
                                    class="mt-6 bg-red-50 border-2 border-red-200 rounded-xl p-4 flex items-start gap-3"
                                >
                                    <svg
                                        class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <div>
                                        <p class="font-semibold text-red-800">
                                            Tanggal tidak tersedia
                                        </p>
                                        <p class="text-sm text-red-600 mt-1">
                                            Terdapat tanggal yang sudah
                                            dibooking pada rentang waktu yang
                                            Anda pilih. Silakan pilih tanggal
                                            lain.
                                        </p>
                                    </div>
                                </div>
                            </transition>

                            <!-- Info Durasi -->
                            <transition
                                enter-active-class="transition duration-300 ease-out"
                                enter-from-class="opacity-0 scale-95"
                                enter-to-class="opacity-100 scale-100"
                            >
                                <div
                                    v-if="
                                        rentalForm.tanggal_mulai &&
                                        rentalForm.tanggal_selesai &&
                                        !hasBookedDatesInRange
                                    "
                                    class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-xl p-4"
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="bg-blue-500 rounded-full p-2"
                                            >
                                                <svg
                                                    class="w-5 h-5 text-white"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                                    />
                                                </svg>
                                            </div>
                                            <div>
                                                <p
                                                    class="text-sm text-gray-600"
                                                >
                                                    Durasi Rental
                                                </p>
                                                <p
                                                    class="text-xl font-bold text-gray-800"
                                                >
                                                    {{ calculateDuration() }}
                                                    Hari
                                                </p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500">
                                                Dari
                                            </p>
                                            <p
                                                class="text-sm font-semibold text-gray-700"
                                            >
                                                {{
                                                    formatDate(
                                                        rentalForm.tanggal_mulai
                                                    )
                                                }}
                                            </p>
                                            <p
                                                class="text-xs text-gray-500 mt-1"
                                            >
                                                Sampai
                                            </p>
                                            <p
                                                class="text-sm font-semibold text-gray-700"
                                            >
                                                {{
                                                    formatDate(
                                                        rentalForm.tanggal_selesai
                                                    )
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </transition>
                        </div>

                        <!-- Booking Form -->
                        <form
                            @submit.prevent="handleSubmit"
                            class="space-y-6 pt-2"
                        >
                            <!-- Informasi Pemesan - Hanya muncul jika showBookerInfo = true -->
                            <div v-if="showBookerInfo" class="space-y-4">
                                <p class="font-semibold text-gray-700 text-sm">
                                    Informasi Pemesan :
                                </p>

                                <!-- Nama Pemesan -->
                                <div class="group">
                                    <label
                                        class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-3"
                                    >
                                        <svg
                                            class="w-4 h-4 text-blue-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                            />
                                        </svg>
                                        Nama Lengkap
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="rentalForm.nama_pemesan"
                                            type="text"
                                            required
                                            placeholder="Masukkan nama lengkap Anda"
                                            class="w-full px-5 py-4 pr-12 border-2 rounded-xl font-medium text-gray-700 transition-all duration-300 focus:outline-none focus:ring-4 border-gray-200 bg-white focus:border-blue-500 focus:ring-blue-100 hover:border-blue-300 hover:shadow-md"
                                        />
                                        <div
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                                        >
                                            <svg
                                                v-if="rentalForm.nama_pemesan"
                                                class="w-5 h-5 text-green-500"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M5 13l4 4L19 7"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-5 h-5 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                </div>

                                <!-- Nomor WhatsApp -->
                                <div class="group">
                                    <label
                                        class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-3"
                                    >
                                        <svg
                                            class="w-4 h-4 text-green-600"
                                            fill="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                                            />
                                        </svg>
                                        Nomor WhatsApp
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="rentalForm.nomor_wa"
                                            type="tel"
                                            required
                                            placeholder="Contoh: 089532873283"
                                            pattern="[0-9]{10,13}"
                                            class="w-full px-5 py-4 pr-12 border-2 rounded-xl font-medium text-gray-700 transition-all duration-300 focus:outline-none focus:ring-4 border-gray-200 bg-white focus:border-green-500 focus:ring-green-100 hover:border-green-300 hover:shadow-md"
                                        />
                                        <div
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                                        >
                                            <svg
                                                v-if="
                                                    rentalForm.nomor_wa &&
                                                    rentalForm.nomor_wa
                                                        .length >= 10
                                                "
                                                class="w-5 h-5 text-green-500"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M5 13l4 4L19 7"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-5 h-5 text-gray-400"
                                                fill="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <p
                                        class="mt-2 text-xs text-gray-500 flex items-center gap-1"
                                    >
                                        <svg
                                            class="w-3 h-3"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        Format: 08xx tanpa +62 atau spasi
                                    </p>
                                </div>
                            </div>

                            <p class="font-semibold text-gray-700 text-sm">
                                Tanggal :
                            </p>

                            <!-- Date Range -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="group">
                                    <label
                                        class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-3"
                                    >
                                        <svg
                                            class="w-4 h-4 text-blue-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                        Mulai
                                    </label>

                                    <div class="relative">
                                        <input
                                            v-model="rentalForm.tanggal_mulai"
                                            type="date"
                                            :min="minDate"
                                            required
                                            :class="[
                                                'w-full px-5 py-4 pr-12',
                                                'border-2 rounded-xl',
                                                'font-medium text-gray-700',
                                                'transition-all duration-300',
                                                'focus:outline-none focus:ring-4',
                                                hasBookedDatesInRange
                                                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100'
                                                    : 'border-gray-200 bg-white focus:border-blue-500 focus:ring-blue-100 hover:border-blue-300 hover:shadow-md',
                                            ]"
                                        />
                                        <div
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                                        >
                                            <svg
                                                v-if="!hasBookedDatesInRange"
                                                class="w-5 h-5 text-blue-500"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-5 h-5 text-red-500"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <transition
                                        enter-active-class="transition duration-200 ease-out"
                                        enter-from-class="opacity-0 -translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition duration-150 ease-in"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 -translate-y-1"
                                    >
                                        <p
                                            v-if="rentalForm.tanggal_mulai"
                                            class="mt-2 text-xs text-green-600 font-medium flex items-center gap-1"
                                        >
                                            <svg
                                                class="w-3 h-3"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            Tanggal dipilih
                                        </p>
                                    </transition>
                                </div>

                                <div class="group">
                                    <label
                                        class="flex items-center gap-2 text-sm font-semibold text-gray-700 mb-3"
                                    >
                                        <svg
                                            class="w-4 h-4 text-indigo-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                            />
                                        </svg>
                                        Selesai
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="rentalForm.tanggal_selesai"
                                            type="date"
                                            :min="
                                                rentalForm.tanggal_mulai ||
                                                minDate
                                            "
                                            required
                                            :disabled="
                                                !rentalForm.tanggal_mulai
                                            "
                                            :class="[
                                                'w-full px-5 py-4 pr-12',
                                                'border-2 rounded-xl',
                                                'font-medium text-gray-700',
                                                'transition-all duration-300',
                                                'focus:outline-none focus:ring-4',
                                                !rentalForm.tanggal_mulai &&
                                                    'opacity-50 cursor-not-allowed',
                                                hasBookedDatesInRange
                                                    ? 'border-red-300 bg-red-50 focus:border-red-500 focus:ring-red-100'
                                                    : 'border-gray-200 bg-white focus:border-indigo-500 focus:ring-indigo-100 hover:border-indigo-300 hover:shadow-md',
                                            ]"
                                        />
                                        <div
                                            class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none"
                                        >
                                            <svg
                                                v-if="
                                                    !hasBookedDatesInRange &&
                                                    rentalForm.tanggal_mulai
                                                "
                                                class="w-5 h-5 text-indigo-500"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                            <svg
                                                v-else-if="
                                                    hasBookedDatesInRange
                                                "
                                                class="w-5 h-5 text-red-500"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-5 h-5 text-gray-400"
                                                fill="none"
                                                stroke="currentColor"
                                                viewBox="0 0 24 24"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"
                                                />
                                            </svg>
                                        </div>
                                    </div>
                                    <transition
                                        enter-active-class="transition duration-200 ease-out"
                                        enter-from-class="opacity-0 -translate-y-1"
                                        enter-to-class="opacity-100 translate-y-0"
                                        leave-active-class="transition duration-150 ease-in"
                                        leave-from-class="opacity-100 translate-y-0"
                                        leave-to-class="opacity-0 -translate-y-1"
                                    >
                                        <p
                                            v-if="!rentalForm.tanggal_mulai"
                                            class="mt-2 text-xs text-gray-500 flex items-center gap-1"
                                        >
                                            <svg
                                                class="w-3 h-3"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            Pilih tanggal mulai terlebih dahulu
                                        </p>
                                        <p
                                            v-else-if="
                                                rentalForm.tanggal_selesai
                                            "
                                            class="mt-2 text-xs text-green-600 font-medium flex items-center gap-1"
                                        >
                                            <svg
                                                class="w-3 h-3"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            Tanggal dipilih
                                        </p>
                                    </transition>
                                </div>
                            </div>

                            <!-- Notes -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 mb-2"
                                >
                                    Catatan (Opsional)
                                </label>
                                <textarea
                                    v-model="rentalForm.catatan"
                                    rows="3"
                                    placeholder="Tambahkan catatan untuk pesanan Anda..."
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:border-blue-500"
                                ></textarea>
                            </div>

                            <!-- Summary -->
                            <div
                                v-if="rentalDuration > 0"
                                class="bg-blue-50 rounded-xl p-6 space-y-3"
                            >
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600"
                                        >Durasi Sewa</span
                                    >
                                    <span class="font-bold"
                                        >{{ rentalDuration }} Hari</span
                                    >
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600"
                                        >Harga per Hari</span
                                    >
                                    <span class="font-bold">{{
                                        formatCurrency(
                                            product.harga_sewa_perhari
                                        )
                                    }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Jumlah</span>
                                    <span class="font-bold"
                                        >{{ quantity }} Unit</span
                                    >
                                </div>
                                <div class="border-t pt-3 flex justify-between">
                                    <span class="font-bold text-lg">Total</span>
                                    <span
                                        class="font-bold text-2xl text-blue-600"
                                    >
                                        {{ formatCurrency(totalPrice) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="space-y-3">
                                <button
                                    type="submit"
                                    :disabled="!isFormValidForBooking"
                                    :class="[
                                        'w-full font-bold py-4 rounded-xl transition shadow-lg',
                                        isFormValidForBooking
                                            ? 'bg-blue-600 hover:bg-blue-700 text-white'
                                            : 'bg-gray-300 text-gray-500 cursor-not-allowed',
                                    ]"
                                >
                                    🎯 Booking Sekarang
                                </button>
                                <button
                                    type="button"
                                    @click="addToCart"
                                    :disabled="!isFormValidForCart"
                                    :class="[
                                        'w-full font-bold py-4 rounded-xl transition border-2',
                                        isFormValidForCart
                                            ? 'bg-white border-blue-600 text-blue-600 hover:bg-blue-50'
                                            : 'bg-gray-100 border-gray-300 text-gray-400 cursor-not-allowed',
                                    ]"
                                >
                                    🛒 Tambah ke Keranjang
                                </button>
                            </div>
                        </form>

                        <!-- Additional Info -->
                        <div class="mt-6 pt-6 border-t space-y-3">
                            <div
                                class="flex items-center space-x-3 text-sm text-gray-600"
                            >
                                <span>✅</span>
                                <span
                                    >Gratis antar-jemput area Jepara Kota selama
                                    Penyewaan paket body dan lensa</span
                                >
                            </div>
                            <div
                                class="flex items-center space-x-3 text-sm text-gray-600"
                            >
                                <span>🛡️</span>
                                <span>Garansi peralatan & asuransi</span>
                            </div>
                            <div
                                class="flex items-center space-x-3 text-sm text-gray-600"
                            >
                                <span>💳</span>
                                <span>Pembayaran fleksibel</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
input[type="date"]::-webkit-calendar-picker-indicator {
    cursor: pointer;
}
</style>
