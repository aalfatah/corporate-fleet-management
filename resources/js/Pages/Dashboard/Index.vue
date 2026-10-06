<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatCard from '@/Components/StatCard.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import {
    Car,
    Users,
    Clock,
    CheckCircle2,
    XCircle,
    Calendar,
    Navigation,
    AlertTriangle,
    ArrowRight,
    MapPin
} from 'lucide-vue-next';

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({}),
    },
    activeBookings: {
        type: Array,
        default: () => [],
    },
    pendingApprovals: {
        type: Array,
        default: () => [],
    },
    driverCurrentTrip: {
        type: Object,
        default: null,
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const roleSlug = computed(() => user.value?.role?.slug);

// Rejection Modal
const showRejectModal = ref(false);
const selectedBooking = ref(null);
const rejectionReason = ref('');
const isSubmitting = ref(false);

const openRejectModal = (booking) => {
    selectedBooking.value = booking;
    rejectionReason.value = '';
    showRejectModal.value = true;
};

const confirmReject = () => {
    if (!rejectionReason.value || rejectionReason.value.length < 5) return;
    isSubmitting.value = true;
    router.post(`/bookings/${selectedBooking.value.id}/reject`, {
        rejection_reason: rejectionReason.value,
    }, {
        onFinish: () => {
            isSubmitting.value = false;
            showRejectModal.value = false;
        },
    });
};

const approveBooking = (bookingId) => {
    if (confirm('Are you sure you want to approve this booking request?')) {
        router.post(`/bookings/${bookingId}/approve`);
    }
};

// Driver Trip Finish Modal
const showCompleteTripModal = ref(false);
const tripLogForm = ref({
    start_km: '',
    end_km: '',
    notes: '',
    fuel_receipt_url: '',
});

const openCompleteTripModal = (booking) => {
    selectedBooking.value = booking;
    tripLogForm.value.start_km = booking.vehicle?.current_km || 0;
    tripLogForm.value.end_km = (booking.vehicle?.current_km || 0) + 10;
    showCompleteTripModal.value = true;
};

const submitCompleteTrip = () => {
    if (parseInt(tripLogForm.value.end_km) <= parseInt(tripLogForm.value.start_km)) {
        alert('End KM must be greater than Start KM.');
        return;
    }
    isSubmitting.value = true;
    router.post(`/trips/${selectedBooking.value.id}/complete`, tripLogForm.value, {
        onFinish: () => {
            isSubmitting.value = false;
            showCompleteTripModal.value = false;
        },
    });
};

const startDriverTrip = (bookingId) => {
    if (confirm('Start this trip now? Status will change to In Progress.')) {
        router.post(`/trips/${bookingId}/start`);
    }
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    const d = new Date(dateStr);
    return d.toLocaleString('en-GB', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <AppLayout title="Dashboard">
        <Head title="Dashboard - KCC Fleet" />

        <div class="space-y-6">
            <!-- Welcome Header Banner (Solid Flat Card) -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-6 transition-colors">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">System Dashboard</span>
                        <h2 class="text-xl md:text-2xl font-bold text-slate-900 dark:text-white tracking-tight mt-1">
                            Welcome back, {{ user?.name }}
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                            Role: <span class="text-slate-700 dark:text-slate-200 font-semibold">{{ user?.role?.name }}</span>
                            <span v-if="user?.department"> • Department: <span class="text-slate-700 dark:text-slate-200">{{ user.department.name }}</span></span>
                        </p>
                    </div>

                    <div v-if="roleSlug === 'employee' || roleSlug === 'super_admin'">
                        <Link
                            href="/bookings/create"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition-colors"
                        >
                            <Calendar class="h-4 w-4" />
                            Book Car
                        </Link>
                    </div>
                </div>
            </div>

            <!-- DRIVER ROLE HERO VIEW (Mobile-First Trip Control) -->
            <div v-if="roleSlug === 'driver'" class="space-y-6">
                <!-- In-Progress Trip Active Card -->
                <div v-if="driverCurrentTrip" class="rounded-xl bg-white dark:bg-slate-800 border-2 border-blue-500 dark:border-blue-500/50 p-6 transition-colors">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-200 dark:border-slate-700 pb-4">
                        <div class="flex items-center gap-2">
                            <span class="h-2.5 w-2.5 rounded-full bg-blue-600"></span>
                            <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400">Current Ongoing Trip</span>
                        </div>
                        <StatusBadge :status="driverCurrentTrip.status" size="sm" />
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-500 dark:text-slate-400">Destination</p>
                            <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2 mt-0.5">
                                <MapPin class="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                {{ driverCurrentTrip.destination }}
                            </h3>
                            <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Passenger: {{ driverCurrentTrip.employee?.name }} ({{ driverCurrentTrip.passenger_count }} pax)</p>
                        </div>

                        <div class="bg-slate-50 dark:bg-slate-900/60 rounded-lg p-3 border border-slate-200 dark:border-slate-700 text-xs">
                            <p class="text-slate-500 dark:text-slate-400 font-medium">Assigned Vehicle</p>
                            <p class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">{{ driverCurrentTrip.vehicle?.brand }} {{ driverCurrentTrip.vehicle?.model }}</p>
                            <p class="font-mono text-blue-600 dark:text-blue-400 mt-0.5">{{ driverCurrentTrip.vehicle?.plate_number }} • {{ driverCurrentTrip.vehicle?.current_km?.toLocaleString() }} KM</p>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button
                            @click="openCompleteTripModal(driverCurrentTrip)"
                            class="w-full py-3 px-4 rounded-lg bg-green-600 hover:bg-green-700 text-white font-semibold text-xs flex items-center justify-center gap-2 transition-colors"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            Finish Trip & Submit Log (KM / Fuel)
                        </button>
                    </div>
                </div>

                <!-- Assigned Trips Waiting to Start -->
                <div class="space-y-4">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <Clock class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        Upcoming Assigned Trips
                    </h3>

                    <div v-if="activeBookings.length === 0 && !driverCurrentTrip" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-8 text-center">
                        <CheckCircle2 class="h-10 w-10 text-slate-400 dark:text-slate-500 mx-auto mb-2" />
                        <p class="text-sm font-semibold text-slate-900 dark:text-white">No active trips assigned</p>
                        <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">New dispatch requests from the administrator will appear here automatically</p>
                    </div>

                    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div
                            v-for="b in activeBookings"
                            :key="b.id"
                            class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 space-y-3 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <StatusBadge :status="b.status" size="sm" />
                                <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">{{ formatDate(b.start_time) }}</span>
                            </div>

                            <div>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-1.5">
                                    <MapPin class="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                                    {{ b.destination }}
                                </h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Requester: {{ b.employee?.name }} • {{ b.passenger_count }} Passenger(s)</p>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-900/60 rounded-lg p-2.5 border border-slate-200 dark:border-slate-700 text-xs">
                                <span class="text-slate-500 dark:text-slate-400">Vehicle:</span>
                                <span class="font-bold text-slate-900 dark:text-white ml-1">{{ b.vehicle?.brand }} {{ b.vehicle?.model }}</span>
                                <span class="font-mono text-blue-600 dark:text-blue-400 ml-1.5">({{ b.vehicle?.plate_number }})</span>
                            </div>

                            <div v-if="b.status === 'assigned'" class="pt-1">
                                <button
                                    @click="startDriverTrip(b.id)"
                                    class="w-full py-2 px-4 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs flex items-center justify-center gap-1.5 transition-colors"
                                >
                                    <Navigation class="h-4 w-4" />
                                    Start Trip
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- METRIC CARDS FOR SUPER ADMIN, PIC, EMPLOYEE -->
            <div v-if="roleSlug === 'super_admin'" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard title="Available Cars" :value="stats.available_vehicles || 0" subtitle="Ready in pool" color="green">
                    <template #icon><Car class="h-4 w-4" /></template>
                </StatCard>
                <StatCard title="Cars In Use" :value="stats.in_use_vehicles || 0" subtitle="On the road" color="blue">
                    <template #icon><Navigation class="h-4 w-4" /></template>
                </StatCard>
                <StatCard title="Pending Approvals" :value="stats.pending_approval || 0" subtitle="Awaiting PIC" color="amber">
                    <template #icon><Clock class="h-4 w-4" /></template>
                </StatCard>
                <StatCard title="Maintenance" :value="stats.maintenance_vehicles || 0" subtitle="In garage" color="red">
                    <template #icon><AlertTriangle class="h-4 w-4" /></template>
                </StatCard>
            </div>

            <div v-else-if="roleSlug === 'pic'" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard title="Awaiting My Action" :value="stats.pending_my_approval || 0" subtitle="Requires decision" color="amber">
                    <template #icon><Clock class="h-4 w-4" /></template>
                </StatCard>
                <StatCard title="Approved by Me" :value="stats.approved_by_me || 0" subtitle="Granted requests" color="blue">
                    <template #icon><CheckCircle2 class="h-4 w-4" /></template>
                </StatCard>
                <StatCard title="In Progress Trips" :value="stats.in_progress || 0" subtitle="Ongoing" color="blue">
                    <template #icon><Navigation class="h-4 w-4" /></template>
                </StatCard>
                <StatCard title="Completed" :value="stats.completed || 0" subtitle="Past trips" color="green">
                    <template #icon><CheckCircle2 class="h-4 w-4" /></template>
                </StatCard>
            </div>

            <div v-else-if="roleSlug === 'employee'" class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <StatCard title="Available Fleet" :value="stats.available_cars || 0" subtitle="Ready to book" color="green">
                    <template #icon><Car class="h-4 w-4" /></template>
                </StatCard>
                <StatCard title="My Pending" :value="stats.my_pending || 0" subtitle="Awaiting manager" color="amber">
                    <template #icon><Clock class="h-4 w-4" /></template>
                </StatCard>
                <StatCard title="My Active Trips" :value="stats.my_active || 0" subtitle="Approved / In trip" color="blue">
                    <template #icon><Navigation class="h-4 w-4" /></template>
                </StatCard>
                <StatCard title="Completed Trips" :value="stats.my_completed || 0" subtitle="History" color="green">
                    <template #icon><CheckCircle2 class="h-4 w-4" /></template>
                </StatCard>
            </div>

            <!-- PIC APPROVAL QUEUE SECTION (Section 5.B) -->
            <div v-if="roleSlug === 'pic' && pendingApprovals.length > 0" class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <Clock class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                        Pending Approvals Required
                    </h3>
                    <span class="text-xs px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 font-semibold">
                        {{ pendingApprovals.length }} action(s)
                    </span>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        v-for="b in pendingApprovals"
                        :key="b.id"
                        class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 space-y-4 transition-colors"
                    >
                        <div class="flex items-start justify-between">
                            <div>
                                <p class="text-xs font-semibold text-slate-500 dark:text-slate-400">Employee Request</p>
                                <h4 class="text-sm font-bold text-slate-900 dark:text-white mt-0.5">{{ b.employee?.name }}</h4>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ b.employee?.department?.name || 'General' }}</p>
                            </div>
                            <StatusBadge :status="b.status" size="sm" />
                        </div>

                        <div class="space-y-1.5 text-xs text-slate-700 dark:text-slate-300 bg-slate-50 dark:bg-slate-900/60 p-3 rounded-lg border border-slate-200 dark:border-slate-700">
                            <p><strong class="text-slate-500 dark:text-slate-400">Destination:</strong> {{ b.destination }}</p>
                            <p><strong class="text-slate-500 dark:text-slate-400">Schedule:</strong> {{ formatDate(b.start_time) }} - {{ formatDate(b.end_time) }}</p>
                            <p><strong class="text-slate-500 dark:text-slate-400">Purpose:</strong> {{ b.purpose }}</p>
                            <p><strong class="text-slate-500 dark:text-slate-400">Requested Vehicle:</strong> {{ b.vehicle?.brand }} {{ b.vehicle?.model }} ({{ b.vehicle?.plate_number }})</p>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button
                                @click="approveBooking(b.id)"
                                class="flex-1 py-2 px-4 rounded-lg bg-green-600 hover:bg-green-700 text-white font-medium text-xs flex items-center justify-center gap-1.5 transition-colors"
                            >
                                <CheckCircle2 class="h-4 w-4" />
                                Approve
                            </button>
                            <button
                                @click="openRejectModal(b)"
                                class="flex-1 py-2 px-4 rounded-lg border border-red-300 dark:border-red-800 text-red-700 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 font-medium text-xs flex items-center justify-center gap-1.5 transition-colors"
                            >
                                <XCircle class="h-4 w-4" />
                                Reject
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RECENT / ACTIVE BOOKINGS TABLE -->
            <div v-if="roleSlug !== 'driver'" class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 transition-colors">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white tracking-tight">Recent Fleet Bookings</h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">Status overview of corporate trips</p>
                    </div>
                    <Link
                        href="/bookings"
                        class="text-xs font-semibold text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1 transition-colors"
                    >
                        View All <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <div v-if="activeBookings.length === 0" class="py-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                    No recent bookings found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                                <th class="py-3 px-3">Destination</th>
                                <th class="py-3 px-3">Requester</th>
                                <th class="py-3 px-3">Vehicle</th>
                                <th class="py-3 px-3">Driver</th>
                                <th class="py-3 px-3">Departure</th>
                                <th class="py-3 px-3">Status</th>
                                <th class="py-3 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/60">
                            <tr v-for="b in activeBookings" :key="b.id" class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors">
                                <td class="py-3 px-3 font-semibold text-slate-900 dark:text-white">
                                    <div class="flex items-center gap-1.5">
                                        <MapPin class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400 shrink-0" />
                                        <span>{{ b.destination }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-3 text-slate-700 dark:text-slate-300">{{ b.employee?.name || '-' }}</td>
                                <td class="py-3 px-3 text-slate-700 dark:text-slate-300">
                                    <span v-if="b.vehicle">{{ b.vehicle.brand }} {{ b.vehicle.model }} ({{ b.vehicle.plate_number }})</span>
                                    <span v-else class="text-slate-400 italic">Unassigned</span>
                                </td>
                                <td class="py-3 px-3 text-slate-700 dark:text-slate-300">
                                    <span v-if="b.driver">{{ b.driver.user?.name }}</span>
                                    <span v-else class="text-slate-400 italic">Not assigned</span>
                                </td>
                                <td class="py-3 px-3 text-slate-500 dark:text-slate-400 font-mono">{{ formatDate(b.start_time) }}</td>
                                <td class="py-3 px-3">
                                    <StatusBadge :status="b.status" size="sm" />
                                </td>
                                <td class="py-3 px-3 text-right">
                                    <Link
                                        :href="`/bookings/${b.id}`"
                                        class="px-2.5 py-1 rounded-md border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 text-[11px] font-medium transition-colors"
                                    >
                                        Details
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- REJECT MODAL -->
        <Modal :show="showRejectModal" @close="showRejectModal = false" maxWidth="md">
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                    <XCircle class="h-5 w-5 shrink-0" />
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Reject Booking Request</h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Please provide an official rejection reason for the employee.
                </p>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                        Reason for Rejection *
                    </label>
                    <textarea
                        v-model="rejectionReason"
                        rows="3"
                        placeholder="e.g., Requested schedule conflicts with corporate priority event..."
                        class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none placeholder-slate-400 transition-colors"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="showRejectModal = false"
                        class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmitting || rejectionReason.length < 5"
                        @click="confirmReject"
                        class="px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 text-white text-xs font-medium disabled:opacity-50 transition-colors"
                    >
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </Modal>

        <!-- DRIVER COMPLETE TRIP MODAL -->
        <Modal :show="showCompleteTripModal" @close="showCompleteTripModal = false" maxWidth="md">
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                    <CheckCircle2 class="h-5 w-5 shrink-0" />
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Complete Trip & Input Log</h3>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Enter the odometer readings to finalize the trip and return the vehicle to the available pool.
                </p>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Start KM *</label>
                        <input
                            v-model="tripLogForm.start_km"
                            type="number"
                            required
                            class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 px-3 py-2 text-xs text-slate-900 dark:text-white font-mono outline-none focus:ring-2 focus:ring-blue-600"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">End KM *</label>
                        <input
                            v-model="tripLogForm.end_km"
                            type="number"
                            required
                            class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 px-3 py-2 text-xs text-slate-900 dark:text-white font-mono outline-none focus:ring-2 focus:ring-blue-600"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Fuel Receipt URL (Optional)</label>
                    <input
                        v-model="tripLogForm.fuel_receipt_url"
                        type="url"
                        placeholder="https://..."
                        class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 px-3 py-2 text-xs text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-blue-600"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Trip Notes / Vehicle Condition</label>
                    <textarea
                        v-model="tripLogForm.notes"
                        rows="2"
                        placeholder="e.g., Smooth trip, vehicle returned clean."
                        class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-xs text-slate-900 dark:text-white outline-none focus:ring-2 focus:ring-blue-600"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="showCompleteTripModal = false"
                        class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmitting"
                        @click="submitCompleteTrip"
                        class="px-4 py-2 rounded-lg bg-green-600 hover:bg-green-700 text-white text-xs font-medium disabled:opacity-50 transition-colors"
                    >
                        Submit & Release Car
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
