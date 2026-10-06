<script setup>
import { ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import {
    Users,
    ArrowLeft,
    Car,
    Clock,
    MapPin,
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
    return new Date(dateStr).toLocaleString('en-GB', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};
</script>

<template>
    <AppLayout title="Driver Details">
        <Head :title="`${driver.user?.name} - Driver Profile`" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        href="/drivers"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors mb-2"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" /> Back to Drivers
                    </Link>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">{{ driver.user?.name }}</h2>
                        <span
                            class="px-2.5 py-0.5 rounded-full text-xs font-semibold"
                            :class="[
                                driver.is_active
                                    ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                    : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                            ]"
                        >
                            {{ driver.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">{{ driver.user?.email }} • {{ driver.user?.department?.name || 'Operations' }}</p>
                </div>

                <div v-if="user?.role?.slug === 'super_admin'" class="flex items-center gap-2">
                    <button
                        @click="toggleActiveStatus"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                    >
                        {{ driver.is_active ? 'Deactivate Driver' : 'Activate Driver' }}
                    </button>
                    <button
                        @click="deleteDriver"
                        class="p-2 rounded-lg border border-red-300 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                        title="Delete Driver Profile"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Driver Info Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 transition-colors">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium block">License Number</span>
                    <p class="text-base font-bold font-mono text-blue-600 dark:text-blue-400 mt-1">{{ driver.license_number }}</p>
                </div>

                <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 transition-colors">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium block">License Expiry</span>
                    <p class="text-base font-bold font-mono text-slate-900 dark:text-white mt-1">{{ driver.license_expiry || 'Permanent / Not set' }}</p>
                </div>

                <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 transition-colors">
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-medium block">Phone Contact</span>
                    <p class="text-base font-bold text-slate-900 dark:text-white mt-1">{{ driver.user?.phone || 'No phone set' }}</p>
                </div>
            </div>

            <!-- Assigned Trips History -->
            <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 transition-colors">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Trip Assignment History</h3>

                <div v-if="driver.bookings?.length === 0" class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                    No trips currently logged for this driver.
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="b in driver.bookings"
                        :key="b.id"
                        class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-xs"
                    >
                        <div class="flex items-center gap-3">
                            <MapPin class="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white">{{ b.destination }}</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Requester: {{ b.employee?.name }} • Car: {{ b.vehicle?.brand }} {{ b.vehicle?.model }} ({{ b.vehicle?.plate_number }})
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="font-mono text-slate-500 dark:text-slate-400 hidden sm:block">{{ formatDate(b.start_time) }}</span>
                            <StatusBadge :status="b.status" size="sm" />
                            <Link
                                :href="`/bookings/${b.id}`"
                                class="px-2.5 py-1 rounded-md border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 font-medium"
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
