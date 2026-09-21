<script setup>
import { ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import {
    Users,
    ArrowLeft,
    Car,
    Clock,
    MapPin,
    CheckCircle2,
    ShieldCheck,
    Trash2,
    Calendar
} from 'lucide-vue-next';

const props = defineProps({
    driver: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const user = page.props.auth?.user;

const toggleActiveStatus = () => {
    router.put(`/drivers/${props.driver.id}`, {
        user_id: props.driver.user_id,
        license_number: props.driver.license_number,
        license_expiry: props.driver.license_expiry,
        is_active: !props.driver.is_active,
    });
};

const deleteDriver = () => {
    if (confirm('Delete driver profile? Historical bookings will remain intact.')) {
        router.delete(`/drivers/${props.driver.id}`);
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};
</script>

<template>
    <AppLayout title="Driver Dossier">
        <Head :title="`${driver.user?.name} - Driver Profile`" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        href="/drivers"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-indigo-400 transition-colors mb-2"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" /> Back to Drivers
                    </Link>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-extrabold text-white tracking-tight">{{ driver.user?.name }}</h2>
                        <span
                            class="px-2.5 py-1 rounded-full text-xs font-semibold border"
                            :class="[
                                driver.is_active
                                    ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'
                                    : 'bg-rose-500/15 text-rose-400 border-rose-500/30'
                            ]"
                        >
                            {{ driver.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1">{{ driver.user?.email }} • {{ driver.user?.department?.name || 'Operations' }}</p>
                </div>

                <div v-if="user?.role?.slug === 'super_admin'" class="flex items-center gap-2">
                    <button
                        @click="toggleActiveStatus"
                        class="px-3.5 py-2 rounded-xl text-xs font-semibold border transition-all"
                        :class="[
                            driver.is_active
                                ? 'bg-amber-500/15 text-amber-300 border-amber-500/30 hover:bg-amber-500/25'
                                : 'bg-emerald-500/15 text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/25'
                        ]"
                    >
                        {{ driver.is_active ? 'Deactivate Driver' : 'Activate Driver' }}
                    </button>
                    <button
                        @click="deleteDriver"
                        class="p-2 rounded-xl bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 transition-colors"
                        title="Delete Driver Profile"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Profile Info Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
                    <span class="text-xs text-slate-400 font-medium">Driver License Number</span>
                    <p class="text-lg font-bold text-indigo-400 font-mono mt-1">{{ driver.license_number }}</p>
                </div>

                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
                    <span class="text-xs text-slate-400 font-medium">License Expiration</span>
                    <p class="text-lg font-bold text-white font-mono mt-1">{{ driver.license_expiry || 'Indefinite' }}</p>
                </div>

                <div class="rounded-2xl bg-slate-900 border border-slate-800 p-4">
                    <span class="text-xs text-slate-400 font-medium">Assigned Trips Total</span>
                    <p class="text-lg font-bold text-white mt-1">{{ driver.bookings?.length || 0 }} Completed</p>
                </div>
            </div>

            <!-- Trip History -->
            <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 shadow-xl">
                <h3 class="text-base font-bold text-white mb-4">Trip Assignment History</h3>

                <div v-if="driver.bookings?.length === 0" class="py-12 text-center text-slate-500 text-xs">
                    No trips currently assigned or completed by this driver.
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="b in driver.bookings"
                        :key="b.id"
                        class="flex items-center justify-between p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800/80 text-xs"
                    >
                        <div class="flex items-center gap-3">
                            <MapPin class="h-4 w-4 text-indigo-400 shrink-0" />
                            <div>
                                <h4 class="font-bold text-white">{{ b.destination }}</h4>
                                <p class="text-[11px] text-slate-400">
                                    Vehicle: {{ b.vehicle?.brand }} {{ b.vehicle?.model }} ({{ b.vehicle?.plate_number }}) • Requester: {{ b.employee?.name }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-4">
                            <span class="font-mono text-slate-400 hidden sm:block">{{ formatDate(b.start_time) }}</span>
                            <StatusBadge :status="b.status" size="sm" />
                            <Link
                                :href="`/bookings/${b.id}`"
                                class="px-2.5 py-1 rounded-lg bg-slate-800 text-slate-300 hover:text-white"
                            >
                                Details
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
