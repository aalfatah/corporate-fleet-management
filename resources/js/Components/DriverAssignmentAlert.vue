<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { usePage, router, Link } from '@inertiajs/vue3';
import {
    Bell,
    Car,
    MapPin,
    Calendar,
    Clock,
    User,
    CheckCircle2,
    Navigation,
    X,
    AlertTriangle,
    ShieldCheck
} from 'lucide-vue-next';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const isDriver = computed(() => user.value?.role?.slug === 'driver');

const showModal = ref(false);
const assignedTrip = ref(null);
const isStarting = ref(false);
let pollTimer = null;

// Dual-tone high clarity synthesized chime using Web Audio API
const playChime = () => {
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (!AudioContext) return;
        const ctx = new AudioContext();

        const playTone = (freq, start, duration) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(freq, ctx.currentTime + start);
            gain.gain.setValueAtTime(0.3, ctx.currentTime + start);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + start + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(ctx.currentTime + start);
            osc.stop(ctx.currentTime + start + duration);
        };

        // Note 1: E5 (659.25Hz), Note 2: A5 (880Hz), Note 3: B5 (987.77Hz)
        playTone(659.25, 0, 0.18);
        playTone(880.00, 0.12, 0.22);
        playTone(987.77, 0.26, 0.35);

        // Mobile haptic vibration pattern
        if ('vibrate' in navigator) {
            navigator.vibrate([200, 100, 200, 100, 350]);
        }
    } catch (e) {
        console.warn('Audio notification unavailable:', e);
    }
};

const triggerAssignmentAlert = (tripData) => {
    if (!tripData || !tripData.id && !tripData.booking_id) return;
    const bookingId = tripData.booking_id || tripData.id;

    const acknowledgedKey = `kcc_driver_ack_${bookingId}`;
    if (sessionStorage.getItem(acknowledgedKey)) {
        return; // Already acknowledged in this session
    }

    assignedTrip.value = {
        id: bookingId,
        destination: tripData.destination || 'Tujuan Dinas',
        purpose: tripData.purpose || '-',
        start_time: tripData.start_time,
        end_time: tripData.end_time,
        passenger_count: tripData.passenger_count || 1,
        employee_name: tripData.employee?.name || tripData.employee_name || 'Karyawan',
        department: tripData.employee?.department?.name || tripData.department || 'Operasional',
        vehicle: tripData.vehicle || null,
        assigned_by: tripData.assigned_by || 'PIC / Admin',
    };

    playChime();
    showModal.value = true;
};

const dismissModal = () => {
    if (assignedTrip.value?.id) {
        sessionStorage.setItem(`kcc_driver_ack_${assignedTrip.value.id}`, 'true');
    }
    showModal.value = false;
};

const viewDetails = () => {
    if (assignedTrip.value?.id) {
        sessionStorage.setItem(`kcc_driver_ack_${assignedTrip.value.id}`, 'true');
        showModal.value = false;
        router.visit(`/bookings/${assignedTrip.value.id}`);
    }
};

const startTripNow = () => {
    if (!assignedTrip.value?.id) return;
    isStarting.value = true;
    router.post(`/trips/${assignedTrip.value.id}/start`, {}, {
        onSuccess: () => {
            sessionStorage.setItem(`kcc_driver_ack_${assignedTrip.value.id}`, 'true');
            showModal.value = false;
        },
        onFinish: () => {
            isStarting.value = false;
        },
    });
};

// Check for active assigned trip via resilient polling
const pollActiveAssignment = async () => {
    if (!isDriver.value) return;
    try {
        const response = await fetch('/api/driver/active-assignment', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });
        if (response.ok) {
            const data = await response.json();
            if (data.assignment) {
                triggerAssignmentAlert(data.assignment);
            }
        }
    } catch (err) {
        // Silent error for background polling
    }
};

onMounted(() => {
    if (!isDriver.value) return;

    // 1. Initial check on mount
    pollActiveAssignment();

    // 2. Set interval polling fallback (every 6 seconds for Driver mobile view)
    pollTimer = setInterval(pollActiveAssignment, 6000);

    // 3. Setup WebSocket listener if Laravel Echo is active
    if (window.Echo && user.value) {
        try {
            // Listen to private user channel
            window.Echo.private(`user.${user.value.id}`)
                .listen('.TripAssignedEvent', (e) => {
                    triggerAssignmentAlert(e);
                })
                .listen('TripAssignedEvent', (e) => {
                    triggerAssignmentAlert(e);
                })
                .notification((notification) => {
                    if (notification.type === 'trip_assigned' || notification.booking_id) {
                        triggerAssignmentAlert(notification);
                    }
                });

            // Listen to private driver channel if driver profile loaded
            if (user.value.driver?.id) {
                window.Echo.private(`driver.${user.value.driver.id}`)
                    .listen('.TripAssignedEvent', (e) => {
                        triggerAssignmentAlert(e);
                    })
                    .listen('TripAssignedEvent', (e) => {
                        triggerAssignmentAlert(e);
                    });
            }
        } catch (echoErr) {
            console.warn('Echo setup warning:', echoErr);
        }
    }
});

onBeforeUnmount(() => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
});

const formatDate = (dateStr) => {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleString('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
};
</script>

<template>
    <!-- Interactive Pop-up Alert Modal (Driver Device Only) -->
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="showModal && assignedTrip"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm"
                @click.self="dismissModal"
            >
                <div
                    class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white dark:bg-slate-900 border-2 border-blue-500 shadow-2xl shadow-blue-500/20 transform transition-all animate-bounce-short"
                >
                    <!-- Top Emergency/Alert Banner -->
                    <div class="relative bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 px-5 py-4 text-white">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <span class="relative flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-amber-300"></span>
                                </span>
                                <h3 class="text-base font-extrabold tracking-wide uppercase">
                                    Tugas Perjalanan Baru!
                                </h3>
                            </div>
                            <button
                                @click="dismissModal"
                                class="rounded-lg p-1 text-white/80 hover:text-white hover:bg-white/20 transition-colors"
                                aria-label="Tutup"
                            >
                                <X class="h-5 w-5" />
                            </button>
                        </div>
                        <p class="text-xs text-blue-100 mt-1 font-medium">
                            Anda baru saja ditugaskan untuk mengemudikan kendaraan pool dinas.
                        </p>
                    </div>

                    <!-- Body Information Card -->
                    <div class="p-5 space-y-4 text-xs">
                        <!-- Destination Spotlight Card -->
                        <div class="bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-xl p-3.5 flex items-start gap-3">
                            <div class="p-2 rounded-lg bg-blue-600 text-white shrink-0 mt-0.5">
                                <MapPin class="h-5 w-5" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <span class="text-[10px] uppercase font-bold text-blue-600 dark:text-blue-400 tracking-wider">Tujuan Perjalanan</span>
                                <h4 class="text-base font-bold text-slate-900 dark:text-white truncate">
                                    {{ assignedTrip.destination }}
                                </h4>
                                <p class="text-slate-600 dark:text-slate-300 text-[11px] mt-0.5 line-clamp-2">
                                    Keperluan: {{ assignedTrip.purpose }}
                                </p>
                            </div>
                        </div>

                        <!-- Vehicle & Schedule Grid -->
                        <div class="grid grid-cols-2 gap-3">
                            <!-- Vehicle Details -->
                            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 mb-1">
                                    <Car class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
                                    <span class="font-semibold text-[11px]">Kendaraan Pool</span>
                                </div>
                                <div v-if="assignedTrip.vehicle" class="space-y-0.5">
                                    <p class="text-xs font-bold text-slate-900 dark:text-white">
                                        {{ assignedTrip.vehicle.brand }} {{ assignedTrip.vehicle.model }}
                                    </p>
                                    <span class="inline-block px-2 py-0.5 rounded font-mono font-bold text-xs bg-slate-900 text-amber-400 dark:bg-slate-950 border border-slate-700">
                                        {{ assignedTrip.vehicle.plate_number }}
                                    </span>
                                </div>
                                <p v-else class="text-slate-400 italic">Kendaraan belum terpasang</p>
                            </div>

                            <!-- Requester Details -->
                            <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-xl border border-slate-200 dark:border-slate-700">
                                <div class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400 mb-1">
                                    <User class="h-3.5 w-3.5 text-indigo-600 dark:text-indigo-400" />
                                    <span class="font-semibold text-[11px]">Pemohon / Penumpang</span>
                                </div>
                                <p class="text-xs font-bold text-slate-900 dark:text-white truncate">
                                    {{ assignedTrip.employee_name }}
                                </p>
                                <p class="text-[11px] text-slate-500 dark:text-slate-400">
                                    {{ assignedTrip.department }} • {{ assignedTrip.passenger_count }} Orang
                                </p>
                            </div>
                        </div>

                        <!-- Time Schedule -->
                        <div class="bg-slate-50 dark:bg-slate-800/80 p-3 rounded-xl border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-semibold block">Waktu Berangkat</span>
                                <span class="font-mono font-bold text-slate-900 dark:text-white text-xs">
                                    {{ formatDate(assignedTrip.start_time) }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-[10px] text-slate-500 dark:text-slate-400 uppercase font-semibold block">Estimasi Selesai</span>
                                <span class="font-mono font-medium text-slate-700 dark:text-slate-300 text-xs">
                                    {{ formatDate(assignedTrip.end_time) }}
                                </span>
                            </div>
                        </div>

                        <!-- Assigned By Notice -->
                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500 dark:text-slate-400 px-1">
                            <ShieldCheck class="h-4 w-4 text-green-600 shrink-0" />
                            <span>Ditugaskan oleh: <strong class="font-semibold text-slate-800 dark:text-slate-200">{{ assignedTrip.assigned_by }}</strong></span>
                        </div>
                    </div>

                    <!-- Interactive Action Footer -->
                    <div class="p-4 bg-slate-50 dark:bg-slate-900/90 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row gap-2.5">
                        <button
                            type="button"
                            @click="dismissModal"
                            class="order-2 sm:order-1 flex-1 px-4 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 font-semibold text-xs transition-colors"
                        >
                            Nanti Saja
                        </button>

                        <button
                            type="button"
                            @click="viewDetails"
                            class="order-1 sm:order-2 flex-1 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-md shadow-blue-500/20 transition-colors"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            Lihat Detail Tugas
                        </button>

                        <button
                            type="button"
                            :disabled="isStarting"
                            @click="startTripNow"
                            class="order-3 flex-1 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white font-bold text-xs flex items-center justify-center gap-1.5 shadow-md shadow-emerald-500/20 transition-colors"
                        >
                            <Navigation class="h-4 w-4" />
                            {{ isStarting ? 'Memproses...' : 'Mulai Sekarang' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>

<style scoped>
@keyframes bounceShort {
    0%, 100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-4px);
    }
}
.animate-bounce-short {
    animation: bounceShort 0.4s ease-in-out;
}
</style>
