<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import {
    CalendarDays,
    PlusCircle,
    MapPin,
    Clock,
    Car,
    User,
    ChevronRight,
    Filter
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
    return new Date(dateStr).toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout title="Fleet Bookings">
        <Head title="Bookings - KCC Fleet" />

        <div class="space-y-6">
            <!-- Header with Action -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">Booking Requests & Trips</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Track, review, and manage pool car lifecycle</p>
                </div>
                <div>
                    <Link
                        href="/bookings/create"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 transition-all active:scale-95"
                    >
                        <PlusCircle class="h-4 w-4" />
                        New Request
                    </Link>
                </div>
            </div>

            <!-- Filter Status Pills -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button
                    v-for="s in statuses"
                    :key="s.value"
                    @click="applyFilter(s.value)"
                    class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all border"
                    :class="[
                        currentStatus === s.value
                            ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30'
                            : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white hover:border-slate-700'
                    ]"
                >
                    {{ s.label }}
                </button>
            </div>

            <!-- Mobile Card View -->
            <div class="grid grid-cols-1 gap-3 md:hidden">
                <div
                    v-for="b in bookings.data"
                    :key="b.id"
                    class="rounded-2xl bg-slate-900 border border-slate-800 p-4 space-y-3 shadow-lg"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <MapPin class="h-4 w-4 text-indigo-400 shrink-0" />
                                <h3 class="font-bold text-white text-sm">{{ b.destination }}</h3>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">Requester: {{ b.employee?.name }}</p>
                        </div>
                        <StatusBadge :status="b.status" size="sm" />
                    </div>

                    <div class="space-y-1 text-xs text-slate-300 bg-slate-950/60 p-2.5 rounded-xl border border-slate-800/80">
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Vehicle:</span>
                            <span class="font-semibold text-white">{{ b.vehicle ? `${b.vehicle.brand} ${b.vehicle.model}` : 'Unassigned' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Driver:</span>
                            <span class="font-semibold text-white">{{ b.driver?.user?.name || 'Pending assignment' }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-slate-400">Departure:</span>
                            <span class="font-mono text-slate-300">{{ formatDate(b.start_time) }}</span>
                        </div>
                    </div>

                    <div class="pt-1 flex justify-end">
                        <Link
                            :href="`/bookings/${b.id}`"
                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-indigo-600/20 text-indigo-300 border border-indigo-500/30 text-xs font-semibold hover:bg-indigo-600 hover:text-white transition-all"
                        >
                            View Details <ChevronRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>

                <div v-if="bookings.data.length === 0" class="rounded-2xl border border-slate-800 bg-slate-900/40 p-12 text-center text-slate-500 text-sm">
                    No bookings found matching filter.
                </div>
            </div>

            <!-- Desktop Table View -->
            <div class="hidden md:block rounded-3xl bg-slate-900/80 border border-slate-800 p-6 shadow-xl backdrop-blur-xl">
                <div v-if="bookings.data.length === 0" class="py-16 text-center text-slate-500 text-sm">
                    No bookings match your current filter.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 font-semibold uppercase tracking-wider">
                                <th class="pb-3.5 pl-2">Destination</th>
                                <th class="pb-3.5">Employee</th>
                                <th class="pb-3.5">Vehicle</th>
                                <th class="pb-3.5">Driver</th>
                                <th class="pb-3.5">Schedule</th>
                                <th class="pb-3.5">Status</th>
                                <th class="pb-3.5 text-right pr-2">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr
                                v-for="b in bookings.data"
                                :key="b.id"
                                class="hover:bg-slate-800/40 transition-colors group"
                            >
                                <td class="py-4 pl-2 font-bold text-white">
                                    <div class="flex items-center gap-2">
                                        <MapPin class="h-4 w-4 text-indigo-400 shrink-0" />
                                        <span>{{ b.destination }}</span>
                                    </div>
                                </td>
                                <td class="py-4 text-slate-300">
                                    <div>{{ b.employee?.name }}</div>
                                    <span class="text-[10px] text-slate-500 font-mono">{{ b.employee?.department?.code }}</span>
                                </td>
                                <td class="py-4 text-slate-300">
                                    <div v-if="b.vehicle">
                                        <span class="font-semibold">{{ b.vehicle.brand }} {{ b.vehicle.model }}</span>
                                        <div class="text-[10px] text-indigo-400 font-mono">{{ b.vehicle.plate_number }}</div>
                                    </div>
                                    <span v-else class="text-slate-500 italic">Unassigned</span>
                                </td>
                                <td class="py-4 text-slate-300">
                                    <span v-if="b.driver" class="font-medium">{{ b.driver.user?.name }}</span>
                                    <span v-else class="text-slate-500 italic">Not assigned</span>
                                </td>
                                <td class="py-4 text-slate-400 font-mono text-[11px]">
                                    <div>{{ formatDate(b.start_time) }}</div>
                                    <div class="text-slate-500">to {{ formatDate(b.end_time) }}</div>
                                </td>
                                <td class="py-4">
                                    <StatusBadge :status="b.status" size="sm" />
                                </td>
                                <td class="py-4 text-right pr-2">
                                    <Link
                                        :href="`/bookings/${b.id}`"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-indigo-600 text-slate-300 hover:text-white text-xs font-semibold transition-all"
                                    >
                                        Details <ChevronRight class="h-3.5 w-3.5" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="bookings.links && bookings.links.length > 3" class="mt-6 pt-4 border-t border-slate-800 flex items-center justify-between">
                    <p class="text-xs text-slate-400">
                        Showing {{ bookings.from || 0 }} to {{ bookings.to || 0 }} of {{ bookings.total || 0 }} records
                    </p>
                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, i) in bookings.links"
                            :key="i"
                            :href="link.url || '#'"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors"
                            :class="[
                                link.active
                                    ? 'bg-indigo-600 text-white'
                                    : link.url
                                        ? 'bg-slate-800 text-slate-300 hover:bg-slate-700'
                                        : 'text-slate-600 cursor-not-allowed'
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
