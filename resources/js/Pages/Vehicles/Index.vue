<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Modal from '@/Components/Modal.vue';
import {
    Car,
    PlusCircle,
    Search,
    Users,
    Fuel,
    Gauge,
    Calendar,
    ChevronRight,
    ShieldAlert
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
    }, { preserveState: true, replace: true });
};

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
    <AppLayout title="Fleet Catalog">
        <Head title="Vehicles - KCC Fleet" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">Pool Fleet Catalog</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Corporate vehicles, capacity, and operational condition</p>
                </div>

                <div v-if="user?.role?.slug === 'super_admin'">
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 transition-all active:scale-95"
                    >
                        <PlusCircle class="h-4 w-4" /> Add Vehicle
                    </button>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <Search class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search by plate number, brand, model..."
                        class="w-full rounded-xl bg-slate-900 border border-slate-800 pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:border-indigo-500"
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
                        class="px-3 py-1.5 rounded-xl text-xs font-semibold whitespace-nowrap transition-all border"
                        :class="[
                            currentStatus === s.value
                                ? 'bg-indigo-600 text-white border-indigo-500 shadow-md shadow-indigo-600/30'
                                : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white'
                        ]"
                    >
                        {{ s.label }}
                    </button>
                </div>
            </div>

            <!-- Vehicle Card Grid -->
            <div v-if="vehicles.data.length === 0" class="rounded-3xl border border-slate-800 bg-slate-900/40 p-16 text-center text-slate-500 text-sm">
                No vehicles found matching criteria.
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="v in vehicles.data"
                    :key="v.id"
                    class="rounded-3xl bg-slate-900/80 border border-slate-800 p-5 shadow-xl hover:border-slate-700 transition-all group flex flex-col justify-between"
                >
                    <div class="space-y-4">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="text-xs font-extrabold uppercase tracking-wider text-indigo-400 font-mono">{{ v.plate_number }}</span>
                                <h3 class="text-lg font-bold text-white mt-0.5">{{ v.brand }} {{ v.model }}</h3>
                                <p class="text-xs text-slate-400">{{ v.color || 'Standard' }} • {{ v.year || '-' }}</p>
                            </div>
                            <StatusBadge :status="v.current_status" size="sm" />
                        </div>

                        <!-- Details Grid -->
                        <div class="grid grid-cols-3 gap-2 bg-slate-950/60 p-3 rounded-2xl border border-slate-800/80 text-center">
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase font-semibold block">Capacity</span>
                                <span class="text-xs font-bold text-slate-200 mt-0.5 flex items-center justify-center gap-1">
                                    <Users class="h-3 w-3 text-indigo-400" /> {{ v.capacity }} Seats
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase font-semibold block">Fuel</span>
                                <span class="text-xs font-bold text-slate-200 mt-0.5 capitalize flex items-center justify-center gap-1">
                                    <Fuel class="h-3 w-3 text-amber-400" /> {{ v.fuel_type || 'Petrol' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-[10px] text-slate-500 uppercase font-semibold block">Odometer</span>
                                <span class="text-xs font-bold text-slate-200 mt-0.5 font-mono">
                                    {{ v.current_km ? (v.current_km / 1000).toFixed(1) + 'k' : '0' }} KM
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-5 border-t border-slate-800/80 mt-4 flex items-center justify-between">
                        <Link
                            :href="`/vehicles/${v.id}`"
                            class="text-xs font-semibold text-slate-400 hover:text-indigo-400 flex items-center gap-1 transition-colors"
                        >
                            History & Details <ChevronRight class="h-3.5 w-3.5" />
                        </Link>

                        <Link
                            v-if="v.current_status === 'available'"
                            :href="`/bookings/create?vehicle_id=${v.id}`"
                            class="px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600 text-indigo-300 hover:text-white text-xs font-semibold border border-indigo-500/30 transition-all"
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
                <div class="flex items-center gap-2 text-indigo-400">
                    <Car class="h-6 w-6" />
                    <h3 class="text-base font-bold text-white">Register Pool Vehicle</h3>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Plate Number *</label>
                            <input
                                v-model="form.plate_number"
                                type="text"
                                required
                                placeholder="e.g. B 1234 KCC"
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-white font-mono uppercase"
                            />
                            <p v-if="form.errors.plate_number" class="text-rose-400 mt-1">{{ form.errors.plate_number }}</p>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Brand *</label>
                            <input
                                v-model="form.brand"
                                type="text"
                                required
                                placeholder="e.g. Toyota"
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-white"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Model *</label>
                            <input
                                v-model="form.model"
                                type="text"
                                required
                                placeholder="e.g. Innova Zenix"
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-white"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Color</label>
                            <input
                                v-model="form.color"
                                type="text"
                                placeholder="e.g. Silver Metallic"
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-white"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Capacity *</label>
                            <input
                                v-model="form.capacity"
                                type="number"
                                min="1"
                                required
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-white"
                            />
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Fuel Type</label>
                            <select
                                v-model="form.fuel_type"
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-white"
                            >
                                <option value="petrol">Petrol</option>
                                <option value="diesel">Diesel</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="electric">Electric</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 uppercase mb-1">Current KM</label>
                            <input
                                v-model="form.current_km"
                                type="number"
                                min="0"
                                class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-white font-mono"
                            />
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-800">
                        <button
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 rounded-xl text-slate-400 hover:bg-slate-800"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-600/30"
                        >
                            Register Vehicle
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
