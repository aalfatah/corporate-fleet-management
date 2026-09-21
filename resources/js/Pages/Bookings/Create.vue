<script setup>
import { ref, watch } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Calendar,
    Car,
    Clock,
    MapPin,
    FileText,
    Users,
    ShieldAlert,
    CheckCircle2,
    ArrowLeft,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    managers: {
        type: Array,
        default: () => [],
    },
    vehicles: {
        type: Array,
        default: () => [],
    },
});

// Default to tomorrow 09:00 -> 17:00
const getDefaultDateTime = (offsetDays = 1, hour = 9) => {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    d.setHours(hour, 0, 0, 0);
    return d.toISOString().slice(0, 16);
};

const form = useForm({
    vehicle_id: props.vehicles[0]?.id || '',
    manager_id: props.managers[0]?.id || '',
    start_time: getDefaultDateTime(1, 9),
    end_time: getDefaultDateTime(1, 17),
    destination: '',
    purpose: '',
    passenger_count: 1,
});

const isCheckingAvailability = ref(false);
const availableVehicles = ref(props.vehicles);
const selectedVehicleAvailable = ref(true);

const checkAvailability = async () => {
    if (!form.start_time || !form.end_time || form.start_time >= form.end_time) return;

    isCheckingAvailability.value = true;
    try {
        const response = await fetch(`/api/vehicles/available?start_time=${encodeURIComponent(form.start_time)}&end_time=${encodeURIComponent(form.end_time)}`);
        const data = await response.json();
        availableVehicles.value = data.available_vehicles || [];

        // Check if currently selected vehicle is available in list
        if (form.vehicle_id) {
            const isAvail = availableVehicles.value.some(v => v.id === form.vehicle_id);
            selectedVehicleAvailable.value = isAvail;
        }
    } catch (e) {
        console.error('Error checking vehicle availability', e);
    } finally {
        isCheckingAvailability.value = false;
    }
};

watch(() => [form.start_time, form.end_time], () => {
    checkAvailability();
});

watch(() => form.vehicle_id, () => {
    if (form.vehicle_id) {
        selectedVehicleAvailable.value = availableVehicles.value.some(v => v.id === form.vehicle_id);
    }
});

const submit = () => {
    form.post('/bookings');
};
</script>

<template>
    <AppLayout title="Request Pool Car">
        <Head title="Book Pool Car - KCC Fleet" />

        <div class="max-w-3xl mx-auto space-y-6">
            <!-- Header Bar -->
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        href="/bookings"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400 hover:text-indigo-400 transition-colors mb-2"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" /> Back to Bookings
                    </Link>
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">New Vehicle Booking Request</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Submit request for pool car assignment and PIC manager approval</p>
                </div>
            </div>

            <!-- Form Card -->
            <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 md:p-8 shadow-2xl backdrop-blur-xl">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Schedule Row -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5 flex items-center gap-1.5">
                                <Clock class="h-3.5 w-3.5 text-indigo-400" /> Start Date & Time *
                            </label>
                            <input
                                v-model="form.start_time"
                                type="datetime-local"
                                required
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.start_time" class="mt-1 text-xs text-rose-400 flex items-center gap-1">
                                <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.start_time }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5 flex items-center gap-1.5">
                                <Clock class="h-3.5 w-3.5 text-indigo-400" /> Estimated Return Time *
                            </label>
                            <input
                                v-model="form.end_time"
                                type="datetime-local"
                                required
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.end_time" class="mt-1 text-xs text-rose-400 flex items-center gap-1">
                                <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.end_time }}
                            </p>
                        </div>
                    </div>

                    <!-- Vehicle Selection with Real-time Anti-Double Booking Check -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 flex items-center gap-1.5">
                                <Car class="h-3.5 w-3.5 text-indigo-400" /> Desired Vehicle *
                            </label>
                            <span v-if="isCheckingAvailability" class="text-[11px] text-indigo-400 animate-pulse font-medium">
                                Verifying availability...
                            </span>
                            <span v-else-if="selectedVehicleAvailable && form.vehicle_id" class="text-[11px] text-emerald-400 font-semibold flex items-center gap-1">
                                <CheckCircle2 class="h-3 w-3" /> Available for this schedule
                            </span>
                            <span v-else-if="!selectedVehicleAvailable && form.vehicle_id" class="text-[11px] text-rose-400 font-semibold flex items-center gap-1">
                                <AlertCircle class="h-3 w-3" /> Booked / Unavailable in this slot
                            </span>
                        </div>

                        <select
                            v-model="form.vehicle_id"
                            required
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            :class="{ 'border-rose-500': !selectedVehicleAvailable && form.vehicle_id }"
                        >
                            <option value="" disabled>Select vehicle...</option>
                            <option
                                v-for="v in vehicles"
                                :key="v.id"
                                :value="v.id"
                            >
                                {{ v.brand }} {{ v.model }} ({{ v.plate_number }}) - Capacity: {{ v.capacity }} Seats
                            </option>
                        </select>
                        <p v-if="form.errors.vehicle_id" class="mt-1 text-xs text-rose-400 flex items-center gap-1">
                            <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.vehicle_id }}
                        </p>
                    </div>

                    <!-- Approving Manager & Passenger Count -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5 flex items-center gap-1.5">
                                <Users class="h-3.5 w-3.5 text-indigo-400" /> Approving Manager / PIC *
                            </label>
                            <select
                                v-model="form.manager_id"
                                required
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            >
                                <option value="" disabled>Select Approver...</option>
                                <option
                                    v-for="m in managers"
                                    :key="m.id"
                                    :value="m.id"
                                >
                                    {{ m.name }} ({{ m.email }})
                                </option>
                            </select>
                            <p v-if="form.errors.manager_id" class="mt-1 text-xs text-rose-400 flex items-center gap-1">
                                <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.manager_id }}
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5 flex items-center gap-1.5">
                                <Users class="h-3.5 w-3.5 text-indigo-400" /> Passenger Count *
                            </label>
                            <input
                                v-model="form.passenger_count"
                                type="number"
                                min="1"
                                max="20"
                                required
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.passenger_count" class="mt-1 text-xs text-rose-400 flex items-center gap-1">
                                <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.passenger_count }}
                            </p>
                        </div>
                    </div>

                    <!-- Destination -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5 flex items-center gap-1.5">
                            <MapPin class="h-3.5 w-3.5 text-indigo-400" /> Destination / Location *
                        </label>
                        <input
                            v-model="form.destination"
                            type="text"
                            required
                            placeholder="e.g., Client Office, Kawasan Industri Jababeka, Cikarang"
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2.5 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                        />
                        <p v-if="form.errors.destination" class="mt-1 text-xs text-rose-400 flex items-center gap-1">
                            <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.destination }}
                        </p>
                    </div>

                    <!-- Purpose -->
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-300 mb-1.5 flex items-center gap-1.5">
                            <FileText class="h-3.5 w-3.5 text-indigo-400" /> Business Purpose & Official Justification *
                        </label>
                        <textarea
                            v-model="form.purpose"
                            rows="3"
                            required
                            placeholder="Describe official project/meeting purpose..."
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 p-3 text-sm text-white focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder-slate-500"
                        ></textarea>
                        <p v-if="form.errors.purpose" class="mt-1 text-xs text-rose-400 flex items-center gap-1">
                            <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.purpose }}
                        </p>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 border-t border-slate-800 flex justify-end gap-3">
                        <Link
                            href="/bookings"
                            class="px-5 py-2.5 rounded-xl border border-slate-700 text-slate-300 text-sm font-semibold hover:bg-slate-800 transition-colors"
                        >
                            Cancel
                        </Link>
                        <button
                            type="submit"
                            :disabled="form.processing || !selectedVehicleAvailable"
                            class="px-6 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 hover:brightness-110 text-white text-sm font-bold shadow-xl shadow-indigo-600/30 active:scale-95 disabled:opacity-50 transition-all flex items-center gap-2"
                        >
                            <span v-if="form.processing">Submitting...</span>
                            <span v-else>Submit Booking Request</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
