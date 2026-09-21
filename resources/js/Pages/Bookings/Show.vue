<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import {
    Calendar,
    Clock,
    MapPin,
    Car,
    User,
    CheckCircle2,
    XCircle,
    Navigation,
    Fuel,
    Gauge,
    FileText,
    ArrowLeft,
    ShieldCheck,
    AlertCircle,
    UserCheck,
    Ban
} from 'lucide-vue-next';

const props = defineProps({
    booking: {
        type: Object,
        required: true,
    },
    availableDrivers: {
        type: Array,
        default: () => [],
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const roleSlug = computed(() => user.value?.role?.slug);

const isSubmitting = ref(false);

// FSM Steps Tracker
const fsmSteps = [
    { key: 'pending_approval', label: 'Requested' },
    { key: 'approved', label: 'Approved' },
    { key: 'assigned', label: 'Driver Assigned' },
    { key: 'in_progress', label: 'In Progress' },
    { key: 'completed', label: 'Completed' },
];

const getStepIndex = (status) => {
    return fsmSteps.findIndex(s => s.key === status);
};

const currentStepIndex = computed(() => getStepIndex(props.booking.status));

// Rejection Modal
const showRejectModal = ref(false);
const rejectionReason = ref('');

const confirmReject = () => {
    if (!rejectionReason.value || rejectionReason.value.length < 5) return;
    isSubmitting.value = true;
    router.post(`/bookings/${props.booking.id}/reject`, {
        rejection_reason: rejectionReason.value,
    }, {
        onFinish: () => {
            isSubmitting.value = false;
            showRejectModal.value = false;
        },
    });
};

const approve = () => {
    if (confirm('Approve this corporate vehicle booking request?')) {
        router.post(`/bookings/${props.booking.id}/approve`);
    }
};

// Assignment Modal (Admin)
const showAssignModal = ref(false);
const assignForm = ref({
    driver_id: props.booking.driver_id || '',
    vehicle_id: props.booking.vehicle_id || '',
});

const submitAssign = () => {
    if (!assignForm.value.driver_id) {
        alert('Please choose a driver to assign.');
        return;
    }
    isSubmitting.value = true;
    router.post(`/bookings/${props.booking.id}/assign`, assignForm.value, {
        onFinish: () => {
            isSubmitting.value = false;
            showAssignModal.value = false;
        },
    });
};

// Cancel Booking
const cancelBooking = () => {
    if (confirm('Are you sure you want to cancel this booking?')) {
        router.post(`/bookings/${props.booking.id}/cancel`);
    }
};

// Driver Trip Finish Modal
const showCompleteTripModal = ref(false);
const tripLogForm = ref({
    start_km: props.booking.vehicle?.current_km || 0,
    end_km: (props.booking.vehicle?.current_km || 0) + 10,
    notes: '',
    fuel_receipt_url: '',
});

const submitCompleteTrip = () => {
    if (parseInt(tripLogForm.value.end_km) <= parseInt(tripLogForm.value.start_km)) {
        alert('End KM must be strictly greater than Start KM.');
        return;
    }
    isSubmitting.value = true;
    router.post(`/trips/${props.booking.id}/complete`, tripLogForm.value, {
        onFinish: () => {
            isSubmitting.value = false;
            showCompleteTripModal.value = false;
        },
    });
};

const startTrip = () => {
    if (confirm('Start this trip? Status will change to In Progress.')) {
        router.post(`/trips/${props.booking.id}/start`);
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
    <AppLayout title="Booking Details">
        <Head :title="`Booking - ${booking.destination}`" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header & Back Navigation -->
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        href="/bookings"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-indigo-400 transition-colors mb-2"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" /> Back to Bookings
                    </Link>
                    <div class="flex items-center gap-3">
                        <h2 class="text-2xl font-extrabold text-white tracking-tight">{{ booking.destination }}</h2>
                        <StatusBadge :status="booking.status" size="md" />
                    </div>
                    <p class="text-xs text-slate-400 mt-1 font-mono">ID: {{ booking.id }}</p>
                </div>

                <!-- Cancel Action for Requester / Admin -->
                <div v-if="['pending_approval', 'approved'].includes(booking.status) && (booking.employee_id === user?.id || roleSlug === 'super_admin')">
                    <button
                        @click="cancelBooking"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-rose-500/15 text-rose-300 border border-rose-500/30 text-xs font-bold hover:bg-rose-500/25 transition-all"
                    >
                        <Ban class="h-4 w-4" /> Cancel Booking
                    </button>
                </div>
            </div>

            <!-- FSM Progress Tracker (If not rejected/cancelled) -->
            <div
                v-if="!['rejected', 'cancelled'].includes(booking.status)"
                class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 shadow-xl backdrop-blur-xl"
            >
                <h3 class="text-xs font-semibold uppercase tracking-wider text-slate-400 mb-6">
                    Workflow & State Progression
                </h3>

                <div class="relative flex items-center justify-between">
                    <!-- Progress Connecting Line -->
                    <div class="absolute left-0 top-1/2 -translate-y-1/2 h-1 w-full bg-slate-800 -z-0"></div>
                    <div
                        class="absolute left-0 top-1/2 -translate-y-1/2 h-1 bg-gradient-to-r from-indigo-500 to-emerald-500 transition-all duration-500 -z-0"
                        :style="{ width: `${(Math.max(0, currentStepIndex) / (fsmSteps.length - 1)) * 100}%` }"
                    ></div>

                    <!-- Step Dots -->
                    <div
                        v-for="(step, idx) in fsmSteps"
                        :key="step.key"
                        class="relative z-10 flex flex-col items-center"
                    >
                        <div
                            class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold border-2 transition-all"
                            :class="[
                                idx <= currentStepIndex
                                    ? 'bg-indigo-600 border-indigo-400 text-white shadow-lg shadow-indigo-600/40'
                                    : 'bg-slate-900 border-slate-700 text-slate-500'
                            ]"
                        >
                            <CheckCircle2 v-if="idx < currentStepIndex" class="h-4 w-4 text-emerald-400" />
                            <span v-else>{{ idx + 1 }}</span>
                        </div>
                        <span
                            class="text-[11px] font-medium mt-2 whitespace-nowrap hidden sm:block"
                            :class="idx <= currentStepIndex ? 'text-white font-semibold' : 'text-slate-500'"
                        >
                            {{ step.label }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Rejection Alert Box -->
            <div
                v-if="booking.status === 'rejected'"
                class="rounded-2xl bg-rose-950/40 border border-rose-500/30 p-5 flex items-start gap-4 text-rose-200"
            >
                <XCircle class="h-6 w-6 text-rose-400 shrink-0 mt-0.5" />
                <div>
                    <h4 class="font-bold text-sm text-rose-300">Booking Request Rejected</h4>
                    <p class="text-xs text-rose-200/90 mt-1"><strong class="text-rose-100">Reason:</strong> {{ booking.rejection_reason || 'No specific reason entered.' }}</p>
                </div>
            </div>

            <!-- Detail Information Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Trip Information -->
                <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 space-y-4 shadow-xl">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                        <MapPin class="h-4 w-4 text-indigo-400" /> Trip Parameters
                    </h3>

                    <div class="space-y-3 text-xs">
                        <div>
                            <span class="text-slate-400 block font-medium">Destination</span>
                            <span class="text-white font-semibold text-sm">{{ booking.destination }}</span>
                        </div>

                        <div class="grid grid-cols-2 gap-3 bg-slate-950/60 p-3 rounded-xl border border-slate-800/80">
                            <div>
                                <span class="text-slate-400 block font-medium">Departure</span>
                                <span class="text-white font-mono font-medium">{{ formatDate(booking.start_time) }}</span>
                            </div>
                            <div>
                                <span class="text-slate-400 block font-medium">Estimated Return</span>
                                <span class="text-white font-mono font-medium">{{ formatDate(booking.end_time) }}</span>
                            </div>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-medium">Passengers</span>
                            <span class="text-white font-semibold">{{ booking.passenger_count }} Person(s)</span>
                        </div>

                        <div>
                            <span class="text-slate-400 block font-medium">Purpose</span>
                            <p class="text-slate-300 mt-1 bg-slate-950/40 p-2.5 rounded-lg border border-slate-800">{{ booking.purpose }}</p>
                        </div>
                    </div>
                </div>

                <!-- Fleet & Driver Assignment -->
                <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 space-y-4 shadow-xl">
                    <h3 class="text-sm font-bold text-white flex items-center gap-2 border-b border-slate-800 pb-3">
                        <Car class="h-4 w-4 text-indigo-400" /> Assigned Resources
                    </h3>

                    <div class="space-y-4 text-xs">
                        <!-- Vehicle -->
                        <div class="bg-slate-950/60 p-3.5 rounded-xl border border-slate-800/80">
                            <div class="flex items-center justify-between">
                                <span class="text-slate-400 font-medium">Pool Vehicle</span>
                                <StatusBadge v-if="booking.vehicle" :status="booking.vehicle.current_status" size="sm" />
                            </div>
                            <div v-if="booking.vehicle" class="mt-2">
                                <h4 class="text-sm font-bold text-white">{{ booking.vehicle.brand }} {{ booking.vehicle.model }}</h4>
                                <div class="flex items-center gap-3 text-slate-400 mt-1 font-mono text-[11px]">
                                    <span class="text-indigo-400 font-semibold">{{ booking.vehicle.plate_number }}</span>
                                    <span>• {{ booking.vehicle.capacity }} Seats</span>
                                    <span>• {{ booking.vehicle.current_km?.toLocaleString() }} KM</span>
                                </div>
                            </div>
                            <div v-else class="text-slate-500 italic mt-1">Vehicle not yet allocated.</div>
                        </div>

                        <!-- Driver -->
                        <div class="bg-slate-950/60 p-3.5 rounded-xl border border-slate-800/80">
                            <span class="text-slate-400 font-medium block">Designated Driver</span>
                            <div v-if="booking.driver" class="mt-2">
                                <h4 class="text-sm font-bold text-white">{{ booking.driver.user?.name }}</h4>
                                <div class="flex items-center gap-2 text-slate-400 mt-1 text-[11px]">
                                    <span>License: <span class="font-mono text-slate-200">{{ booking.driver.license_number }}</span></span>
                                    <span v-if="booking.driver.user?.phone">• Tel: {{ booking.driver.user.phone }}</span>
                                </div>
                            </div>
                            <div v-else class="text-slate-500 italic mt-1">
                                Driver will be assigned by Administrator upon approval.
                            </div>
                        </div>

                        <!-- People -->
                        <div class="grid grid-cols-2 gap-2 text-slate-400">
                            <div>
                                <span class="block text-[10px] uppercase font-bold">Requester</span>
                                <span class="text-white font-semibold">{{ booking.employee?.name }}</span>
                            </div>
                            <div>
                                <span class="block text-[10px] uppercase font-bold">PIC Approver</span>
                                <span class="text-white font-semibold">{{ booking.manager?.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- POST-TRIP LOG REPORT (If completed) -->
            <div
                v-if="booking.status === 'completed' && booking.trip_log"
                class="rounded-3xl bg-slate-900/90 border border-emerald-500/30 p-6 shadow-xl"
            >
                <div class="flex items-center gap-2 text-emerald-400 border-b border-slate-800 pb-3 mb-4">
                    <CheckCircle2 class="h-5 w-5" />
                    <h3 class="text-base font-bold text-white">Post-Trip Report Summary</h3>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-xs">
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800">
                        <span class="text-slate-400 block font-medium">Start Odometer</span>
                        <span class="text-white font-bold font-mono text-base">{{ booking.trip_log.start_km?.toLocaleString() }} KM</span>
                    </div>
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800">
                        <span class="text-slate-400 block font-medium">End Odometer</span>
                        <span class="text-white font-bold font-mono text-base">{{ booking.trip_log.end_km?.toLocaleString() }} KM</span>
                    </div>
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800">
                        <span class="text-slate-400 block font-medium">Total Distance</span>
                        <span class="text-emerald-400 font-extrabold font-mono text-base">{{ booking.trip_log.total_km?.toLocaleString() }} KM</span>
                    </div>
                    <div class="bg-slate-950/60 p-3 rounded-xl border border-slate-800">
                        <span class="text-slate-400 block font-medium">Fuel Receipt</span>
                        <a
                            v-if="booking.trip_log.fuel_receipt_url"
                            :href="booking.trip_log.fuel_receipt_url"
                            target="_blank"
                            class="text-indigo-400 underline font-semibold mt-1 inline-block"
                        >
                            View Receipt Link
                        </a>
                        <span v-else class="text-slate-500">None attached</span>
                    </div>
                </div>

                <div v-if="booking.trip_log.notes" class="mt-3 text-xs bg-slate-950/40 p-3 rounded-xl border border-slate-800">
                    <span class="text-slate-400 font-semibold block mb-1">Driver Notes:</span>
                    <p class="text-slate-300">{{ booking.trip_log.notes }}</p>
                </div>
            </div>

            <!-- ACTION CONTROLS BAR -->
            <div class="rounded-3xl bg-slate-900 border border-slate-800 p-6 flex flex-wrap items-center justify-between gap-4 shadow-xl">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Current Phase</span>
                    <p class="text-sm font-bold text-white capitalize mt-0.5">{{ booking.status.replace('_', ' ') }}</p>
                </div>

                <div class="flex items-center gap-3">
                    <!-- PIC / Approver Actions -->
                    <template v-if="booking.status === 'pending_approval' && (booking.manager_id === user?.id || roleSlug === 'super_admin' || roleSlug === 'pic')">
                        <button
                            @click="approve"
                            class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 transition-all active:scale-95"
                        >
                            <CheckCircle2 class="h-4 w-4" /> Approve Booking
                        </button>
                        <button
                            @click="showRejectModal = true"
                            class="px-5 py-2.5 rounded-xl bg-rose-600/20 hover:bg-rose-600/30 text-rose-300 border border-rose-500/30 font-bold text-xs transition-all active:scale-95"
                        >
                            <XCircle class="h-4 w-4" /> Reject Request
                        </button>
                    </template>

                    <!-- Super Admin Assign Driver & Vehicle -->
                    <template v-if="['approved', 'assigned'].includes(booking.status) && roleSlug === 'super_admin'">
                        <button
                            @click="showAssignModal = true"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center gap-1.5 transition-all active:scale-95"
                        >
                            <UserCheck class="h-4 w-4" />
                            {{ booking.status === 'assigned' ? 'Reassign Driver / Car' : 'Assign Driver & Confirm Car' }}
                        </button>
                    </template>

                    <!-- Driver / Admin Start Trip -->
                    <template v-if="booking.status === 'assigned' && (booking.driver?.user_id === user?.id || roleSlug === 'super_admin')">
                        <button
                            @click="startTrip"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold text-xs shadow-lg shadow-indigo-600/30 flex items-center gap-1.5 transition-all active:scale-95"
                        >
                            <Navigation class="h-4 w-4" /> Start Trip Now
                        </button>
                    </template>

                    <!-- Driver / Admin Complete Trip -->
                    <template v-if="booking.status === 'in_progress' && (booking.driver?.user_id === user?.id || roleSlug === 'super_admin')">
                        <button
                            @click="showCompleteTripModal = true"
                            class="px-6 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold text-xs shadow-lg shadow-emerald-600/30 flex items-center gap-1.5 transition-all active:scale-95"
                        >
                            <CheckCircle2 class="h-4 w-4" /> Complete Trip & Input KM
                        </button>
                    </template>
                </div>
            </div>
        </div>

        <!-- REJECTION MODAL -->
        <Modal :show="showRejectModal" @close="showRejectModal = false" maxWidth="md">
            <div class="space-y-4">
                <div class="flex items-center gap-3 text-rose-400">
                    <XCircle class="h-6 w-6 shrink-0" />
                    <h3 class="text-base font-bold text-white">Reject Request</h3>
                </div>
                <p class="text-xs text-slate-400">Please provide a reason for rejecting this booking request.</p>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Rejection Reason *</label>
                    <textarea
                        v-model="rejectionReason"
                        rows="3"
                        required
                        placeholder="State why this booking cannot be approved..."
                        class="w-full rounded-xl bg-slate-950 border border-slate-700 p-3 text-xs text-white placeholder-slate-500 focus:border-rose-500"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="showRejectModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmitting || rejectionReason.length < 5"
                        @click="confirmReject"
                        class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs font-bold shadow-lg shadow-rose-600/30 disabled:opacity-50"
                    >
                        Confirm Rejection
                    </button>
                </div>
            </div>
        </Modal>

        <!-- ASSIGN DRIVER & VEHICLE MODAL (Super Admin) -->
        <Modal :show="showAssignModal" @close="showAssignModal = false" maxWidth="md">
            <div class="space-y-4">
                <div class="flex items-center gap-3 text-indigo-400">
                    <UserCheck class="h-6 w-6 shrink-0" />
                    <h3 class="text-base font-bold text-white">Assign Driver & Vehicle</h3>
                </div>
                <p class="text-xs text-slate-400">
                    Select an available driver for the scheduled window:
                    <span class="font-mono text-indigo-300">{{ formatDate(booking.start_time) }} - {{ formatDate(booking.end_time) }}</span>
                </p>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Select Driver *</label>
                    <select
                        v-model="assignForm.driver_id"
                        required
                        class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2.5 text-sm text-white focus:border-indigo-500"
                    >
                        <option value="" disabled>Choose driver...</option>
                        <option
                            v-for="d in availableDrivers"
                            :key="d.id"
                            :value="d.id"
                        >
                            {{ d.user?.name }} (License: {{ d.license_number }})
                        </option>
                    </select>
                    <p v-if="availableDrivers.length === 0" class="mt-1 text-[11px] text-amber-400">
                        No idle drivers found in this exact time slot.
                    </p>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="showAssignModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmitting || !assignForm.driver_id"
                        @click="submitAssign"
                        class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 disabled:opacity-50"
                    >
                        Save Assignment
                    </button>
                </div>
            </div>
        </Modal>

        <!-- DRIVER COMPLETE TRIP MODAL -->
        <Modal :show="showCompleteTripModal" @close="showCompleteTripModal = false" maxWidth="md">
            <div class="space-y-4">
                <div class="flex items-center gap-3 text-emerald-400">
                    <CheckCircle2 class="h-6 w-6 shrink-0" />
                    <h3 class="text-base font-bold text-white">Finalize Trip & Vehicle Log</h3>
                </div>
                <p class="text-xs text-slate-400">
                    Record final odometer readings and receipt links to mark trip as Completed and free the vehicle.
                </p>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Start KM *</label>
                        <input
                            v-model="tripLogForm.start_km"
                            type="number"
                            required
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white font-mono"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">End KM *</label>
                        <input
                            v-model="tripLogForm.end_km"
                            type="number"
                            required
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white font-mono"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Fuel Receipt URL (Optional)</label>
                    <input
                        v-model="tripLogForm.fuel_receipt_url"
                        type="url"
                        placeholder="https://..."
                        class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-white"
                    />
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase mb-1">Notes / Observations</label>
                    <textarea
                        v-model="tripLogForm.notes"
                        rows="2"
                        placeholder="Remarks on vehicle condition..."
                        class="w-full rounded-xl bg-slate-950 border border-slate-700 p-2 text-xs text-white"
                    ></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button
                        type="button"
                        @click="showCompleteTripModal = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:bg-slate-800"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        :disabled="isSubmitting"
                        @click="submitCompleteTrip"
                        class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold shadow-lg shadow-emerald-600/30 disabled:opacity-50"
                    >
                        Finalize & Release
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
