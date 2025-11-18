<script setup>
import { onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";

const page = usePage();
const booking = page.props.booking;

onMounted(() => {
    if (booking?.whatsapp_url) {
        window.location.href = booking.whatsapp_url;
    }
});
</script>

<template>
    <div class="text-center p-10">
        <h1 class="text-xl font-bold">Mengalihkan ke WhatsApp...</h1>
        <p>Jika tidak otomatis, klik tombol di bawah:</p>
        <a
            :href="booking.whatsapp_url"
            class="mt-4 inline-block bg-green-600 text-white px-4 py-2 rounded"
        >
            Buka WhatsApp
        </a>
    </div>
</template>
