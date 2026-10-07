<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import {
    Bell,
    Check,
    CheckCheck,
    Car,
    Clock,
    Calendar,
    ChevronRight,
    MapPin,
    X
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);

const isOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);
const isLoading = ref(false);
let pollInterval = null;

const dropdownRef = ref(null);

const fetchNotifications = async () => {
    if (!user.value) return;
    try {
        const response = await fetch('/notifications/unread', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const data = await response.json();
            unreadCount.value = data.unread_count || 0;
            notifications.value = data.notifications || [];
        }
    } catch (e) {
        // silent fail
    }
};

const markAsRead = async (notification) => {
    try {
        await fetch(`/notifications/${notification.id}/read`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        // Update local state
        notification.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);

        const actionUrl = notification.data?.action_url;
        if (actionUrl) {
            isOpen.value = false;
            router.visit(actionUrl);
        }
    } catch (e) {
        console.error('Error marking notification read:', e);
    }
};

const markAllRead = async () => {
    try {
        await fetch('/notifications/read-all', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        notifications.value.forEach(n => n.read_at = new Date().toISOString());
        unreadCount.value = 0;
    } catch (e) {
        console.error('Error marking all notifications read:', e);
    }
};

const toggleDropdown = () => {
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        fetchNotifications();
    }
};

const handleClickOutside = (e) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
        isOpen.value = false;
    }
};

onMounted(() => {
    fetchNotifications();
    pollInterval = setInterval(fetchNotifications, 10000);
    document.addEventListener('click', handleClickOutside);

    if (window.Echo && user.value) {
        window.Echo.private(`user.${user.value.id}`)
            .notification(() => {
                fetchNotifications();
            })
            .listen('.TripAssignedEvent', () => {
                fetchNotifications();
            });
    }
});

onBeforeUnmount(() => {
    if (pollInterval) clearInterval(pollInterval);
    document.removeEventListener('click', handleClickOutside);
});

const formatTimeAgo = (dateStr) => {
    if (!dateStr) return '';
    const diff = Math.floor((new Date() - new Date(dateStr)) / 1000);
    if (diff < 60) return 'Baru saja';
    if (diff < 3600) return `${Math.floor(diff / 60)} mnt lalu`;
    if (diff < 86400) return `${Math.floor(diff / 3600)} jam lalu`;
    return `${Math.floor(diff / 86400)} hari lalu`;
};
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <!-- Bell Trigger Button -->
        <button
            type="button"
            @click.stop="toggleDropdown"
            class="relative p-2 rounded-lg text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors focus:outline-none"
            title="Notifikasi"
            aria-label="Notifikasi"
        >
            <Bell class="h-5 w-5" />
            <!-- Glowing Red/Blue Unread Badge -->
            <span
                v-if="unreadCount > 0"
                class="absolute top-1 right-1 flex items-center justify-center min-w-[18px] h-[18px] px-1 text-[10px] font-bold text-white bg-red-600 rounded-full ring-2 ring-white dark:ring-slate-900 animate-pulse"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Notification Drawer -->
        <transition
            enter-active-class="transition ease-out duration-150"
            enter-from-class="opacity-0 scale-95 translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition ease-in duration-100"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-1"
        >
            <div
                v-if="isOpen"
                class="absolute right-0 top-full mt-2 w-80 sm:w-96 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 shadow-2xl z-50 overflow-hidden origin-top-right text-xs"
            >
                <!-- Header -->
                <div class="px-4 py-3 border-b border-slate-100 dark:border-slate-700/80 flex items-center justify-between bg-slate-50/70 dark:bg-slate-900/60">
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-slate-900 dark:text-white text-sm">Notifikasi</span>
                        <span v-if="unreadCount > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                            {{ unreadCount }} Baru
                        </span>
                    </div>

                    <button
                        v-if="unreadCount > 0"
                        @click="markAllRead"
                        class="text-[11px] font-medium text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1"
                    >
                        <CheckCheck class="h-3.5 w-3.5" />
                        Tandai semua dibaca
                    </button>
                </div>

                <!-- Notifications List -->
                <div class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700/60">
                    <div
                        v-if="notifications.length === 0"
                        class="py-12 px-4 text-center text-slate-400 dark:text-slate-500"
                    >
                        <Bell class="h-8 w-8 mx-auto mb-2 opacity-40" />
                        <p class="font-medium">Tidak ada notifikasi baru</p>
                        <p class="text-[10px] mt-0.5">Semua tugas dan pemberitahuan terkini akan muncul di sini</p>
                    </div>

                    <div
                        v-for="item in notifications"
                        :key="item.id"
                        @click="markAsRead(item)"
                        class="p-3.5 hover:bg-slate-50 dark:hover:bg-slate-700/40 transition-colors cursor-pointer flex items-start gap-3"
                        :class="!item.read_at ? 'bg-blue-50/50 dark:bg-blue-950/20' : ''"
                    >
                        <!-- Icon -->
                        <div
                            class="p-2 rounded-xl shrink-0 mt-0.5 text-white"
                            :class="item.data?.type === 'trip_assigned' ? 'bg-blue-600' : 'bg-slate-700'"
                        >
                            <Car class="h-4 w-4" />
                        </div>

                        <!-- Content -->
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between gap-1 mb-0.5">
                                <h4 class="font-bold text-slate-900 dark:text-white truncate text-xs">
                                    {{ item.data?.title || 'Pemberitahuan Sistem' }}
                                </h4>
                                <span class="text-[10px] text-slate-400 dark:text-slate-500 shrink-0">
                                    {{ formatTimeAgo(item.created_at) }}
                                </span>
                            </div>

                            <p class="text-[11px] text-slate-600 dark:text-slate-300 line-clamp-2">
                                {{ item.data?.message || 'Anda memiliki pembaruan tugas perjalanan.' }}
                            </p>

                            <div v-if="item.data?.destination" class="mt-1.5 flex items-center gap-2 text-[10px] text-slate-500 dark:text-slate-400">
                                <span class="flex items-center gap-1 font-semibold text-blue-600 dark:text-blue-400">
                                    <MapPin class="h-3 w-3" />
                                    {{ item.data.destination }}
                                </span>
                                <span v-if="item.data?.vehicle?.plate_number" class="font-mono bg-slate-100 dark:bg-slate-800 px-1.5 py-0.2 rounded border border-slate-200 dark:border-slate-700">
                                    {{ item.data.vehicle.plate_number }}
                                </span>
                            </div>
                        </div>

                        <!-- Unread Dot -->
                        <div v-if="!item.read_at" class="shrink-0 self-center">
                            <span class="h-2 w-2 rounded-full bg-blue-600 block"></span>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>
