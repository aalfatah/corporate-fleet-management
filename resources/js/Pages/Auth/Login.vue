<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import { Car, Lock, Mail, ShieldAlert, ArrowRight, Sun, Moon } from 'lucide-vue-next';

const form = useForm({
    email: 'superadmin@fleet.local',
    password: 'Admin@1234!',
    remember: true,
});

const isDark = ref(false);

onMounted(() => {
    isDark.value = document.documentElement.classList.contains('dark');
});

const toggleTheme = () => {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.setItem('theme', 'dark');
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.setItem('theme', 'light');
    }
};

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Sign In - KCC Fleet" />

    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative transition-colors duration-150">
        <!-- Theme Toggle in Corner -->
        <div class="absolute top-4 right-4 z-20">
            <button
                type="button"
                @click="toggleTheme"
                class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-800 transition-colors"
                :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
            >
                <Sun v-if="isDark" class="h-5 w-5 text-amber-500" />
                <Moon v-else class="h-5 w-5 text-slate-600" />
            </button>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md px-4 text-center">
            <!-- Brand Emblem (Solid Blue) -->
            <div class="flex justify-center">
                <div class="h-12 w-12 rounded-xl bg-blue-600 flex items-center justify-center text-white">
                    <Car class="h-6 w-6" />
                </div>
            </div>
            <h2 class="mt-4 text-center text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                KCC Fleet Portal
            </h2>
            <p class="mt-1 text-center text-xs text-slate-500 dark:text-slate-400">
                Corporate Pool Car & Fleet Management System
            </p>
        </div>

        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md px-4">
            <div class="bg-white dark:bg-slate-800 py-8 px-6 rounded-xl border border-slate-200 dark:border-slate-700 sm:px-10 transition-colors shadow-sm">
                <form class="space-y-4" @submit.prevent="submit">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Corporate Email
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <Mail class="h-4 w-4" />
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                                class="block w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 pl-9 pr-3 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                placeholder="name@homecc.com"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                            <ShieldAlert class="h-3.5 w-3.5 shrink-0" />
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1">
                            Password
                        </label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <Lock class="h-4 w-4" />
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                autocomplete="current-password"
                                class="block w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 pl-9 pr-3 py-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                placeholder="••••••••"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                            <ShieldAlert class="h-3.5 w-3.5 shrink-0" />
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember -->
                    <div class="flex items-center justify-between text-xs pt-1">
                        <label class="flex items-center gap-2 text-slate-600 dark:text-slate-400 cursor-pointer select-none">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-300 dark:border-slate-600 text-blue-600 focus:ring-blue-600"
                            />
                            <span>Stay logged in</span>
                        </label>
                    </div>

                    <!-- Submit Button (Solid Blue Flat) -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex justify-center items-center gap-2 py-2.5 px-4 rounded-lg text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 disabled:opacity-50 transition-colors"
                        >
                            <span v-if="form.processing">Signing in...</span>
                            <span v-else class="flex items-center gap-1.5">
                                Sign In <ArrowRight class="h-4 w-4" />
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
