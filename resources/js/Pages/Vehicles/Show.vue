<script setup>
import { ref } from 'vue';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import {
    Car,
    ArrowLeft,
    Users,
    Fuel,
    Gauge,
    Calendar,
    Wrench,
    MapPin,
    Trash2
} from 'lucide-vue-next';

const props = defineProps({
    vehicle: {
        type: Object,
        required: true,
    },
});

const page = usePage();
const user = page.props.auth?.user;

const showEditStatusModal = ref(false);
const newStatus = ref(props.vehicle.current_status);

const updateStatus = () => {
    router.put(`/vehicles/${props.vehicle.id}`, {
        ...props.vehicle,
        current_status: newStatus.value,
    }, {
        onSuccess: () => {
            showEditStatusModal.value = false;
        },
    });
};

const deleteVehicle = () => {
    if (confirm('Delete this vehicle from fleet? Soft delete will preserve historical bookings.')) {
        router.delete(`/vehicles/${props.vehicle.id}`);
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
    <AppLayout title="Vehicle Details">
        <Head :title="`${vehicle.brand} ${vehicle.model} - KCC Fleet`" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <Link
                        href="/vehicles"
                        class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors mb-2"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" /> Back to Vehicles
                    </Link>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">{{ vehicle.brand }} {{ vehicle.model }}</h2>
                        <StatusBadge :status="vehicle.current_status" size="md" />
                    </div>
                    <p class="text-xs font-mono text-blue-600 dark:text-blue-400 mt-0.5 font-bold">{{ vehicle.plate_number }}</p>
                </div>

                <div v-if="user?.role?.slug === 'super_admin'" class="flex items-center gap-2">
                    <button
                        @click="showEditStatusModal = true"
                        class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-medium flex items-center gap-1.5 transition-colors"
                    >
                        <Wrench class="h-3.5 w-3.5" /> Update Status
                    </button>
                    <button
                        @click="deleteVehicle"
                        class="p-2 rounded-lg border border-red-300 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors"
                        title="Remove vehicle"
                    >
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <!-- Specs Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 transition-colors">
                    <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5 font-medium">
                        <Users class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" /> Seating Capacity
                    </span>
                    <p class="text-lg font-bold text-slate-900 dark:text-white mt-2">{{ vehicle.capacity }} Seats</p>
                </div>

                <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 transition-colors">
                    <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5 font-medium">
                        <Fuel class="h-3.5 w-3.5 text-amber-500" /> Fuel Type
                    </span>
                    <p class="text-lg font-bold text-slate-900 dark:text-white mt-2 capitalize">{{ vehicle.fuel_type || 'Petrol' }}</p>
                </div>

                <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 transition-colors">
                    <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5 font-medium">
                        <Gauge class="h-3.5 w-3.5 text-green-600 dark:text-green-400" /> Current Odometer
                    </span>
                    <p class="text-lg font-bold text-slate-900 dark:text-white font-mono mt-2">{{ vehicle.current_km?.toLocaleString() }} KM</p>
                </div>

                <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-4 transition-colors">
                    <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5 font-medium">
                        <Calendar class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" /> Model Year
                    </span>
                    <p class="text-lg font-bold text-slate-900 dark:text-white mt-2">{{ vehicle.year || '2024' }}</p>
                </div>
            </div>

            <!-- Booking History for this vehicle -->
            <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 transition-colors">
                <h3 class="text-sm font-bold text-slate-900 dark:text-white mb-4">Trip History with this Vehicle</h3>

                <div v-if="vehicle.bookings?.length === 0" class="py-8 text-center text-slate-400 dark:text-slate-500 text-xs">
                    No booking records linked to this vehicle.
                </div>

                <div v-else class="space-y-2">
                    <div
                        v-for="b in vehicle.bookings"
                        :key="b.id"
                        class="flex items-center justify-between p-3 rounded-lg bg-slate-50 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-xs"
                    >
                        <div class="flex items-center gap-3">
                            <MapPin class="h-4 w-4 text-blue-600 dark:text-blue-400 shrink-0" />
                            <div>
                                <h4 class="font-bold text-slate-900 dark:text-white">{{ b.destination }}</h4>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">Requester: {{ b.employee?.name }} • Driver: {{ b.driver?.user?.name || 'Unassigned' }}</p>
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

        <!-- UPDATE STATUS MODAL -->
        <Modal :show="showEditStatusModal" @close="showEditStatusModal = false" maxWidth="sm">
            <div class="space-y-4">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Update Vehicle Status</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400">Change operational status for {{ vehicle.plate_number }}.</p>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">Status</label>
                    <select
                        v-model="newStatus"
                        class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                    >
                        <option value="available">Available</option>
                        <option value="in_use">In Use</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showEditStatusModal = false"
                        class="px-3 py-1.5 rounded-lg border border-slate-300 dark:border-slate-600 text-xs text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="updateStatus"
                        class="px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium transition-colors"
                    >
                        Save
                    </button>
                </div>
            </div>
        </Modal>
    </AppLayout>
</template>
