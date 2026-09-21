<script setup>
import { ref, computed } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import {
    LayoutDashboard,
    CalendarDays,
    Car,
    Users,
    PlusCircle,
    LogOut,
    CheckCircle2,
    AlertCircle,
    Menu,
    X,
    ChevronRight
} from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: null,
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user);
const flash = computed(() => page.props.flash || {});
const mobileMenuOpen = ref(false);

// const logout = () => {
//     router.post(route ? route('logout') : '/logout');
// };
const logout = () => {
    const logoutUrl = typeof route !== 'undefined' ? route('logout') : '/logout';
    router.post(logoutUrl);
};

const navItems = computed(() => {
    const roleSlug = user.value?.role?.slug;
    const items = [
        { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
        { name: 'Bookings', href: '/bookings', icon: CalendarDays },
        { name: 'Vehicles', href: '/vehicles', icon: Car },
    ];

    if (roleSlug === 'super_admin') {
        items.push({ name: 'Drivers', href: '/drivers', icon: Users });
    }

    return items;
});

const roleBadgeClass = computed(() => {
    switch (user.value?.role?.slug) {
        case 'super_admin': return 'bg-purple-500/20 text-purple-300 border-purple-500/30';
        case 'pic': return 'bg-amber-500/20 text-amber-300 border-amber-500/30';
        case 'driver': return 'bg-sky-500/20 text-sky-300 border-sky-500/30';
        default: return 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30';
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row pb-20 md:pb-0">
        <!-- Toast Notification -->
        <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="flash.success"
                    class="pointer-events-auto flex items-center gap-3 p-4 rounded-xl bg-emerald-950/90 border border-emerald-500/30 text-emerald-200 shadow-2xl backdrop-blur-md"
                >
                    <CheckCircle2 class="h-5 w-5 text-emerald-400 shrink-0" />
                    <p class="text-sm font-medium">{{ flash.success }}</p>
                </div>
            </transition>

            <transition
                enter-active-class="transform ease-out duration-300 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="flash.error"
                    class="pointer-events-auto flex items-center gap-3 p-4 rounded-xl bg-rose-950/90 border border-rose-500/30 text-rose-200 shadow-2xl backdrop-blur-md"
                >
                    <AlertCircle class="h-5 w-5 text-rose-400 shrink-0" />
                    <p class="text-sm font-medium">{{ flash.error }}</p>
                </div>
            </transition>
        </div>

        <!-- Desktop Sidebar -->
        <aside class="hidden md:flex flex-col w-64 border-r border-slate-800 bg-slate-900/60 backdrop-blur-md shrink-0">
            <!-- Brand -->
            <div class="h-16 px-6 flex items-center gap-3 border-b border-slate-800/80">
                <div class="h-9 w-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-indigo-400 flex items-center justify-center shadow-lg shadow-indigo-500/20">
                    <Car class="h-5 w-5 text-white" />
                </div>
                <div>
                    <h1 class="text-sm font-bold tracking-tight text-white flex items-center gap-1.5">
                        KCC Fleet <span class="text-[10px] uppercase font-extrabold px-1.5 py-0.5 rounded bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">Pool</span>
                    </h1>
                    <p class="text-[11px] text-slate-400">Corporate Management</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium transition-all"
                    :class="[
                        $page.url.startsWith(item.href)
                            ? 'bg-indigo-600/15 text-indigo-400 border border-indigo-500/30 shadow-sm'
                            : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60'
                    ]"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                    {{ item.name }}
                </Link>

                <div v-if="user?.role?.slug === 'employee' || user?.role?.slug === 'super_admin'" class="pt-4">
                    <Link
                        href="/bookings/create"
                        class="flex items-center justify-center gap-2 w-full px-4 py-2.5 rounded-xl bg-gradient-to-r from-indigo-600 to-blue-600 text-white text-sm font-semibold shadow-lg shadow-indigo-500/25 hover:brightness-110 active:scale-95 transition-all"
                    >
                        <PlusCircle class="h-4 w-4" />
                        New Request
                    </Link>
                </div>
            </nav>

            <!-- User Info & Logout -->
            <div class="p-4 border-t border-slate-800/80 bg-slate-900/40">
                <div class="flex items-center gap-3 mb-3">
                    <div class="h-10 w-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center text-sm font-bold text-indigo-400">
                        {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-white truncate">{{ user?.name }}</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-[10px] font-medium px-2 py-0.5 rounded-full border" :class="roleBadgeClass">
                                {{ user?.role?.name || 'User' }}
                            </span>
                            <span v-if="user?.department?.code" class="text-[10px] text-slate-400 font-mono">
                                {{ user.department.code }}
                            </span>
                        </div>
                    </div>
                </div>

                <button
                    type="button"
                    @click.prevent="logout"
                    class="relative z-10 w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer"
                >
                    <LogOut class="h-3.5 w-3.5" />
                    Sign Out
                </button>
                <!-- <Link
                    href="/logout"
                    method="post"
                    as="button"
                    class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-xs font-medium text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer"
                >
                    <LogOut class="h-3.5 w-3.5" />
                    <span>Sign Out</span>
                </Link> -->
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Mobile Header -->
            <header class="md:hidden sticky top-0 z-30 h-14 bg-slate-900/90 backdrop-blur-md border-b border-slate-800 px-4 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-lg bg-indigo-600 flex items-center justify-center">
                        <Car class="h-4 w-4 text-white" />
                    </div>
                    <span class="text-sm font-bold tracking-tight text-white">KCC Fleet</span>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-medium px-2 py-0.5 rounded-full border" :class="roleBadgeClass">
                        {{ user?.role?.name || 'User' }}
                    </span>
                    <button
                        @click="logout"
                        class="p-2 text-slate-400 hover:text-rose-400 active:scale-95"
                        title="Sign Out"
                    >
                        <LogOut class="h-4 w-4" />
                    </button>
                    <!-- <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="p-2 text-slate-400 hover:text-rose-400 active:scale-95 cursor-pointer"
                        title="Sign Out"
                    >
                        <LogOut class="h-4 w-4" />
                    </Link> -->
                </div>
            </header>

            <!-- Page Title Bar (Desktop) -->
            <div v-if="title || $slots.header" class="hidden md:flex h-16 px-8 items-center justify-between border-b border-slate-800/80 bg-slate-900/30">
                <h2 class="text-lg font-bold text-white tracking-tight">{{ title }}</h2>
                <slot name="header" />
            </div>

            <!-- Page Body -->
            <main class="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
                <slot />
            </main>
        </div>

        <!-- Mobile Bottom Nav Bar (PWA optimized for Drivers and Mobile Employees) -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-900/95 backdrop-blur-lg border-t border-slate-800 px-3 py-2 flex items-center justify-around">
            <Link
                v-for="item in navItems"
                :key="item.name"
                :href="item.href"
                class="flex flex-col items-center gap-1 py-1 px-3 rounded-lg text-[11px] font-medium transition-colors"
                :class="[
                    $page.url.startsWith(item.href)
                        ? 'text-indigo-400 font-semibold'
                        : 'text-slate-400 hover:text-slate-200'
                ]"
            >
                <component :is="item.icon" class="h-5 w-5" />
                <span>{{ item.name }}</span>
            </Link>

            <Link
                v-if="user?.role?.slug === 'employee' || user?.role?.slug === 'super_admin'"
                href="/bookings/create"
                class="flex flex-col items-center gap-1 py-1 px-3 text-[11px] font-medium text-indigo-400"
            >
                <div class="h-7 w-7 rounded-full bg-indigo-600 text-white flex items-center justify-center -mt-2 shadow-lg shadow-indigo-600/40">
                    <PlusCircle class="h-4 w-4" />
                </div>
                <span>New</span>
            </Link>
        </nav>
    </div>
</template>
