<script setup>
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import {
    Users,
    PlusCircle,
    Search,
    ShieldCheck,
    CheckCircle2,
    XCircle,
    Calendar,
    ChevronRight,
    Car
} from 'lucide-vue-next';

const props = defineProps({
    drivers: {
        type: Object,
        default: () => ({ data: [] }),
    },
    eligibleUsers: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ is_active: '', search: '' }),
    },
});

const search = ref(props.filters.search || '');
const showCreateModal = ref(false);

const form = useForm({
    user_id: props.eligibleUsers[0]?.id || '',
    license_number: '',
    license_expiry: '',
    is_active: true,
});

const applyFilters = () => {
    router.get('/drivers', {
        search: search.value,
    }, { preserveState: true, replace: true });
};

const submitCreate = () => {
    form.post('/drivers', {
        onSuccess: () => {
            showCreateModal.value = false;
            form.reset();
        },
    });
};

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
};
</script>

<template>
    <AppLayout title="Driver Directory">
        <Head title="Drivers - KCC Fleet" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-extrabold text-white tracking-tight">Driver Registry</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Manage designated drivers, licenses, and availability</p>
                </div>

                <div>
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-xs shadow-lg shadow-indigo-600/30 transition-all active:scale-95"
                    >
                        <PlusCircle class="h-4 w-4" /> Register Driver Profile
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="relative max-w-md">
                <Search class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500" />
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Search driver name, license..."
                    class="w-full rounded-xl bg-slate-900 border border-slate-800 pl-10 pr-4 py-2.5 text-xs text-white placeholder-slate-500 focus:border-indigo-500"
                />
            </div>

            <!-- Drivers Table / Card List -->
            <div class="rounded-3xl bg-slate-900/80 border border-slate-800 p-6 shadow-xl backdrop-blur-xl">
                <div v-if="drivers.data.length === 0" class="py-16 text-center text-slate-500 text-sm">
                    No registered drivers found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 font-semibold uppercase tracking-wider">
                                <th class="pb-3.5 pl-2">Driver Name</th>
                                <th class="pb-3.5">Email</th>
                                <th class="pb-3.5">License Number</th>
                                <th class="pb-3.5">License Expiry</th>
                                <th class="pb-3.5">Active Status</th>
                                <th class="pb-3.5 text-right pr-2">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr
                                v-for="d in drivers.data"
                                :key="d.id"
                                class="hover:bg-slate-800/40 transition-colors"
                            >
                                <td class="py-4 pl-2 font-bold text-white flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-full bg-slate-800 text-indigo-400 flex items-center justify-center font-bold text-xs">
                                        {{ d.user?.name?.charAt(0) || 'D' }}
                                    </div>
                                    <span>{{ d.user?.name }}</span>
                                </td>
                                <td class="py-4 text-slate-300">{{ d.user?.email }}</td>
                                <td class="py-4 text-indigo-400 font-mono font-semibold">{{ d.license_number }}</td>
                                <td class="py-4 text-slate-400 font-mono">{{ formatDate(d.license_expiry) }}</td>
                                <td class="py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border"
                                        :class="[
                                            d.is_active
                                                ? 'bg-emerald-500/15 text-emerald-400 border-emerald-500/30'
                                                : 'bg-rose-500/15 text-rose-400 border-rose-500/30'
                                        ]"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="d.is_active ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                                        {{ d.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-4 text-right pr-2">
                                    <Link
                                        :href="`/drivers/${d.id}`"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-indigo-600 text-slate-300 hover:text-white text-xs font-semibold transition-all"
                                    >
                                        History <ChevronRight class="h-3.5 w-3.5" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- CREATE DRIVER PROFILE MODAL -->
        <Modal :show="showCreateModal" @close="showCreateModal = false" maxWidth="md">
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-indigo-400">
                    <Users class="h-6 w-6" />
                    <h3 class="text-base font-bold text-white">Register Driver Profile</h3>
                </div>
                <p class="text-xs text-slate-400">
                    Assign a driving license and activate trip assignment readiness for a user account.
                </p>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-300 uppercase mb-1">User Account *</label>
                        <select
                            v-model="form.user_id"
                            required
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-white"
                        >
                            <option value="" disabled>Select user...</option>
                            <option
                                v-for="u in eligibleUsers"
                                :key="u.id"
                                :value="u.id"
                            >
                                {{ u.name }} ({{ u.email }})
                            </option>
                        </select>
                        <p v-if="eligibleUsers.length === 0" class="text-[11px] text-amber-400 mt-1">
                            No unassigned users with the 'Driver' role found.
                        </p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 uppercase mb-1">Driver License (SIM A / B) *</label>
                        <input
                            v-model="form.license_number"
                            type="text"
                            required
                            placeholder="e.g. 1234-5678-9012"
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-white font-mono"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 uppercase mb-1">License Expiry Date</label>
                        <input
                            v-model="form.license_expiry"
                            type="date"
                            class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-white"
                        />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="active-toggle"
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-indigo-600"
                        />
                        <label for="active-toggle" class="text-slate-300 font-medium cursor-pointer">
                            Mark active and ready for trip dispatch
                        </label>
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
                            :disabled="form.processing || !form.user_id"
                            class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-bold shadow-lg shadow-indigo-600/30 disabled:opacity-50"
                        >
                            Register Driver
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
