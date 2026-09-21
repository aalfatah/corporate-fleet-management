<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import { Car, Lock, Mail, ShieldAlert, ArrowRight } from 'lucide-vue-next';

const form = useForm({
    email: 'superadmin@fleet.local',
    password: 'Admin@1234!',
    remember: true,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    });
};

const setCredentials = (email, password) => {
    form.email = email;
    form.password = password;
};
</script>

<template>
    <Head title="Sign In - KCC Fleet" />

    <div class="min-h-screen bg-slate-950 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden">
        <!-- Background Ambient Glow -->
        <div class="pointer-events-none absolute -top-40 left-1/2 -translate-x-1/2 w-[700px] h-[500px] bg-gradient-to-tr from-indigo-600/20 via-blue-600/10 to-transparent rounded-full blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 right-10 w-[500px] h-[400px] bg-gradient-to-br from-emerald-600/10 to-transparent rounded-full blur-3xl"></div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4">
            <!-- Brand Emblem -->
            <div class="flex justify-center">
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center shadow-2xl shadow-indigo-500/30 border border-indigo-400/30">
                    <Car class="h-8 w-8 text-white" />
                </div>
            </div>
            <h2 class="mt-6 text-center text-2xl sm:text-3xl font-extrabold tracking-tight text-white">
                KCC Fleet Portal
            </h2>
            <p class="mt-2 text-center text-xs sm:text-sm text-slate-400">
                Corporate Pool Car & Fleet Management System
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4">
            <div class="bg-slate-900/80 backdrop-blur-xl py-8 px-6 shadow-2xl rounded-3xl border border-slate-800 sm:px-10">
                <form class="space-y-5" @submit.prevent="submit">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                            Corporate Email
                        </label>
                        <div class="mt-1.5 relative rounded-xl shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                                <Mail class="h-4 w-4" />
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                autocomplete="email"
                                class="block w-full rounded-xl bg-slate-950/80 border border-slate-700/80 pl-10 pr-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                                placeholder="name@company.com"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                            <ShieldAlert class="h-3.5 w-3.5 shrink-0" />
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wider text-slate-300">
                            Password
                        </label>
                        <div class="mt-1.5 relative rounded-xl shadow-sm">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-500">
                                <Lock class="h-4 w-4" />
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                autocomplete="current-password"
                                class="block w-full rounded-xl bg-slate-950/80 border border-slate-700/80 pl-10 pr-3 py-2.5 text-sm text-white placeholder-slate-500 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 transition-all"
                                placeholder="••••••••"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-400 flex items-center gap-1">
                            <ShieldAlert class="h-3.5 w-3.5 shrink-0" />
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember & Action -->
                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center gap-2 text-slate-400 cursor-pointer select-none">
                            <input
                                v-model="form.remember"
                                type="checkbox"
                                class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-indigo-600 focus:ring-indigo-500/40"
                            />
                            <span>Stay logged in</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full flex justify-center items-center gap-2 py-3 px-4 border border-transparent rounded-xl text-sm font-semibold text-white bg-gradient-to-r from-indigo-600 to-blue-600 shadow-lg shadow-indigo-600/30 hover:brightness-110 active:scale-[0.98] disabled:opacity-60 transition-all"
                        >
                            <span v-if="form.processing">Authenticating...</span>
                            <span v-else class="flex items-center gap-2">
                                Sign In <ArrowRight class="h-4 w-4" />
                            </span>
                        </button>
                    </div>
                </form>

                <!-- Quick Test Preset -->
                <!-- <div class="mt-8 pt-6 border-t border-slate-800/80">
                    <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider text-center mb-2.5">
                        Demo Account Presets
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            @click="setCredentials('superadmin@fleet.local', 'Admin@1234!')"
                            class="px-2.5 py-1.5 text-xs font-medium rounded-lg bg-slate-800/80 hover:bg-slate-800 text-slate-300 border border-slate-700/60 text-left transition-all"
                        >
                            <span class="block font-bold text-indigo-300">Super Admin</span>
                            <span class="text-[10px] text-slate-400 truncate block">superadmin@fleet.local</span>
                        </button>
                        <button
                            type="button"
                            @click="setCredentials('pic.manager@fleet.local', 'Password@123')"
                            class="px-2.5 py-1.5 text-xs font-medium rounded-lg bg-slate-800/80 hover:bg-slate-800 text-slate-300 border border-slate-700/60 text-left transition-all"
                        >
                            <span class="block font-bold text-amber-300">PIC Approver</span>
                            <span class="text-[10px] text-slate-400 truncate block">pic.manager@fleet.local</span>
                        </button>
                        <button
                            type="button"
                            @click="setCredentials('budi.santoso@fleet.local', 'Password@123')"
                            class="px-2.5 py-1.5 text-xs font-medium rounded-lg bg-slate-800/80 hover:bg-slate-800 text-slate-300 border border-slate-700/60 text-left transition-all"
                        >
                            <span class="block font-bold text-emerald-300">Employee</span>
                            <span class="text-[10px] text-slate-400 truncate block">budi.santoso@fleet.local</span>
                        </button>
                        <button
                            type="button"
                            @click="setCredentials('joko.driver@fleet.local', 'Password@123')"
                            class="px-2.5 py-1.5 text-xs font-medium rounded-lg bg-slate-800/80 hover:bg-slate-800 text-slate-300 border border-slate-700/60 text-left transition-all"
                        >
                            <span class="block font-bold text-sky-300">Driver</span>
                            <span class="text-[10px] text-slate-400 truncate block">joko.driver@fleet.local</span>
                        </button>
                    </div>
                </div> -->
            </div>
        </div>
    </div>
</template>
