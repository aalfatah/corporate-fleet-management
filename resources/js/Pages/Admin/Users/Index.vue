<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import {
    UserPlus, Pencil, Trash2, RotateCcw,
    Search, ChevronLeft, ChevronRight, X, Eye, EyeOff,
    ShieldCheck, Users
} from 'lucide-vue-next';

const props = defineProps({
    users:       { type: Object, default: () => ({ data: [] }) },
    roles:       { type: Array,  default: () => [] },
    departments: { type: Array,  default: () => [] },
    managers:    { type: Array,  default: () => [] },
    filters:     { type: Object, default: () => ({}) },
});

// ── Filters ───────────────────────────────────────────────────────────────────
const search      = ref(props.filters.search || '');
const activeRole  = ref(props.filters.role  || '');

let searchTimer = null;
const applyFilters = () => {
    router.get('/admin/users', {
        search: search.value,
        role:   activeRole.value,
    }, { preserveState: true, replace: true });
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

// ── Role filter tabs ──────────────────────────────────────────────────────────
const roleTabs = computed(() => [
    { label: 'All', slug: '' },
    ...props.roles.map(r => ({ label: r.name, slug: r.slug })),
]);

const setRoleFilter = (slug) => {
    activeRole.value = slug;
    applyFilters();
};

// ── Role badge styling (Section 2.A) ──────────────────────────────────────────
const roleBadge = (slug) => {
    switch (slug) {
        case 'super_admin': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'pic':         return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        case 'driver':      return 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
        default:            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
    }
};

// ── Create Modal ──────────────────────────────────────────────────────────────
const showCreate    = ref(false);
const showPassword  = ref(false);

const createForm = useForm({
    name:                 '',
    email:                '',
    password:             '',
    role_id:              '',
    department_id:        '',
    manager_id:           '',
    phone:                '',
    must_change_password: true,
});

const openCreate = () => {
    createForm.reset();
    createForm.must_change_password = true;
    showCreate.value = true;
};

const submitCreate = () => {
    createForm.post('/admin/users', {
        onSuccess: () => { showCreate.value = false; createForm.reset(); },
    });
};

// ── Edit Modal ────────────────────────────────────────────────────────────────
const showEdit     = ref(false);
const editingUser  = ref(null);
const showEditPass = ref(false);

const editForm = useForm({
    name:                 '',
    email:                '',
    password:             '',
    role_id:              '',
    department_id:        '',
    manager_id:           '',
    phone:                '',
    must_change_password: false,
});

const openEdit = (user) => {
    editingUser.value = user;
    editForm.name                 = user.name;
    editForm.email                = user.email;
    editForm.password             = '';
    editForm.role_id              = user.role?.id ?? '';
    editForm.department_id        = user.department?.id ?? '';
    editForm.manager_id           = user.manager_id ?? '';
    editForm.phone                = user.phone ?? '';
    editForm.must_change_password = user.must_change_password ?? false;
    showEdit.value    = true;
    showEditPass.value = false;
};

const submitEdit = () => {
    editForm.put(`/admin/users/${editingUser.value.id}`, {
        onSuccess: () => { showEdit.value = false; },
    });
};

// ── Delete / Restore ──────────────────────────────────────────────────────────
const deleteUser = (user) => {
    if (!confirm(`Deactivate "${user.name}"? They will not be able to log in.`)) return;
    router.delete(`/admin/users/${user.id}`, { preserveScroll: true });
};

const restoreUser = (user) => {
    router.post(`/admin/users/${user.id}/restore`, {}, { preserveScroll: true });
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const avatarInitial = (name) => name ? name.charAt(0).toUpperCase() : 'U';
const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '-';
const page = usePage();
const authUser = computed(() => page.props.auth?.user);
</script>

<template>
    <AppLayout title="User Management">
        <Head title="Users - KCC Fleet" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">User Accounts</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage system access across Employees, PIC, Drivers, and Administrators</p>
                </div>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition-colors self-start sm:self-auto"
                >
                    <UserPlus class="h-4 w-4" />
                    Create User
                </button>
            </div>

            <!-- Role Filter Tabs & Search Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <!-- Role Filter Tabs -->
                <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800/60 p-1 rounded-lg overflow-x-auto">
                    <button
                        v-for="tab in roleTabs"
                        :key="tab.slug"
                        @click="setRoleFilter(tab.slug)"
                        class="px-3 py-1.5 rounded-md text-xs font-medium whitespace-nowrap transition-colors"
                        :class="[
                            activeRole === tab.slug
                                ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white font-semibold shadow-sm'
                                : 'text-slate-600 dark:text-slate-400 hover:text-slate-900 dark:hover:text-white'
                        ]"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <!-- Search Input -->
                <div class="relative w-full sm:w-64">
                    <Search class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search name or email..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                    />
                </div>
            </div>

            <!-- Data Table Card -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">
                <div v-if="users.data.length === 0" class="p-12 text-center">
                    <Users class="h-10 w-10 text-slate-400 dark:text-slate-500 mx-auto mb-3" />
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">No users found</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Try adjusting your role filter or search query</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                                <th class="px-5 py-3">User</th>
                                <th class="px-4 py-3">Role</th>
                                <th class="px-4 py-3 hidden md:table-cell">Department</th>
                                <th class="px-4 py-3 hidden lg:table-cell">Phone</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/60">
                            <tr
                                v-for="u in users.data"
                                :key="u.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
                                :class="u.deleted_at ? 'opacity-60 bg-slate-50/50 dark:bg-slate-900/30' : ''"
                            >
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <img
                                            v-if="u.avatar"
                                            :src="`/storage/${u.avatar}`"
                                            class="h-8 w-8 rounded-full object-cover border border-slate-200 dark:border-slate-700 shrink-0"
                                            alt=""
                                        />
                                        <div
                                            v-else
                                            class="h-8 w-8 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 flex items-center justify-center font-bold text-xs shrink-0"
                                        >
                                            {{ avatarInitial(u.name) }}
                                        </div>
                                        <div>
                                            <p class="font-semibold text-slate-900 dark:text-white flex items-center gap-1.5">
                                                {{ u.name }}
                                                <span v-if="u.id === authUser?.id" class="text-[10px] bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 px-1.5 py-0.2 rounded font-semibold">You</span>
                                                <span v-if="u.must_change_password" title="Must change password" class="text-amber-500">
                                                    <ShieldCheck class="h-3.5 w-3.5" />
                                                </span>
                                            </p>
                                            <p class="text-slate-500 dark:text-slate-400 font-mono text-[11px]">{{ u.email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold" :class="roleBadge(u.role?.slug)">
                                        {{ u.role?.name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 hidden md:table-cell text-slate-600 dark:text-slate-300">{{ u.department?.name ?? '-' }}</td>
                                <td class="px-4 py-3.5 hidden lg:table-cell text-slate-600 dark:text-slate-300 font-mono">{{ u.phone ?? '-' }}</td>
                                <td class="px-4 py-3.5">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                        :class="u.deleted_at
                                            ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                            : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
                                    >
                                        {{ u.deleted_at ? 'Inactive' : 'Active' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <template v-if="!u.deleted_at">
                                            <button
                                                @click="openEdit(u)"
                                                class="p-1.5 rounded-md border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                                title="Edit"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                v-if="u.id !== authUser?.id"
                                                @click="deleteUser(u)"
                                                class="p-1.5 rounded-md border border-slate-300 dark:border-slate-600 text-slate-500 hover:text-red-600 hover:border-red-300 dark:hover:bg-red-950/30 transition-colors"
                                                title="Deactivate"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </template>
                                        <button
                                            v-else
                                            @click="restoreUser(u)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md border border-green-300 dark:border-green-800 text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/30 font-medium transition-colors"
                                            title="Restore"
                                        >
                                            <RotateCcw class="h-3.5 w-3.5" />
                                            Restore
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
                    <span>Showing {{ users.from }}–{{ users.to }} of {{ users.total }}</span>
                    <div class="flex gap-1.5">
                        <Link
                            v-if="users.prev_page_url"
                            :href="users.prev_page_url"
                            preserve-scroll preserve-state
                            class="p-1.5 rounded-md border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </Link>
                        <span class="px-2.5 py-1 rounded-md bg-blue-600 text-white font-semibold">{{ users.current_page }}</span>
                        <Link
                            v-if="users.next_page_url"
                            :href="users.next_page_url"
                            preserve-scroll preserve-state
                            class="p-1.5 rounded-md border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </Link>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── CREATE USER MODAL ─────────────────────────────────────────────── -->
        <Modal :show="showCreate" @close="showCreate = false" maxWidth="lg">
            <div class="space-y-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <UserPlus class="h-5 w-5" />
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Create New User</h3>
                    </div>
                    <button @click="showCreate = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <!-- Row 1: Name & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
                            <input v-model="createForm.name" type="text" required placeholder="e.g. John Doe"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                :class="createForm.errors.name ? 'border-red-500' : ''"
                            />
                            <p v-if="createForm.errors.name" class="text-red-600 dark:text-red-400 mt-1">{{ createForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Email Address *</label>
                            <input v-model="createForm.email" type="email" required placeholder="user@company.com"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                :class="createForm.errors.email ? 'border-red-500' : ''"
                            />
                            <p v-if="createForm.errors.email" class="text-red-600 dark:text-red-400 mt-1">{{ createForm.errors.email }}</p>
                        </div>
                    </div>

                    <!-- Row 2: Role & Department -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Role *</label>
                            <select v-model="createForm.role_id" required
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            >
                                <option value="" disabled>Select Role</option>
                                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                            <p v-if="createForm.errors.role_id" class="text-red-600 dark:text-red-400 mt-1">{{ createForm.errors.role_id }}</p>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Department</label>
                            <select v-model="createForm.department_id"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            >
                                <option value="">None / Corporate</option>
                                <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 3: Manager & Phone -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Direct Manager (PIC)</label>
                            <select v-model="createForm.manager_id"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            >
                                <option value="">None</option>
                                <option v-for="m in managers" :key="m.id" :value="m.id">{{ m.name }} ({{ m.email }})</option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                            <input v-model="createForm.phone" type="text" placeholder="+62 8xx xxxx xxxx"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Row 4: Temporary Password -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Initial Password *</label>
                        <div class="relative">
                            <input v-model="createForm.password" :type="showPassword ? 'text' : 'password'" required placeholder="Min. 8 characters, uppercase & number"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 pr-10 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                :class="createForm.errors.password ? 'border-red-500' : ''"
                            />
                            <button type="button" @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <Eye v-if="!showPassword" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="createForm.errors.password" class="text-red-600 dark:text-red-400 mt-1">{{ createForm.errors.password }}</p>
                    </div>

                    <!-- Force Password Change Checkbox -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="create_must_change"
                            v-model="createForm.must_change_password"
                            type="checkbox"
                            class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-600 h-4 w-4"
                        />
                        <label for="create_must_change" class="text-xs text-slate-700 dark:text-slate-300 select-none">
                            Require password change upon first login (Recommended)
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showCreate = false"
                            class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="createForm.processing"
                            class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium disabled:opacity-50 transition-colors"
                        >
                            {{ createForm.processing ? 'Saving...' : 'Create Account' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ── EDIT USER MODAL ───────────────────────────────────────────────── -->
        <Modal :show="showEdit" @close="showEdit = false" maxWidth="lg">
            <div class="space-y-5">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <Pencil class="h-5 w-5" />
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit User: {{ editingUser?.name }}</h3>
                    </div>
                    <button @click="showEdit = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 text-xs">
                    <!-- Row 1: Name & Email -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
                            <input v-model="editForm.name" type="text" required
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                :class="editForm.errors.name ? 'border-red-500' : ''"
                            />
                            <p v-if="editForm.errors.name" class="text-red-600 dark:text-red-400 mt-1">{{ editForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Email Address *</label>
                            <input v-model="editForm.email" type="email" required
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                :class="editForm.errors.email ? 'border-red-500' : ''"
                            />
                            <p v-if="editForm.errors.email" class="text-red-600 dark:text-red-400 mt-1">{{ editForm.errors.email }}</p>
                        </div>
                    </div>

                    <!-- Row 2: Role & Department -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Role *</label>
                            <select v-model="editForm.role_id" required :disabled="editingUser?.id === authUser?.id"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors disabled:opacity-50"
                            >
                                <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                            </select>
                            <p v-if="editingUser?.id === authUser?.id" class="text-[11px] text-slate-400 mt-0.5">You cannot change your own role.</p>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Department</label>
                            <select v-model="editForm.department_id"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            >
                                <option value="">None / Corporate</option>
                                <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Row 3: Manager & Phone -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Direct Manager (PIC)</label>
                            <select v-model="editForm.manager_id"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            >
                                <option value="">None</option>
                                <option v-for="m in managers" :key="m.id" :value="m.id" :disabled="m.id === editingUser?.id">
                                    {{ m.name }} ({{ m.email }})
                                </option>
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                            <input v-model="editForm.phone" type="text" placeholder="+62 8xx xxxx xxxx"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            />
                        </div>
                    </div>

                    <!-- Reset Password (Optional) -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Reset Password <span class="text-slate-400 font-normal">(leave blank to keep current)</span>
                        </label>
                        <div class="relative">
                            <input v-model="editForm.password" :type="showEditPass ? 'text' : 'password'" placeholder="Enter new password if resetting"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 pr-10 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                :class="editForm.errors.password ? 'border-red-500' : ''"
                            />
                            <button type="button" @click="showEditPass = !showEditPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                <Eye v-if="!showEditPass" class="h-4 w-4" />
                                <EyeOff v-else class="h-4 w-4" />
                            </button>
                        </div>
                        <p v-if="editForm.errors.password" class="text-red-600 dark:text-red-400 mt-1">{{ editForm.errors.password }}</p>
                    </div>

                    <!-- Force Password Change Checkbox -->
                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="edit_must_change"
                            v-model="editForm.must_change_password"
                            type="checkbox"
                            class="rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-600 h-4 w-4"
                        />
                        <label for="edit_must_change" class="text-xs text-slate-700 dark:text-slate-300 select-none">
                            Require user to change password on next login
                        </label>
                    </div>

                    <!-- Modal Actions -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200 dark:border-slate-700">
                        <button
                            type="button"
                            @click="showEdit = false"
                            class="px-4 py-2 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700 font-medium transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium disabled:opacity-50 transition-colors"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
