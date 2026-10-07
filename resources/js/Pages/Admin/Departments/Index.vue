<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Modal from '@/Components/Modal.vue';
import {
    Building2, Plus, Pencil, Trash2, RotateCcw,
    Search, ChevronLeft, ChevronRight, X, Users as UsersIcon,
    Hash, AlignLeft, Eye, EyeOff, ToggleLeft,
} from 'lucide-vue-next';

const props = defineProps({
    departments: { type: Object, default: () => ({ data: [] }) },
    filters:     { type: Object, default: () => ({}) },
});

// ── Filters ───────────────────────────────────────────────────────────────────
const search       = ref(props.filters.search || '');
const showInactive = ref(props.filters.show_inactive === 'true' || props.filters.show_inactive === true);

let searchTimer = null;
const applyFilters = () => {
    router.get('/admin/departments', {
        search:        search.value,
        show_inactive: showInactive.value ? '1' : '',
    }, { preserveState: true, replace: true, preserveScroll: true });
};

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

watch(showInactive, applyFilters);

// ── Create Modal ──────────────────────────────────────────────────────────────
const showCreate = ref(false);

const createForm = useForm({
    name:        '',
    code:        '',
    description: '',
});

const openCreate = () => {
    createForm.reset();
    showCreate.value = true;
};

const submitCreate = () => {
    createForm.post('/admin/departments', {
        onSuccess: () => { showCreate.value = false; createForm.reset(); },
    });
};

// Auto-generate uppercase code from name when code is empty
const syncCodeFromName = () => {
    if (!createForm.code) {
        // Generate abbreviation: first letters of each word, max 5 chars
        createForm.code = createForm.name
            .split(/\s+/)
            .filter(Boolean)
            .map(w => w[0])
            .join('')
            .toUpperCase()
            .slice(0, 5);
    }
};

// ── Edit Modal ────────────────────────────────────────────────────────────────
const showEdit      = ref(false);
const editingDept   = ref(null);

const editForm = useForm({
    name:        '',
    code:        '',
    description: '',
});

const openEdit = (dept) => {
    editingDept.value = dept;
    editForm.name        = dept.name;
    editForm.code        = dept.code ?? '';
    editForm.description = dept.description ?? '';
    showEdit.value = true;
};

const submitEdit = () => {
    editForm.put(`/admin/departments/${editingDept.value.id}`, {
        onSuccess: () => { showEdit.value = false; },
    });
};

// ── Delete / Restore ──────────────────────────────────────────────────────────
const deleteDept = (dept) => {
    if (!confirm(`Deactivate department "${dept.name}"?\n\nThis will only be blocked if there are active users still assigned.`)) return;
    router.delete(`/admin/departments/${dept.id}`, { preserveScroll: true });
};

const restoreDept = (dept) => {
    router.post(`/admin/departments/${dept.id}/restore`, {}, { preserveScroll: true });
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const formatDate = (d) => d
    ? new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
    : '-';

const page = usePage();
const flash = computed(() => page.props.flash ?? {});
</script>

<template>
    <AppLayout title="Department Management">
        <Head title="Departments - KCC Fleet" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Departments</h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Manage organizational departments assigned to system users
                    </p>
                </div>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs transition-colors self-start sm:self-auto"
                >
                    <Plus class="h-4 w-4" />
                    Add Department
                </button>
            </div>

            <!-- Flash messages -->
            <div v-if="flash.success"
                class="flex items-center gap-3 p-3.5 rounded-xl bg-green-50 dark:bg-green-950/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 text-xs font-medium"
            >
                <Building2 class="h-4 w-4 shrink-0" />
                {{ flash.success }}
            </div>
            <div v-if="flash.error"
                class="flex items-center gap-3 p-3.5 rounded-xl bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 text-xs font-medium"
            >
                <X class="h-4 w-4 shrink-0" />
                {{ flash.error }}
            </div>

            <!-- Filters -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <!-- Search -->
                <div class="relative w-full sm:w-72">
                    <Search class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search name or code..."
                        class="w-full pl-9 pr-4 py-2 text-xs rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                    />
                </div>

                <!-- Show inactive toggle -->
                <button
                    @click="showInactive = !showInactive"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border text-xs font-medium transition-colors"
                    :class="showInactive
                        ? 'bg-amber-50 dark:bg-amber-950/30 border-amber-300 dark:border-amber-700 text-amber-700 dark:text-amber-400'
                        : 'bg-white dark:bg-slate-800 border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700'"
                >
                    <ToggleLeft class="h-4 w-4" />
                    {{ showInactive ? 'Showing all (incl. inactive)' : 'Show inactive' }}
                </button>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors">

                <!-- Empty state -->
                <div v-if="departments.data.length === 0" class="p-12 text-center">
                    <Building2 class="h-10 w-10 text-slate-400 dark:text-slate-500 mx-auto mb-3" />
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">No departments found</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">Try adjusting your search or add the first department.</p>
                </div>

                <!-- Table -->
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="bg-slate-50 dark:bg-slate-900/50 border-b border-slate-200 dark:border-slate-700 text-slate-500 dark:text-slate-400 font-semibold uppercase tracking-wider">
                                <th class="px-5 py-3">Department</th>
                                <th class="px-4 py-3 hidden sm:table-cell">Code</th>
                                <th class="px-4 py-3 hidden md:table-cell">Description</th>
                                <th class="px-4 py-3 text-center">Users</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-700/60">
                            <tr
                                v-for="dept in departments.data"
                                :key="dept.id"
                                class="hover:bg-slate-50 dark:hover:bg-slate-700/30 transition-colors"
                                :class="dept.deleted_at ? 'opacity-60 bg-slate-50/50 dark:bg-slate-900/30' : ''"
                            >
                                <!-- Name -->
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-3">
                                        <div class="h-8 w-8 rounded-lg flex items-center justify-center shrink-0"
                                            :class="dept.deleted_at
                                                ? 'bg-slate-100 dark:bg-slate-700'
                                                : 'bg-blue-50 dark:bg-blue-900/30'">
                                            <Building2 class="h-4 w-4"
                                                :class="dept.deleted_at ? 'text-slate-400' : 'text-blue-600 dark:text-blue-400'" />
                                        </div>
                                        <span class="font-semibold text-slate-900 dark:text-white">{{ dept.name }}</span>
                                    </div>
                                </td>

                                <!-- Code -->
                                <td class="px-4 py-3.5 hidden sm:table-cell">
                                    <span v-if="dept.code"
                                        class="inline-flex items-center px-2 py-0.5 rounded-md font-mono font-bold text-[11px] bg-slate-100 dark:bg-slate-700 text-slate-700 dark:text-slate-200"
                                    >{{ dept.code }}</span>
                                    <span v-else class="text-slate-400">—</span>
                                </td>

                                <!-- Description -->
                                <td class="px-4 py-3.5 hidden md:table-cell text-slate-500 dark:text-slate-400 max-w-xs truncate">
                                    {{ dept.description || '—' }}
                                </td>

                                <!-- Users count -->
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold"
                                        :class="dept.users_count > 0
                                            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                            : 'bg-slate-100 text-slate-500 dark:bg-slate-700 dark:text-slate-400'"
                                    >
                                        <UsersIcon class="h-3 w-3" />
                                        {{ dept.users_count }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3.5">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold"
                                        :class="dept.deleted_at
                                            ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                                            : 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'"
                                    >
                                        {{ dept.deleted_at ? 'Inactive' : 'Active' }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <template v-if="!dept.deleted_at">
                                            <button
                                                @click="openEdit(dept)"
                                                class="p-1.5 rounded-md border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                                title="Edit Department"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </button>
                                            <button
                                                @click="deleteDept(dept)"
                                                class="p-1.5 rounded-md border border-slate-300 dark:border-slate-600 text-slate-500 hover:text-red-600 hover:border-red-300 dark:hover:bg-red-950/30 transition-colors"
                                                title="Deactivate"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </template>
                                        <button
                                            v-else
                                            @click="restoreDept(dept)"
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
                <div v-if="departments.last_page > 1" class="flex items-center justify-between px-5 py-3 border-t border-slate-200 dark:border-slate-700 text-xs text-slate-500 dark:text-slate-400">
                    <span>Showing {{ departments.from }}–{{ departments.to }} of {{ departments.total }}</span>
                    <div class="flex gap-1.5">
                        <a
                            v-if="departments.prev_page_url"
                            :href="departments.prev_page_url"
                            class="p-1.5 rounded-md border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <ChevronLeft class="h-4 w-4" />
                        </a>
                        <span class="px-2.5 py-1 rounded-md bg-blue-600 text-white font-semibold">{{ departments.current_page }}</span>
                        <a
                            v-if="departments.next_page_url"
                            :href="departments.next_page_url"
                            class="p-1.5 rounded-md border border-slate-300 dark:border-slate-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                        >
                            <ChevronRight class="h-4 w-4" />
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── CREATE MODAL ──────────────────────────────────────────────────────── -->
        <Modal :show="showCreate" @close="showCreate = false" maxWidth="md">
            <div class="space-y-5">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-blue-600 dark:text-blue-400">
                        <Building2 class="h-5 w-5" />
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Add New Department</h3>
                    </div>
                    <button @click="showCreate = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <!-- Name -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Department Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="createForm.name"
                            @blur="syncCodeFromName"
                            type="text"
                            required
                            placeholder="e.g. Information Technology"
                            class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            :class="createForm.errors.name ? 'border-red-500' : ''"
                        />
                        <p v-if="createForm.errors.name" class="text-red-600 dark:text-red-400 mt-1">{{ createForm.errors.name }}</p>
                    </div>

                    <!-- Code -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Department Code
                            <span class="font-normal text-slate-400">(short identifier, e.g. IT, HR, FIN)</span>
                        </label>
                        <div class="relative">
                            <Hash class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="createForm.code"
                                type="text"
                                placeholder="e.g. IT"
                                maxlength="20"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 pl-9 p-2.5 text-slate-900 dark:text-white uppercase placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors font-mono"
                                :class="createForm.errors.code ? 'border-red-500' : ''"
                                @input="createForm.code = createForm.code.toUpperCase()"
                            />
                        </div>
                        <p v-if="createForm.errors.code" class="text-red-600 dark:text-red-400 mt-1">{{ createForm.errors.code }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Description
                            <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <div class="relative">
                            <AlignLeft class="h-4 w-4 absolute left-3 top-3 text-slate-400" />
                            <textarea
                                v-model="createForm.description"
                                rows="2"
                                placeholder="Brief description of this department..."
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 pl-9 p-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors resize-none"
                            />
                        </div>
                    </div>

                    <!-- Actions -->
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
                            {{ createForm.processing ? 'Saving...' : 'Create Department' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- ── EDIT MODAL ────────────────────────────────────────────────────────── -->
        <Modal :show="showEdit" @close="showEdit = false" maxWidth="md">
            <div class="space-y-5">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                        <Pencil class="h-5 w-5" />
                        <h3 class="text-base font-bold text-slate-900 dark:text-white">Edit Department</h3>
                    </div>
                    <button @click="showEdit = false" class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 transition-colors">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 text-xs">
                    <!-- Name -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Department Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            placeholder="e.g. Information Technology"
                            class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            :class="editForm.errors.name ? 'border-red-500' : ''"
                        />
                        <p v-if="editForm.errors.name" class="text-red-600 dark:text-red-400 mt-1">{{ editForm.errors.name }}</p>
                    </div>

                    <!-- Code -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Department Code
                            <span class="font-normal text-slate-400">(letters & numbers only)</span>
                        </label>
                        <div class="relative">
                            <Hash class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input
                                v-model="editForm.code"
                                type="text"
                                placeholder="e.g. IT"
                                maxlength="20"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 pl-9 p-2.5 text-slate-900 dark:text-white uppercase placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors font-mono"
                                :class="editForm.errors.code ? 'border-red-500' : ''"
                                @input="editForm.code = editForm.code.toUpperCase()"
                            />
                        </div>
                        <p v-if="editForm.errors.code" class="text-red-600 dark:text-red-400 mt-1">{{ editForm.errors.code }}</p>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">
                            Description
                            <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <div class="relative">
                            <AlignLeft class="h-4 w-4 absolute left-3 top-3 text-slate-400" />
                            <textarea
                                v-model="editForm.description"
                                rows="2"
                                placeholder="Brief description..."
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 pl-9 p-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors resize-none"
                            />
                        </div>
                    </div>

                    <!-- Info: users assigned -->
                    <div v-if="editingDept?.users_count > 0"
                        class="flex items-center gap-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-950/30 border border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-400"
                    >
                        <UsersIcon class="h-4 w-4 shrink-0" />
                        <span class="text-xs">{{ editingDept.users_count }} user(s) are currently assigned to this department.</span>
                    </div>

                    <!-- Actions -->
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
                            class="px-4 py-2 rounded-lg bg-amber-500 hover:bg-amber-600 text-white font-medium disabled:opacity-50 transition-colors"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AppLayout>
</template>
