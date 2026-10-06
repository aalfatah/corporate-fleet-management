<script setup>
import { ref, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import {
    Car,
    Plus,
    Search,
    Users,
    Fuel,
    Gauge,
    ChevronRight,
    X
} from 'lucide-vue-next';

const props = defineProps({
    vehicles: {
        type: Object,
        default: () => ({ data: [] }),
    },
    filters: {
        type: Object,
        default: () => ({ status: '', search: '' }),
    },
});

const page = usePage();
const user = page.props.auth?.user;

const search = ref(props.filters.search || '');
const currentStatus = ref(props.filters.status || '');
const showCreateModal = ref(false);

const form = useForm({
    plate_number: '',
    brand: '',
    model: '',
    color: '',
    year: new Date().getFullYear(),
    capacity: 5,
    fuel_type: 'petrol',
    current_km: 0,
    current_status: 'available',
});

const applyFilters = () => {
    router.get('/vehicles', {
        status: currentStatus.value,
        search: search.value,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

// Debounce search: fires 400ms after last keystroke
let searchTimer = null;
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

const setStatusFilter = (status) => {
    currentStatus.value = status;
    applyFilters();
};

const submitCreate = () => {
    form.post('/vehicles', {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <AppLayout title="Vehicles">
        <Head title="Vehicles - KCC Fleet" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Pool Fleet Catalog</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Corporate vehicles, capacity, and operational condition</p>
                </div>

                <div v-if="user?.role?.slug === 'super_admin'">
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition-colors"
                    >
                        <Plus class="h-4 w-4" /> Add Vehicle
                    </button>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <Search class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by plate number, brand, model..."
                        class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 pl-10 pr-4 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                    />
                </div>

                <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
                    <button
                        v-for="s in [
                            { label: 'All', value: '' },
                            { label: 'Available', value: 'available' },
                            { label: 'In Use', value: 'in_use' },
                            { label: 'Maintenance', value: 'maintenance' },
                        ]"
                        :key="s.value"
                        @click="setStatusFilter(s.value)"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium whitespace-nowrap transition-colors border"
                        :class="[
                            currentStatus.value === s.value
                                ? 'bg-blue-600 text-white border-blue-600 font-semibold'
                                : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700'
                        ]"
                    >
                        {{ s.label }}
                    </button>
                </div>
            </div>

            <!-- Vehicle Card Grid -->
            <div v-if="vehicles.data.length === 0" class="rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 p-12 text-center text-slate-400 dark:text-slate-500 text-xs">
                No vehicles found matching criteria.
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="v in vehicles.data"
                    :key="v.id"
                    class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-5 transition-colors flex flex-col justify-between"
                >
                    <div class="space-y-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider text-blue-600 dark:text-blue-400 font-mono">{{ v.plate_number }}</span>
                                <h3 class="text-base font-bold text-slate-900 dark:text-white mt-0.5">{{ v.brand }} {{ v.model }}</h3>
                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ v.color || 'Standard' }} • {{ v.year || '-' }}</p>
                            </div>
                            <StatusBadge :status="v.current_status" size="sm" />
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-3 gap-2 bg-slate-50 dark:bg-slate-900/60 p-3 rounded-lg border border-slate-200 dark:border-slate-700 text-center text-xs">
                            <div>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-semibold block">Capacity</span>
                                <span class="font-bold text-slate-900 dark:text-white mt-0.5 flex items-center justify-center gap-1">
                                    <Users class="h-3 w-3 text-blue-600 dark:text-blue-400" /> {{ v.capacity }} Seats
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-semibold block">Fuel</span>
                                <span class="font-bold text-slate-900 dark:text-white mt-0.5 capitalize flex items-center justify-center gap-1">
                                    <Fuel class="h-3 w-3 text-amber-500" /> {{ v.fuel_type || 'Petrol' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-semibold block">Odometer</span>
                                <span class="font-bold text-slate-900 dark:text-white mt-0.5 font-mono">
                                    {{ v.current_km ? (v.current_km / 1000).toFixed(1) + 'k' : '0' }} KM
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700 mt-4 flex items-center justify-between">
                        <Link
                            :href="`/vehicles/${v.id}`"
                            class="text-xs font-semibold text-slate-600 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 flex items-center gap-1 transition-colors"
                        >
                            History & Details <ChevronRight class="h-3.5 w-3.5" />
                        </Link>

                        <Link
                            v-if="v.current_status === 'available'"
                            :href="`/bookings/create?vehicle_id=${v.id}`"
                            class="px-3 py-1.5 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 hover:bg-blue-600 hover:text-white text-xs font-medium border border-blue-200 dark:border-blue-800 transition-colors"
                        >
                            Book This Car
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- CREATE VEHICLE MODAL (Super Admin) -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" maxWidth="lg">
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <Car class="h-5 w-5" />
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Register Pool Vehicle</h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Plate Number *</label>
                            <input
                                v-model="form.plate_number"
                                type="text"
                                required
                                placeholder="e.g. B 1234 KCC"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white font-mono uppercase focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            />
                            <p v-if="form.errors.plate_number" class="text-red-600 dark:text-red-400 mt-1">{{ form.errors.plate_number }}</p>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Brand *</label>
                            <input
                                v-model="form.brand"
                                type="text"
                                required
                                placeholder="e.g. Toyota"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Model *</label>
                            <input
                                v-model="form.model"
                                type="text"
                                required
                                placeholder="e.g. Innova Zenix"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Color</label>
                            <input
                                v-model="form.color"
                                type="text"
                                placeholder="e.g. Silver Metallic"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Capacity *</label>
                            <input
                                v-model="form.capacity"
                                type="number"
                                required
                                min="1"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Year</label>
                            <input
                                v-model="form.year"
                                type="number"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Fuel Type</label>
                            <select
                                v-model="form.fuel_type"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            >
                                <option value="petrol">Petrol</option>
                                <option value="diesel">Diesel</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="electric">Electric</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Initial Odometer (KM)</label>
                        <input
                            v-model="form.current_km"
                            type="number"
                            min="0"
                            class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                        />
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium disabled:opacity-50 transition-colors"
                        >
                            Save Vehicle
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
