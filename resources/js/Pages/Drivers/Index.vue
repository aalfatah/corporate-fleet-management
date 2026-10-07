<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import {
    Users,
    Plus,
    Search,
    ChevronRight,
    X
} from 'lucide-vue-next';

const page = usePage();
const currentUser = computed(() => page.props.auth?.user);
const isSuperAdmin = computed(() => currentUser.value?.role?.slug === 'super_admin');

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
    return new Date(dateStr).toLocaleDateString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });
};
</script>

<template>
    <AppLayout title="Drivers">
        <Head title="Drivers - KCC Fleet" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Driver Registry</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage designated drivers, licenses, and availability</p>
                </div>

                <div v-if="isSuperAdmin">
                    <button
                        @click="showCreateModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition-colors"
                    >
                        <Plus class="h-4 w-4" /> Register Driver Profile
                    </button>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="relative max-w-md">
                <Search class="h-4 w-4 absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400" />
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Search driver name, license..."
                    class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 pl-10 pr-4 py-2 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                />
            </div>

            <!-- Drivers Table / Card List -->
            <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">
                <div v-if="drivers.data.length === 0" class="py-16 text-center text-slate-400 dark:text-slate-500 text-xs">
                    No registered drivers found.
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                                <th class="py-3 px-4">Driver Name</th>
                                <th class="py-3 px-4">Email</th>
                                <th class="py-3 px-4">License Number</th>
                                <th class="py-3 px-4">License Expiry</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/60">
                            <tr
                                v-for="d in drivers.data"
                                :key="d.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
                            >
                                <td class="py-3.5 px-4 font-bold text-slate-900 dark:text-white flex items-center gap-2.5">
                                    <div class="h-8 w-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center font-bold text-xs shrink-0">
                                        {{ d.user?.name?.charAt(0) || 'D' }}
                                    </div>
                                    <span>{{ d.user?.name }}</span>
                                </td>
                                <td class="py-3.5 px-4 text-slate-700 dark:text-slate-300 font-mono text-[11px]">{{ d.user?.email }}</td>
                                <td class="py-3.5 px-4 text-blue-600 dark:text-blue-400 font-mono font-semibold">{{ d.license_number }}</td>
                                <td class="py-3.5 px-4 text-slate-600 dark:text-slate-400 font-mono">{{ formatDate(d.license_expiry) }}</td>
                                <td class="py-3.5 px-4">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                        :class="d.is_active
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                                    >
                                        {{ d.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <Link
                                        :href="`/drivers/${d.id}`"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 text-xs font-medium transition-colors"
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
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <Users class="h-5 w-5" />
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Register Driver Profile</h3>
                    </div>
                    <button @click="showCreateModal = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                        <X class="h-5 w-5" />
                    </button>
                </div>
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Assign a driving license and activate trip assignment readiness for a user account.
                </p>

                <form @submit.prevent="submitCreate" class="space-y-3 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">User Account *</label>
                        <select
                            v-model="form.user_id"
                            required
                            class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
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
                        <p v-if="eligibleUsers.length === 0" class="text-[11px] text-amber-600 dark:text-amber-400 mt-1">
                            No unassigned users with the 'Driver' role found.
                        </p>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Driver License (SIM A / B) *</label>
                        <input
                            v-model="form.license_number"
                            type="text"
                            required
                            placeholder="e.g. 1234-5678-9012"
                            class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white font-mono focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                        />
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">License Expiry Date</label>
                        <input
                            v-model="form.license_expiry"
                            type="date"
                            class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                        />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="active-toggle"
                            v-model="form.is_active"
                            type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-600"
                        />
                        <label for="active-toggle" class="text-slate-700 dark:text-slate-300 font-medium cursor-pointer">
                            Mark active and ready for trip dispatch
                        </label>
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
                            :disabled="form.processing || !form.user_id"
                            class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium disabled:opacity-50 transition-colors"
                        >
                            Register Driver
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
