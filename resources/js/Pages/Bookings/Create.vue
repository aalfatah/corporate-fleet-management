<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import {
    Calendar,
    Car,
    Clock,
    MapPin,
    FileText,
    Users,
    ShieldAlert,
    CheckCircle2,
    ArrowLeft,
    AlertCircle,
    ShieldCheck,
    Sparkles,
    Ban,
    Info,
    Check
} from 'lucide-vue-next';

const props = defineProps({
    vehicles: {
        type: Array,
        default: () => [],
    },
    managers: {
        type: Array,
        default: () => [],
    },
});

// Default to tomorrow 09:00 -> 17:00
const getDefaultDateTime = (offsetDays = 1, hour = 9) => {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    d.setHours(hour, 0, 0, 0);
    const tzOffset = d.getTimezoneOffset() * 60000;
    const localISOTime = new Date(d.getTime() - tzOffset).toISOString().slice(0, 16);
    return localISOTime;
};

const form = useForm({
    vehicle_id: '',
    start_time: getDefaultDateTime(1, 9),
    end_time: getDefaultDateTime(1, 17),
    destination: '',
    purpose: '',
    passenger_count: 1,
});

const isCheckingAvailability = ref(false);
const availableVehicles = ref([]);
const unavailableVehicles = ref([]);
const recommendedVehicle = ref(null);
const autoSwitchedNotice = ref('');

const formatTimeRange = (startStr, endStr) => {
    if (!startStr || !endStr) return '-';
    const s = new Date(startStr);
    const e = new Date(endStr);
    const dateFormatted = s.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
    const startTime = s.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    const endTime = e.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    return `${dateFormatted}, ${startTime} - ${endTime} WIB`;
};

const checkAvailability = async () => {
    if (!form.start_time || !form.end_time || form.start_time >= form.end_time) return;

    isCheckingAvailability.value = true;
    try {
        const response = await fetch(
            `/api/vehicles/available?start_time=${encodeURIComponent(form.start_time)}&end_time=${encodeURIComponent(form.end_time)}`,
            {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }
        );
        if (response.ok) {
            const data = await response.json();
            availableVehicles.value = data.available_vehicles || [];
            unavailableVehicles.value = data.unavailable_vehicles || [];
            recommendedVehicle.value = data.recommended_vehicle || null;

            // Check if current vehicle is still valid
            const isCurrentlyAvailable = availableVehicles.value.some(v => v.id === form.vehicle_id);

            if (!isCurrentlyAvailable) {
                if (data.recommended_vehicle) {
                    const previousVehicle = props.vehicles.find(v => v.id === form.vehicle_id);
                    form.vehicle_id = data.recommended_vehicle.id;
                    if (previousVehicle) {
                        autoSwitchedNotice.value = `Mobil sebelumnya (${previousVehicle.brand} ${previousVehicle.model}) sedang bertugas pada jam ini. Otomatis disarankan ke ${data.recommended_vehicle.brand} ${data.recommended_vehicle.model}.`;
                    }
                } else {
                    form.vehicle_id = '';
                }
            } else {
                autoSwitchedNotice.value = '';
            }
        }
    } catch (e) {
        console.error('Error checking vehicle availability', e);
    } finally {
        isCheckingAvailability.value = false;
    }
};

onMounted(() => {
    checkAvailability();
});

watch(() => [form.start_time, form.end_time], () => {
    checkAvailability();
});

const selectVehicle = (v) => {
    if (!v.is_available) return;
    form.vehicle_id = v.id;
    autoSwitchedNotice.value = '';
};

const submit = () => {
    if (!form.vehicle_id) {
        alert('Silakan pilih salah satu kendaraan yang berstatus Tersedia.');
        return;
    }
    form.post('/bookings');
};
</script>

<template>
    <AppLayout title="New Booking Request">
        <Head title="Book Pool Car - KCC Fleet" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Header Bar -->
            <div>
                <Link
                    href="/bookings"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 dark:text-slate-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors mb-2"
                >
                    <ArrowLeft class="h-3.5 w-3.5" /> Back to Bookings
                </Link>
                <h2 class="text-xl font-bold text-slate-900 dark:text-white tracking-tight">Formulir Pemesanan Mobil Pool</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                    Pilih jadwal perjalanan dinas Anda dan tentukan mobil armada yang tersedia.
                </p>
            </div>

            <!-- Auto Switched Notice Alert -->
            <transition
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
                leave-active-class="transition duration-150 ease-in"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 -translate-y-2"
            >
                <div
                    v-if="autoSwitchedNotice"
                    class="rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-300 dark:border-amber-800 p-4 text-xs text-amber-900 dark:text-amber-200 flex items-start gap-3 shadow-sm"
                >
                    <Sparkles class="h-5 w-5 text-amber-600 dark:text-amber-400 shrink-0 mt-0.5" />
                    <div class="flex-1">
                        <span class="font-bold">Penyesuaian Kendaraan Otomatis:</span>
                        <p class="mt-0.5">{{ autoSwitchedNotice }}</p>
                    </div>
                    <button
                        type="button"
                        @click="autoSwitchedNotice = ''"
                        class="text-amber-600 hover:text-amber-900 font-bold ml-2 text-sm"
                    >
                        ✕
                    </button>
                </div>
            </transition>

            <!-- Main Form Card -->
            <div class="rounded-xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 p-6 md:p-8 transition-colors shadow-sm">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- 1. Jadwal Perjalanan Row -->
                    <div>
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-3 flex items-center gap-2">
                            <Clock class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            1. Tentukan Jadwal Perjalanan
                        </h3>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 dark:bg-slate-900/60 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Waktu Mulai Berangkat *
                                </label>
                                <input
                                    v-model="form.start_time"
                                    type="datetime-local"
                                    required
                                    class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                />
                                <p v-if="form.errors.start_time" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.start_time }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">
                                    Estimasi Selesai / Kembali *
                                </label>
                                <input
                                    v-model="form.end_time"
                                    type="datetime-local"
                                    required
                                    class="w-full rounded-lg bg-white dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                                />
                                <p v-if="form.errors.end_time" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                    <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.end_time }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Vehicle Selection with Interactive Cards & Conflict Details -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-2">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2">
                                <Car class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                                2. Pilih Kendaraan Pool (Ketersediaan Real-Time)
                            </h3>

                            <span v-if="isCheckingAvailability" class="text-xs text-blue-600 dark:text-blue-400 font-semibold animate-pulse flex items-center gap-1">
                                <Clock class="h-3.5 w-3.5" /> Memeriksa status armada...
                            </span>
                            <span v-else class="text-xs text-slate-500 dark:text-slate-400">
                                {{ availableVehicles.length }} Mobil Tersedia • {{ unavailableVehicles.length }} Tidak Tersedia
                            </span>
                        </div>

                        <!-- Error Message if Vehicle Conflicted -->
                        <div v-if="form.errors.vehicle_id" class="rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 p-3 text-xs text-red-700 dark:text-red-300 flex items-center gap-2">
                            <AlertCircle class="h-4 w-4 shrink-0" />
                            <span>{{ form.errors.vehicle_id }}</span>
                        </div>

                        <!-- SECTION A: Mobil Tersedia (Recommended & Clickable) -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1.5">
                                    <CheckCircle2 class="h-4 w-4" /> Mobil Tersedia untuk Jadwal Ini
                                </span>
                                <span class="text-[11px] text-slate-500 dark:text-slate-400">
                                    Klik pada mobil untuk memilih
                                </span>
                            </div>

                            <div v-if="availableVehicles.length === 0" class="rounded-xl bg-amber-50 dark:bg-amber-950/30 border border-amber-300 dark:border-amber-800 p-4 text-xs text-amber-800 dark:text-amber-300 text-center">
                                <Ban class="h-6 w-6 mx-auto mb-1.5 text-amber-600" />
                                <p class="font-bold">Tidak ada kendaraan yang tersedia pada jam ini!</p>
                                <p class="text-[11px] mt-0.5">Seluruh armada sedang bertugas atau dalam perawatan. Silakan sesuaikan jam atau tanggal keberangkatan.</p>
                            </div>

                            <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div
                                    v-for="(v, idx) in availableVehicles"
                                    :key="v.id"
                                    @click="selectVehicle(v)"
                                    class="relative p-3.5 rounded-xl border-2 transition-all cursor-pointer select-none flex flex-col justify-between"
                                    :class="[
                                        form.vehicle_id === v.id
                                            ? 'border-blue-600 bg-blue-50/70 dark:bg-blue-950/40 shadow-md ring-2 ring-blue-500/20'
                                            : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800/80 hover:border-blue-300 dark:hover:border-blue-700'
                                    ]"
                                >
                                    <div class="flex items-start justify-between gap-2">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-sm font-bold text-slate-900 dark:text-white">
                                                    {{ v.brand }} {{ v.model }}
                                                </h4>
                                                <span
                                                    v-if="idx === 0"
                                                    class="px-1.5 py-0.5 rounded text-[10px] font-extrabold bg-blue-100 text-blue-700 dark:bg-blue-900/60 dark:text-blue-300"
                                                >
                                                    Saran Utama
                                                </span>
                                            </div>
                                            <p class="font-mono text-xs font-bold text-slate-700 dark:text-slate-300 mt-0.5">
                                                {{ v.plate_number }}
                                            </p>
                                        </div>

                                        <div
                                            class="h-6 w-6 rounded-full flex items-center justify-center border transition-colors shrink-0"
                                            :class="form.vehicle_id === v.id ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-300 dark:border-slate-600 text-transparent'"
                                        >
                                            <Check class="h-3.5 w-3.5" />
                                        </div>
                                    </div>

                                    <div class="mt-3 pt-2.5 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-between text-[11px] text-slate-500 dark:text-slate-400">
                                        <span>Kapasitas: <strong class="text-slate-800 dark:text-slate-200">{{ v.capacity }} Kursi</strong></span>
                                        <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                            <CheckCircle2 class="h-3 w-3" /> Siap Dipakai
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECTION B: Mobil Tidak Tersedia (Sedang Digunakan / Perawatan) -->
                        <div v-if="unavailableVehicles.length > 0" class="pt-2">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400 flex items-center gap-1.5 mb-2">
                                <Ban class="h-4 w-4 text-red-500" /> Mobil Sedang Digunakan / Tidak Dapat Dipilih:
                            </span>

                            <div class="space-y-2.5">
                                <div
                                    v-for="uv in unavailableVehicles"
                                    :key="uv.id"
                                    class="p-3.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/60 opacity-80 cursor-not-allowed select-none"
                                >
                                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h4 class="text-xs font-bold text-slate-700 dark:text-slate-300">
                                                    {{ uv.brand }} {{ uv.model }}
                                                </h4>
                                                <span class="font-mono text-[11px] text-slate-500 dark:text-slate-400">
                                                    ({{ uv.plate_number }})
                                                </span>
                                            </div>
                                        </div>

                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold self-start sm:self-auto bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-400 border border-red-200 dark:border-red-900">
                                            {{ uv.status_label }}
                                        </span>
                                    </div>

                                    <!-- Detail Bentrok: Siapa yang booking dan ke mana -->
                                    <div
                                        v-if="uv.conflict_booking"
                                        class="mt-2.5 bg-white dark:bg-slate-800 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700 text-[11px] space-y-1"
                                    >
                                        <div class="flex items-center gap-2 text-slate-800 dark:text-slate-200">
                                            <span class="text-slate-500 dark:text-slate-400 font-medium">Dibooking oleh:</span>
                                            <strong class="font-bold text-blue-600 dark:text-blue-400">{{ uv.conflict_booking.employee_name }}</strong>
                                            <span class="text-slate-400">({{ uv.conflict_booking.department_name }})</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-slate-800 dark:text-slate-200">
                                            <span class="text-slate-500 dark:text-slate-400 font-medium">Tujuan:</span>
                                            <span class="font-semibold">{{ uv.conflict_booking.destination }}</span>
                                        </div>
                                        <div class="text-[10px] text-slate-500 dark:text-slate-400 font-mono">
                                            Jadwal: {{ formatTimeRange(uv.conflict_booking.start_time, uv.conflict_booking.end_time) }}
                                        </div>
                                    </div>

                                    <div v-else-if="uv.reason === 'maintenance'" class="mt-2 text-[11px] text-amber-700 dark:text-amber-400 italic">
                                        Kendaraan sedang dalam jadwal servis berkala atau perbaikan teknis.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Rincian Perjalanan & Penumpang -->
                    <div class="space-y-4 pt-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-2">
                            <MapPin class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                            3. Rincian Perjalanan & Keperluan
                        </h3>

                        <!-- Destination -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Tujuan / Lokasi Perjalanan Dinas *
                            </label>
                            <input
                                v-model="form.destination"
                                type="text"
                                required
                                placeholder="Contoh: Plant Operations Batang, Factory Site / Rapat Klien Sudirman"
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            />
                            <p v-if="form.errors.destination" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.destination }}
                            </p>
                        </div>

                        <!-- Passenger Count -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Jumlah Penumpang (Orang) *
                            </label>
                            <input
                                v-model="form.passenger_count"
                                type="number"
                                min="1"
                                max="20"
                                required
                                class="w-full sm:w-48 rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-xs text-slate-900 dark:text-white focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            />
                            <p v-if="form.errors.passenger_count" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.passenger_count }}
                            </p>
                        </div>

                        <!-- Purpose -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1">
                                Keperluan Dinas & Justifikasi Operasional *
                            </label>
                            <textarea
                                v-model="form.purpose"
                                rows="3"
                                required
                                placeholder="Tuliskan tujuan bisnis resmi, agenda kerja, atau inspeksi yang akan dilakukan..."
                                class="w-full rounded-lg bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-600 p-2.5 text-xs text-slate-900 dark:text-white placeholder-slate-400 focus:ring-2 focus:ring-blue-600 focus:border-transparent outline-none transition-colors"
                            ></textarea>
                            <p v-if="form.errors.purpose" class="mt-1 text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                <ShieldAlert class="h-3.5 w-3.5" /> {{ form.errors.purpose }}
                            </p>
                        </div>
                    </div>

                    <!-- 4. Flexible PIC Approval Notice (No manual PIC selection required) -->
                    <div class="rounded-xl bg-blue-50/80 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 p-4 flex items-start gap-3 text-xs text-blue-900 dark:text-blue-200">
                        <ShieldCheck class="h-5 w-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" />
                        <div>
                            <span class="font-bold block text-sm">Persetujuan Terpusat (Tanpa Pilih PIC Manual)</span>
                            <p class="mt-0.5 text-[11px] text-blue-700 dark:text-blue-300 leading-relaxed">
                                Permohonan pemesanan ini akan langsung masuk ke antrean armada perusahaan. Seluruh PIC/Manager yang berwenang dapat langsung menyetujui pemesanan ini secara fleksibel dan cepat untuk mengantisipasi kebutuhan operasional mendesak.
                            </p>
                        </div>
                    </div>

                    <!-- Submit & Actions Footer -->
                    <div class="pt-4 border-t border-slate-200 dark:border-slate-700 flex flex-col sm:flex-row items-center justify-between gap-3">
                        <Link
                            href="/bookings"
                            class="w-full sm:w-auto px-4 py-2.5 rounded-lg border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition-colors text-center"
                        >
                            Batalkan
                        </Link>

                        <button
                            type="submit"
                            :disabled="form.processing || !form.vehicle_id || availableVehicles.length === 0"
                            class="w-full sm:w-auto px-6 py-2.5 rounded-lg bg-blue-600 hover:bg-blue-700 disabled:opacity-50 text-white text-xs font-bold transition-colors flex items-center justify-center gap-2 shadow-md shadow-blue-500/20"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            <span v-if="form.processing">Mengirim Permohonan...</span>
                            <span v-else>Kirim Permohonan Booking</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
