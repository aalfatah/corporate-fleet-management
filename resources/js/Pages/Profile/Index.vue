<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    UserCircle, KeyRound, Camera, Eye, EyeOff,
    CheckCircle2, ShieldCheck, Phone, Mail, Building2
} from 'lucide-vue-next';

const props = defineProps({
    user: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash || {});

// ── Profile Form ──────────────────────────────────────────────────────────────
const avatarPreview = ref(null);

const profileForm = useForm({
    name:   props.user.name   ?? '',
    phone:  props.user.phone  ?? '',
    avatar: null,
});

const onAvatarChange = (e) => {
    const file = e.target.files[0];
    if (!file) return;
    profileForm.avatar = file;
    avatarPreview.value = URL.createObjectURL(file);
};

const submitProfile = () => {
    profileForm.transform((data) => ({
        ...data,
        _method: 'put',
    })).post('/profile', {
        forceFormData: true,
        onSuccess: () => {
            avatarPreview.value = null;
        },
    });
};

// ── Password Form ─────────────────────────────────────────────────────────────
const showCurrentPass = ref(false);
const showNewPass     = ref(false);
const showConfirmPass = ref(false);

const passwordForm = useForm({
    current_password:      '',
    password:              '',
    password_confirmation: '',
});

const submitPassword = () => {
    passwordForm.put('/profile/password', {
        onSuccess: () => passwordForm.reset(),
    });
};

// ── Helpers ───────────────────────────────────────────────────────────────────
const roleBadgeClass = computed(() => {
    switch (props.user.role?.slug) {
        case 'super_admin': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'pic':         return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        case 'driver':      return 'bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300';
        default:            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
    }
});

const avatarInitial = computed(() => props.user.name ? props.user.name.charAt(0).toUpperCase() : 'U');

// Password strength indicator
const passwordStrength = computed(() => {
    const p = passwordForm.password;
    if (!p) return { score: 0, label: '', color: '' };
    let score = 0;
    if (p.length >= 8) score++;
    if (/[A-Z]/.test(p)) score++;
    if (/[0-9]/.test(p)) score++;
    if (/[^A-Za-z0-9]/.test(p)) score++;
    const map = [
        { score: 0, label: '',         color: '' },
        { score: 1, label: 'Weak',     color: 'bg-red-500 text-red-600 dark:text-red-400' },
        { score: 2, label: 'Fair',     color: 'bg-amber-500 text-amber-600 dark:text-amber-400' },
        { score: 3, label: 'Good',     color: 'bg-blue-500 text-blue-600 dark:text-blue-400' },
        { score: 4, label: 'Strong',   color: 'bg-green-500 text-green-600 dark:text-green-400' },
    ];
    return map[score];
});
</script>

<template>
    <AppLayout title="My Profile">
        <Head title="Profile - KCC Fleet" />

        <div class="max-w-3xl mx-auto flex flex-col gap-6">
            <!-- Page Header -->
            <div class="order-1">
                <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Account Profile</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Manage personal information and security credentials</p>
            </div>

            <!-- Force Change Password Alert -->
            <div v-if="user.must_change_password" class="order-1 flex items-start gap-3 p-4 rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800 text-amber-800 dark:text-amber-300 text-xs">
                <ShieldCheck class="h-5 w-5 mt-0.5 shrink-0 text-amber-600 dark:text-amber-400" />
                <div>
                    <p class="font-bold text-sm">Password change required</p>
                    <p class="mt-0.5">Your administrator requires you to set a new personal password before you can perform other actions in the system.</p>
                </div>
            </div>

            <!-- ── PROFILE CARD ───────────────────────────────────────────────── -->
            <div
                class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 overflow-hidden transition-colors"
                :class="user.must_change_password ? 'order-3' : 'order-2'"
            >
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center gap-2">
                    <UserCircle class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Personal Information</h3>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submitProfile" class="space-y-6">
                        <!-- Avatar Upload -->
                        <div class="flex items-center gap-5">
                            <div class="relative group">
                                <div
                                    v-if="avatarPreview || user.avatar"
                                    class="h-20 w-20 rounded-xl overflow-hidden border border-slate-200 dark:border-slate-700 group-hover:border-blue-500 transition-colors"
                                >
                                    <img :src="avatarPreview || `/storage/${user.avatar}`" class="h-full w-full object-cover" alt="Avatar" />
                                </div>
                                <div v-else class="h-20 w-20 rounded-xl bg-slate-100 dark:bg-slate-700 border border-slate-200 dark:border-slate-700 group-hover:border-blue-500 flex items-center justify-center text-2xl font-bold text-slate-600 dark:text-slate-300 transition-colors">
                                    {{ avatarInitial }}
                                </div>
                                <label class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                    <Camera class="h-5 w-5 text-white" />
                                    <input type="file" accept="image/jpg,image/jpeg,image/png,image/webp" @change="onAvatarChange" class="sr-only" />
                                </label>
                            </div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">
                                <p class="font-bold text-slate-900 dark:text-white text-sm">{{ user.name }}</p>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold mt-1" :class="roleBadgeClass">
                                    {{ user.role?.name ?? 'User' }}
                                </span>
                                <p class="mt-2 text-slate-400 dark:text-slate-500">Click avatar box to upload photo (JPG, PNG, WebP max 2MB)</p>
                            </div>
                        </div>
                        <p v-if="profileForm.errors.avatar" class="text-red-600 dark:text-red-400 text-xs">{{ profileForm.errors.avatar }}</p>

                        <!-- Name & Phone -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Full Name *</label>
                                <input v-model="profileForm.name" type="text" required
                                    class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                    :class="profileForm.errors.name ? 'border-red-500' : ''"
                                />
                                <p v-if="profileForm.errors.name" class="text-red-600 dark:text-red-400 mt-1">{{ profileForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Phone Number</label>
                                <div class="relative">
                                    <Phone class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <input v-model="profileForm.phone" type="text" placeholder="+62 8xx xxxx xxxx"
                                        class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 pl-9 pr-3 py-2.5 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Read-only Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <label class="block font-semibold text-slate-500 dark:text-slate-400 mb-1">Email Address (Read-only)</label>
                                <div class="flex items-center gap-2 px-3 py-2.5 rounded-lg bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 font-mono">
                                    <Mail class="h-4 w-4 text-slate-400 shrink-0" />
                                    {{ user.email }}
                                </div>
                            </div>
                            <div v-if="user.department">
                                <label class="block font-semibold text-slate-500 dark:text-slate-400 mb-1">Department (Read-only)</label>
                                <div class="flex items-center gap-2 px-3 py-2.5 rounded-lg bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400">
                                    <Building2 class="h-4 w-4 text-slate-400 shrink-0" />
                                    {{ user.department.name }}
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end pt-3 border-t border-slate-200 dark:border-slate-700">
                            <button
                                type="submit"
                                :disabled="profileForm.processing"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs disabled:opacity-50 transition-colors"
                            >
                                <CheckCircle2 v-if="!profileForm.processing" class="h-4 w-4" />
                                {{ profileForm.processing ? 'Saving...' : 'Save Profile' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── PASSWORD CARD ──────────────────────────────────────────────── -->
            <div
                class="rounded-xl bg-white dark:bg-slate-800 border transition-all"
                :class="user.must_change_password ? 'order-2 border-2 border-amber-400 dark:border-amber-600 shadow-md ring-1 ring-amber-400/20' : 'order-3 border-slate-200 dark:border-slate-700'"
            >
                <div class="px-6 py-4 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <KeyRound class="h-4 w-4" :class="user.must_change_password ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400'" />
                        <h3 class="text-sm font-bold text-slate-900 dark:text-white">Change Password</h3>
                    </div>
                    <span v-if="user.must_change_password" class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                        Action Required
                    </span>
                </div>

                <div class="p-6">
                    <form @submit.prevent="submitPassword" class="space-y-4 text-xs">
                        <!-- Current Password -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Current Password *</label>
                            <div class="relative">
                                <input v-model="passwordForm.current_password" :type="showCurrentPass ? 'text' : 'password'" required placeholder="Enter current / temporary password"
                                    class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 pr-10 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                    :class="passwordForm.errors.current_password ? 'border-red-500' : ''"
                                />
                                <button type="button" @click="showCurrentPass = !showCurrentPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                    <Eye v-if="!showCurrentPass" class="h-4 w-4" /><EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="passwordForm.errors.current_password" class="text-red-600 dark:text-red-400 mt-1">{{ passwordForm.errors.current_password }}</p>
                        </div>

                        <!-- New Password -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">New Password *</label>
                            <div class="relative">
                                <input v-model="passwordForm.password" :type="showNewPass ? 'text' : 'password'" required placeholder="Min. 8 chars (uppercase, lowercase, number)"
                                    class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 pr-10 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                    :class="passwordForm.errors.password ? 'border-red-500' : ''"
                                />
                                <button type="button" @click="showNewPass = !showNewPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                    <Eye v-if="!showNewPass" class="h-4 w-4" /><EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1">Must contain at least 8 characters, with uppercase, lowercase, and numbers.</p>
                            <!-- Strength Bar -->
                            <div v-if="passwordForm.password" class="mt-2 flex items-center gap-2">
                                <div class="flex gap-1 flex-1">
                                    <div v-for="i in 4" :key="i" class="h-1 flex-1 rounded-full transition-colors duration-200"
                                        :class="i <= passwordStrength.score ? passwordStrength.color.split(' ')[0] : 'bg-slate-200 dark:bg-slate-700'"
                                    ></div>
                                </div>
                                <span class="text-[10px] font-semibold" :class="passwordStrength.color.split(' ')[1]">{{ passwordStrength.label }}</span>
                            </div>
                            <p v-if="passwordForm.errors.password" class="text-red-600 dark:text-red-400 mt-1">{{ passwordForm.errors.password }}</p>
                        </div>

                        <!-- Confirm Password -->
                        <div>
                            <label class="block font-semibold text-slate-700 dark:text-slate-300 mb-1">Confirm New Password *</label>
                            <div class="relative">
                                <input v-model="passwordForm.password_confirmation" :type="showConfirmPass ? 'text' : 'password'" required placeholder="Re-enter new password"
                                    class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 pr-10 text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                    :class="passwordForm.password_confirmation && passwordForm.password !== passwordForm.password_confirmation ? 'border-red-500' : ''"
                                />
                                <button type="button" @click="showConfirmPass = !showConfirmPass" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200">
                                    <Eye v-if="!showConfirmPass" class="h-4 w-4" /><EyeOff v-else class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="passwordForm.password_confirmation && passwordForm.password !== passwordForm.password_confirmation"
                                class="text-red-600 dark:text-red-400 mt-1">Passwords do not match.</p>
                            <p v-if="passwordForm.errors.password_confirmation" class="text-red-600 dark:text-red-400 mt-1">{{ passwordForm.errors.password_confirmation }}</p>
                        </div>

                        <div class="flex justify-end pt-3 border-t border-slate-200 dark:border-slate-700">
                            <button
                                type="submit"
                                :disabled="passwordForm.processing || (!!passwordForm.password_confirmation && passwordForm.password !== passwordForm.password_confirmation)"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-medium text-xs disabled:opacity-50 transition-colors shadow-sm"
                            >
                                <KeyRound v-if="!passwordForm.processing" class="h-4 w-4" />
                                {{ passwordForm.processing ? 'Updating...' : (user.must_change_password ? 'Update Password & Access Dashboard' : 'Update Password') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
