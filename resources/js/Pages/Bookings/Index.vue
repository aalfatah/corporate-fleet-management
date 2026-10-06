<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import {
    CalendarDays,
    Plus,
    MapPin,
    Clock,
    Car,
    User,
    ChevronRight,
    ChevronLeft
} from 'lucide-vue-next';

const props = defineProps({
    bookings: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    filters: {
        type: Object,
        default: () => ({ status: '' }),
    },
});

const currentStatus = ref(props.filters.status || '');

const statuses = [
    { label: 'All', value: '' },
    { label: 'Pending', value: 'pending_approval' },
    { label: 'Approved', value: 'approved' },
    { label: 'Assigned', value: 'assigned' },
    { label: 'In Progress', value: 'in_progress' },
    { label: 'Completed', value: 'completed' },
    { label: 'Rejected', value: 'rejected' },
    { label: 'Cancelled', value: 'cancelled' },
];

const applyFilter = (status) => {
    currentStatus.value = status;
    router.get('/bookings', { status }, { preserveState: true, replace: true });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('en-GB', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout title="Bookings">
        <Head title="Bookings - KCC Fleet" />

        <div class="space-y-6">
            <!-- Header with Action -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Booking Requests & Trips</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Track, review, and manage pool car requests</p>
                </div>
                <div>
                    <Link
                        href="/bookings/create"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition-colors"
                    >
                        <Plus class="h-4 w-4" />
                        New Request
                    </Link>
                </div>
            </div>

            <!-- Filter Status Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
                <button
                    v-for="s in statuses"
                    :key="s.value"
                    @click="applyFilter(s.value)"
                    class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors border"
                    :class="[
                        currentStatus === s.value
                            ? 'bg-blue-600 text-white border-blue-600 font-semibold'
                            : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'
                    ]"
                >
                    {{ s.label }}
                </button>
            </div>

            <!-- Mobile Card View (Section 4.B) -->
            <div class="grid grid-cols-1 gap-3 md:hidden">
                <div
                    v-for="b in bookings.data"
                    :key="b.id"
                    class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 space-y-3 transition-colors"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <MapPin class="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                <h3 class="font-bold text-slate-900 dark:text-white text-sm">{{ b.destination }}</h3>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Requester: {{ b.employee?.name }}</p>
                        </div>
                        <StatusBadge :status="b.status" size="sm" />
                    </div>

                    <div class="space-y-1 text-xs text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/60 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Vehicle:</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ b.vehicle ? `${b.vehicle.brand} ${b.vehicle.model}` : 'Unassigned' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Driver:</span>
                            <span class="font-medium text-slate-900 dark:text-white">{{ b.driver?.user?.name || 'Pending assignment' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-500 dark:text-slate-400">Departure:</span>
                            <span class="font-mono text-slate-700 dark:text-slate-300">{{ formatDate(b.start_time) }}</span>
                        </div>
                    </div>

                    <div class="pt-1 flex justify-end">
                        <Link
                            :href="`/bookings/${b.id}`"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-md border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-xs font-medium hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                        >
                            View Details <ChevronRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <div v-if="bookings.data.length === 0" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-10 text-center text-slate-400 dark:text-slate-500 text-xs">
                    No bookings found matching filter.
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">
                <div v-if="bookings.data.length === 0" class="py-16 text-center text-slate-400 dark:text-slate-500 text-xs">
                    No bookings match your current filter.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                                <th class="py-3 px-4">Destination</th>
                                <th class="py-3 px-4">Requester</th>
                                <th class="py-3 px-4">Vehicle</th>
                                <th class="py-3 px-4">Driver</th>
                                <th class="py-3 px-4">Schedule</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/60">
                            <tr
                                v-for="b in bookings.data"
                                :key="b.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
                            >
                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-2">
                                        <MapPin class="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                        <span>{{ b.destination }}</span>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                    <div>{{ b.employee?.name }}</div>
                                    <span class="text-[10px] text-slate-400 font-mono">{{ b.employee?.department?.code }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                    <div v-if="b.vehicle">
                                        <div class="font-medium">{{ b.vehicle.brand }} {{ b.vehicle.model }}</div>
                                        <div class="text-[10px] text-blue-600 dark:text-blue-400 font-mono">{{ b.vehicle.plate_number }}</div>
                                    </div>
                                    <span v-else class="text-slate-400 italic">Unassigned</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300">
                                    <span v-if="b.driver">{{ b.driver.user?.name }}</span>
                                    <span v-else class="text-slate-400 italic">Pending assignment</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400 font-mono text-[11px]">
                                    <div>{{ formatDate(b.start_time) }}</div>
                                    <div class="text-[10px] text-slate-400">until {{ formatDate(b.end_time) }}</div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <StatusBadge :status="b.status" size="sm" />
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <Link
                                        :href="`/bookings/${b.id}`"
                                        class="px-2.5 py-1 rounded-md border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-medium transition-colors"
                                    >
                                        Details
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="bookings.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
                    <span>Showing page {{ bookings.current_page }} of {{ bookings.last_page }}</span>
                    <div class="flex gap-1.5">
                        <Link
                            v-if="bookings.prev_page_url"
                            :href="bookings.prev_page_url"
                            preserve-scroll preserve-state
                            class="p-1.5 rounded-md border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Link>
                        <span class="px-2.5 py-1 rounded-md bg-blue-600 text-white font-semibold">{{ bookings.current_page }}</span>
                        <Link
                            v-if="bookings.next_page_url"
                            :href="bookings.next_page_url"
                            preserve-scroll preserve-state
                            class="p-1.5 rounded-md border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
