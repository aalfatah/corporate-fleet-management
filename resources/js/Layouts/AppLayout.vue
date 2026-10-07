<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import NotificationBell from '@/Components/NotificationBell.vue';
import DriverAssignmentAlert from '@/Components/DriverAssignmentAlert.vue';
import {
    LayoutDashboard,
    CalendarDays,
    Car,
    Users,
    Plus,
    LogOut,
    CheckCircle2,
    AlertCircle,
    UserCircle,
    KeyRound,
    ShieldAlert,
    Sun,
    Moon,
    ChevronDown,
    ChevronRight,
    Building2,
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

// Theme toggle
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

const logout = () => {
    const logoutUrl = typeof route !== 'undefined' ? route('logout') : '/logout';
    router.post(logoutUrl);
};

// Mobile avatar dropdown
const showUserMenu = ref(false);
const userMenuRef = ref(null);

const toggleUserMenu = () => {
    showUserMenu.value = !showUserMenu.value;
};

const closeUserMenu = (e) => {
    if (userMenuRef.value && !userMenuRef.value.contains(e.target)) {
        showUserMenu.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', closeUserMenu);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', closeUserMenu);
});

const navItems = computed(() => {
    const roleSlug = user.value?.role?.slug;
    const items = [
        { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
        { name: 'Bookings', href: '/bookings', icon: CalendarDays },
        { name: 'Vehicles', href: '/vehicles', icon: Car },
    ];

    if (roleSlug === 'super_admin' || roleSlug === 'pic') {
        items.push({ name: 'Drivers', href: '/drivers', icon: Users });
    }

    if (roleSlug === 'super_admin') {
        items.push({ name: 'Users', href: '/admin/users', icon: UserCircle });
        items.push({ name: 'Departments', href: '/admin/departments', icon: Building2 });
    }

    return items;
});

// Unified mobile bottom nav items (ordered) with role-based logic.
// Special markers: isNew=true for FAB center, isManage=true for Manage sheet trigger.
// Layouts:
//   driver/pic:   [Dashboard] [Bookings] [Vehicles]             (3 items, balanced)
//   employee:     [Dashboard] [Bookings] [●New●] [Vehicles]     (4 items)
//   super_admin:  [Dashboard] [Bookings] [●New●] [Vehicles] [Manage] (5 items)
const mobileNavItems = computed(() => {
    const roleSlug = user.value?.role?.slug;
    const items = [
        { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard },
        { name: 'Bookings', href: '/bookings', icon: CalendarDays },
    ];

    // Insert New FAB in center position for roles that can book
    if (roleSlug === 'employee' || roleSlug === 'super_admin') {
        items.push({ name: 'New', href: '/bookings/create', isNew: true });
    }

    items.push({ name: 'Vehicles', href: '/vehicles', icon: Car });

    if (roleSlug === 'pic') {
        items.push({ name: 'Drivers', href: '/drivers', icon: Users });
    }

    if (roleSlug === 'super_admin') {
        items.push({ name: 'Manage', isManage: true, icon: Users });
    }

    return items;
});

// For "Manage" tab active state: active when on /drivers, /admin/users, or /admin/departments
const isManageActive = computed(() => {
    return page.url?.startsWith('/drivers')
        || page.url?.startsWith('/admin/users')
        || page.url?.startsWith('/admin/departments');
});

// Manage bottom sheet (super_admin)
const showManageSheet = ref(false);

const mustChangePassword = computed(() => user.value?.must_change_password ?? false);

const roleBadgeClass = computed(() => {
    switch (user.value?.role?.slug) {
        case 'super_admin':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400';
        case 'pic':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
        case 'driver':
            return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300';
        default:
            return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
    }
});
</script>

<template>
    <div class="min-h-screen bg-slate-50 dark:bg-slate-900 text-slate-900 dark:text-slate-100 flex flex-col md:flex-row pb-16 md:pb-0 font-sans transition-colors duration-150">
        <!-- Toast Notification -->
        <div class="fixed top-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none">
            <transition
                enter-active-class="transform ease-out duration-200 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="flash.success"
                    class="pointer-events-auto flex items-center gap-3 p-4 rounded-xl bg-white dark:bg-slate-800 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-300 shadow-lg"
                >
                    <CheckCircle2 class="h-5 w-5 text-green-600 dark:text-green-400 shrink-0" />
                    <p class="text-sm font-medium">{{ flash.success }}</p>
                </div>
            </transition>

            <transition
                enter-active-class="transform ease-out duration-200 transition"
                enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
                enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
                leave-active-class="transition ease-in duration-100"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="flash.error"
                    class="pointer-events-auto flex items-center gap-3 p-4 rounded-xl bg-white dark:bg-slate-800 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-300 shadow-lg"
                >
                    <AlertCircle class="h-5 w-5 text-red-600 dark:text-red-400 shrink-0" />
                    <p class="text-sm font-medium">{{ flash.error }}</p>
                </div>
            </transition>
        </div>

        <!-- Desktop Sidebar -->
        <aside class="hidden md:flex flex-col w-64 border-r border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shrink-0 select-none">
            <!-- Brand -->
            <div class="h-16 px-6 flex items-center gap-3 border-b border-slate-200 dark:border-slate-800">
                <div class="h-8 w-8 rounded-lg bg-blue-600 flex items-center justify-center text-white shrink-0">
                    <Car class="h-5 w-5" />
                </div>
                <div>
                    <h1 class="text-sm font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-1.5">
                        KCC Fleet
                        <span class="text-[10px] uppercase font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Pool</span>
                    </h1>
                    <p class="text-[11px] text-slate-500 dark:text-slate-400 font-normal">Corporate Management</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
                <Link
                    v-for="item in navItems"
                    :key="item.name"
                    :href="item.href"
                    class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm transition-colors"
                    :class="[
                        $page.url.startsWith(item.href)
                            ? 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 font-semibold'
                            : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white font-medium'
                    ]"
                >
                    <component :is="item.icon" class="h-4 w-4" />
                    {{ item.name }}
                </Link>

                <div v-if="user?.role?.slug === 'employee' || user?.role?.slug === 'super_admin'" class="pt-4 px-1">
                    <Link
                        href="/bookings/create"
                        class="flex items-center justify-center gap-2 w-full px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium transition-colors"
                    >
                        <Plus class="h-4 w-4" />
                        New Request
                    </Link>
                </div>
            </nav>

            <!-- User Info, Profile & Logout -->
            <div class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50">
                <div class="flex items-center gap-3 mb-3">
                    <img
                        v-if="user?.avatar"
                        :src="`/storage/${user.avatar}`"
                        class="h-9 w-9 rounded-full object-cover border border-slate-200 dark:border-slate-700 shrink-0"
                        alt=""
                    />
                    <div
                        v-else
                        class="h-9 w-9 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center text-xs font-bold shrink-0"
                    >
                        {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                        <div class="flex items-center gap-1.5 mt-0.5">
                            <span class="text-[10px] font-semibold px-2 py-0.2 rounded-full" :class="roleBadgeClass">
                                {{ user?.role?.name || 'User' }}
                            </span>
                            <span v-if="user?.department?.code" class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">
                                {{ user.department.code }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="space-y-1">
                    <Link
                        href="/profile"
                        class="w-full flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 hover:text-slate-900 dark:hover:text-white transition-colors"
                    >
                        <KeyRound class="h-3.5 w-3.5" />
                        My Profile & Password
                    </Link>

                    <button
                        type="button"
                        @click.prevent="logout"
                        class="w-full flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 transition-colors cursor-pointer"
                    >
                        <LogOut class="h-3.5 w-3.5" />
                        Sign Out
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main Content Wrapper -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Mobile Header (PWA Top Bar) -->
            <header class="md:hidden sticky top-0 z-30 h-14 bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-4 flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                    <div class="h-8 w-8 rounded-lg bg-blue-600 flex items-center justify-center text-white">
                        <Car class="h-4 w-4" />
                    </div>
                    <span class="text-sm font-bold tracking-tight text-slate-900 dark:text-white">KCC Fleet</span>
                </div>

                <div class="flex items-center gap-1.5">
                    <!-- Notification Bell -->
                    <NotificationBell />

                    <!-- Theme Toggle -->
                    <button
                        type="button"
                        @click="toggleTheme"
                        class="p-1.5 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        title="Toggle theme"
                    >
                        <Sun v-if="isDark" class="h-4 w-4 text-amber-500" />
                        <Moon v-else class="h-4 w-4 text-slate-500" />
                    </button>

                    <!-- Avatar Dropdown -->
                    <div ref="userMenuRef" class="relative">
                        <button
                            type="button"
                            @click.stop="toggleUserMenu"
                            class="flex items-center gap-1 rounded-lg p-1 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            aria-label="User menu"
                        >
                            <img
                                v-if="user?.avatar"
                                :src="`/storage/${user.avatar}`"
                                class="h-7 w-7 rounded-full object-cover border border-slate-200 dark:border-slate-700"
                                alt=""
                            />
                            <div
                                v-else
                                class="h-7 w-7 rounded-full bg-slate-200 dark:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center text-xs font-bold border border-slate-200 dark:border-slate-700"
                            >
                                {{ user?.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                            </div>
                            <ChevronDown class="h-3 w-3 text-slate-400 transition-transform duration-150" :class="{ 'rotate-180': showUserMenu }" />
                        </button>

                        <!-- Dropdown Menu -->
                        <transition
                            enter-active-class="transition ease-out duration-100"
                            enter-from-class="opacity-0 scale-95 translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                            leave-active-class="transition ease-in duration-75"
                            leave-from-class="opacity-100 scale-100 translate-y-0"
                            leave-to-class="opacity-0 scale-95 translate-y-1"
                        >
                            <div
                                v-if="showUserMenu"
                                class="absolute right-0 top-full mt-2 w-52 rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-xl z-50 overflow-hidden origin-top-right"
                            >
                                <!-- User info header -->
                                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white truncate">{{ user?.name }}</p>
                                    <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded-full mt-1 inline-block" :class="roleBadgeClass">
                                        {{ user?.role?.name || 'User' }}
                                    </span>
                                </div>
                                <!-- Actions -->
                                <div class="p-1.5">
                                    <Link
                                        href="/profile"
                                        @click="showUserMenu = false"
                                        class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors"
                                    >
                                        <KeyRound class="h-3.5 w-3.5 text-slate-400" />
                                        My Profile & Password
                                    </Link>
                                    <button
                                        type="button"
                                        @click="logout"
                                        class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition-colors"
                                    >
                                        <LogOut class="h-3.5 w-3.5" />
                                        Sign Out
                                    </button>
                                </div>
                            </div>
                        </transition>
                    </div>
                </div>
            </header>

            <!-- Force Change Password Banner -->
            <div
                v-if="mustChangePassword"
                class="bg-amber-50 dark:bg-amber-950/40 border-b border-amber-200 dark:border-amber-800 px-4 py-2.5 flex items-center gap-2 text-amber-800 dark:text-amber-300 text-xs"
            >
                <ShieldAlert class="h-4 w-4 shrink-0" />
                <span>Your account requires a password change. Please update your password now.</span>
                <Link href="/profile" class="ml-auto font-semibold underline hover:text-amber-900 dark:hover:text-amber-200">Change Password →</Link>
            </div>

            <!-- Page Title Bar (Desktop) -->
            <div v-if="title || $slots.header" class="hidden md:flex h-16 px-8 items-center justify-between border-b border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900">
                <h2 class="text-base font-bold text-slate-900 dark:text-white tracking-tight">{{ title }}</h2>
                <div class="flex items-center gap-3">
                    <!-- Notification Bell -->
                    <NotificationBell />

                    <button
                        type="button"
                        @click="toggleTheme"
                        class="p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        :title="isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode'"
                    >
                        <Sun v-if="isDark" class="h-4 w-4 text-amber-500" />
                        <Moon v-else class="h-4 w-4 text-slate-600 dark:text-slate-400" />
                    </button>
                    <slot name="header" />
                </div>
            </div>

            <!-- Page Body -->
            <main class="flex-1 p-4 md:p-8 max-w-7xl w-full mx-auto">
                <slot />
            </main>
        </div>

        <!-- Mobile Bottom Nav Bar -->
        <!-- Flat justify-around layout: items are ordered per role, New FAB always in center -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800">
            <div class="flex items-end justify-around px-1 pb-2 pt-1">
                <template v-for="item in mobileNavItems" :key="item.name">

                    <!-- New Request FAB -->
                    <Link
                        v-if="item.isNew"
                        :href="item.href"
                        class="flex flex-col items-center gap-0.5 px-3 text-[10px] font-semibold text-blue-600 dark:text-blue-400"
                    >
                        <div class="h-11 w-11 rounded-full bg-blue-600 shadow-lg shadow-blue-500/30 text-white flex items-center justify-center -mt-4 border-[3px] border-white dark:border-slate-900 transition-transform duration-150 active:scale-90">
                            <Plus class="h-5 w-5" />
                        </div>
                        <span class="mt-0.5">New</span>
                    </Link>

                    <!-- Manage tab → opens bottom sheet -->
                    <button
                        v-else-if="item.isManage"
                        type="button"
                        @click.stop="showManageSheet = true"
                        class="flex flex-col items-center gap-0.5 py-2 px-3 rounded-xl text-[10px] font-medium transition-colors"
                        :class="isManageActive ? 'text-blue-600 dark:text-blue-400' : 'text-slate-400 dark:text-slate-500'"
                    >
                        <component
                            :is="item.icon"
                            class="h-5 w-5 shrink-0 transition-transform duration-150"
                            :class="{ 'scale-110': isManageActive }"
                        />
                        <span>{{ item.name }}</span>
                    </button>

                    <!-- Regular nav link -->
                    <Link
                        v-else
                        :href="item.href"
                        class="flex flex-col items-center gap-0.5 py-2 px-3 rounded-xl text-[10px] font-medium transition-colors"
                        :class="[
                            $page.url.startsWith(item.href)
                                ? 'text-blue-600 dark:text-blue-400'
                                : 'text-slate-400 dark:text-slate-500 hover:text-slate-700 dark:hover:text-slate-200'
                        ]"
                    >
                        <component
                            :is="item.icon"
                            class="h-5 w-5 shrink-0 transition-transform duration-150"
                            :class="{ 'scale-110': $page.url.startsWith(item.href) }"
                        />
                        <span>{{ item.name }}</span>
                    </Link>

                </template>
            </div>

            <!-- Manage Bottom Sheet (Super Admin) -->
            <transition
                enter-active-class="transition ease-out duration-200"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-if="showManageSheet"
                    class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm"
                    @click="showManageSheet = false"
                />
            </transition>

            <transition
                enter-active-class="transition ease-out duration-250"
                enter-from-class="translate-y-full opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition ease-in duration-150"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-full opacity-0"
            >
                <div
                    v-if="showManageSheet"
                    class="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-slate-900 rounded-t-2xl shadow-2xl border-t border-slate-200 dark:border-slate-700 pb-safe"
                >
                    <!-- Handle bar -->
                    <div class="flex justify-center pt-3 pb-1">
                        <div class="w-10 h-1 rounded-full bg-slate-300 dark:bg-slate-600" />
                    </div>

                    <!-- Sheet title -->
                    <div class="px-5 py-3 border-b border-slate-100 dark:border-slate-800">
                        <p class="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Admin Management</p>
                    </div>

                    <!-- Options -->
                    <div class="p-3 space-y-1">
                        <Link
                            href="/drivers"
                            @click="showManageSheet = false"
                            class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-colors"
                            :class="$page.url.startsWith('/drivers') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        >
                            <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0" :class="$page.url.startsWith('/drivers') ? 'bg-blue-100 dark:bg-blue-900/40' : 'bg-slate-100 dark:bg-slate-800'">
                                <Users class="h-5 w-5" :class="$page.url.startsWith('/drivers') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-400'" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Drivers</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Manage pool drivers</p>
                            </div>
                            <ChevronRight class="h-4 w-4 ml-auto text-slate-300 dark:text-slate-600" />
                        </Link>

                        <Link
                            href="/admin/users"
                            @click="showManageSheet = false"
                            class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-colors"
                            :class="$page.url.startsWith('/admin/users') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        >
                            <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0" :class="$page.url.startsWith('/admin/users') ? 'bg-blue-100 dark:bg-blue-900/40' : 'bg-slate-100 dark:bg-slate-800'">
                                <UserCircle class="h-5 w-5" :class="$page.url.startsWith('/admin/users') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-400'" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Users</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Manage system users</p>
                            </div>
                            <ChevronRight class="h-4 w-4 ml-auto text-slate-300 dark:text-slate-600" />
                        </Link>

                        <Link
                            href="/admin/departments"
                            @click="showManageSheet = false"
                            class="flex items-center gap-3 px-4 py-3.5 rounded-xl transition-colors"
                            :class="$page.url.startsWith('/admin/departments') ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'"
                        >
                            <div class="h-9 w-9 rounded-xl flex items-center justify-center shrink-0" :class="$page.url.startsWith('/admin/departments') ? 'bg-blue-100 dark:bg-blue-900/40' : 'bg-slate-100 dark:bg-slate-800'">
                                <Building2 class="h-5 w-5" :class="$page.url.startsWith('/admin/departments') ? 'text-blue-600 dark:text-blue-400' : 'text-slate-500 dark:text-slate-400'" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold">Departments</p>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500">Manage organizational units</p>
                            </div>
                            <ChevronRight class="h-4 w-4 ml-auto text-slate-300 dark:text-slate-600" />
                        </Link>
                    </div>

                    <!-- Cancel button -->
                    <div class="px-3 pb-4">
                        <button
                            type="button"
                            @click="showManageSheet = false"
                            class="w-full py-3 rounded-xl text-sm font-medium text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors"
                        >
                            Cancel
                        </button>
                    </div>
                </div>
            </transition>
        </nav>

        <!-- Real-Time Driver Pop-Up Alert Modal -->
        <DriverAssignmentAlert />
    </div>
</template>
