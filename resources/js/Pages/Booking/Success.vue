<script setup>
import { onMounted, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const booking = page.props.booking;
const countdown = ref(3);

onMounted(() => {
    if (booking?.whatsapp_url) {
        const timer = setInterval(() => {
            countdown.value--;
            if (countdown.value === 0) {
                clearInterval(timer);
                window.location.href = booking.whatsapp_url;
            }
        }, 1000);
    }
});
</script>

<template>
    <div
        class="min-h-screen bg-gradient-to-br from-green-50 via-white to-green-100 flex items-center justify-center p-4"
    >
        <div class="max-w-md w-full">
            <!-- Card Container -->
            <div
                class="bg-white rounded-3xl shadow-2xl p-8 text-center transform transition-all duration-500 hover:scale-105"
            >
                <!-- WhatsApp Icon with Animation -->
                <div class="mb-6 relative">
                    <div
                        class="absolute inset-0 bg-green-400 rounded-full blur-2xl opacity-30 animate-pulse"
                    ></div>
                    <div
                        class="relative bg-gradient-to-br from-green-400 to-green-600 w-24 h-24 rounded-full mx-auto flex items-center justify-center shadow-lg animate-bounce"
                    >
                        <svg
                            class="w-14 h-14 text-white"
                            fill="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-800 mb-3">
                    Mengalihkan ke WhatsApp
                </h1>

                <!-- Countdown -->
                <div class="mb-6">
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-3"
                    >
                        <span class="text-3xl font-bold text-green-600">{{
                            countdown
                        }}</span>
                    </div>
                    <p class="text-gray-600">detik lagi...</p>
                </div>

                <!-- Description -->
                <p class="text-gray-500 mb-8">
                    Anda akan dialihkan secara otomatis.<br />
                    Jika tidak, silakan klik tombol di bawah.
                </p>

                <!-- Button -->
                <a
                    :href="booking.whatsapp_url"
                    class="group relative inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-green-500 to-green-600 text-white font-semibold rounded-full overflow-hidden shadow-lg hover:shadow-2xl transform transition-all duration-300 hover:scale-105 active:scale-95"
                >
                    <span
                        class="absolute inset-0 bg-white opacity-0 group-hover:opacity-20 transition-opacity duration-300"
                    ></span>
                    <svg
                        class="w-6 h-6 mr-2"
                        fill="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                        />
                    </svg>
                    <span class="relative">Buka WhatsApp Sekarang</span>
                </a>

                <!-- Loading Indicator -->
                <div class="mt-8 flex justify-center space-x-2">
                    <div
                        class="w-2 h-2 bg-green-500 rounded-full animate-pulse"
                    ></div>
                    <div
                        class="w-2 h-2 bg-green-500 rounded-full animate-pulse"
                        style="animation-delay: 0.2s"
                    ></div>
                    <div
                        class="w-2 h-2 bg-green-500 rounded-full animate-pulse"
                        style="animation-delay: 0.4s"
                    ></div>
                </div>
            </div>

            <!-- Footer Note -->
            <p class="text-center text-gray-400 text-sm mt-6">
                Pastikan WhatsApp sudah terinstall di perangkat Anda
            </p>
        </div>
    </div>
</template>

<style scoped>
@keyframes bounce {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.animate-bounce {
    animation: bounce 2s infinite;
}

@keyframes pulse {
    0%,
    100% {
        opacity: 0.3;
    }
    50% {
        opacity: 0.5;
    }
}

.animate-pulse {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
