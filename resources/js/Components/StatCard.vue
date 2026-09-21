<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    subtitle: {
        type: String,
        default: null,
    },
    color: {
        type: String,
        default: 'indigo', // indigo, emerald, amber, rose, sky
    },
});

const colorStyles = {
    indigo: {
        border: 'border-indigo-500/20 hover:border-indigo-500/40',
        glow: 'from-indigo-500/10 to-transparent',
        badge: 'text-indigo-400 bg-indigo-500/10',
    },
    emerald: {
        border: 'border-emerald-500/20 hover:border-emerald-500/40',
        glow: 'from-emerald-500/10 to-transparent',
        badge: 'text-emerald-400 bg-emerald-500/10',
    },
    amber: {
        border: 'border-amber-500/20 hover:border-amber-500/40',
        glow: 'from-amber-500/10 to-transparent',
        badge: 'text-amber-400 bg-amber-500/10',
    },
    rose: {
        border: 'border-rose-500/20 hover:border-rose-500/40',
        glow: 'from-rose-500/10 to-transparent',
        badge: 'text-rose-400 bg-rose-500/10',
    },
    sky: {
        border: 'border-sky-500/20 hover:border-sky-500/40',
        glow: 'from-sky-500/10 to-transparent',
        badge: 'text-sky-400 bg-sky-500/10',
    },
};
</script>

<template>
    <div
        class="relative overflow-hidden rounded-2xl bg-slate-900/90 border p-5 shadow-xl transition-all duration-300 hover:-translate-y-0.5"
        :class="colorStyles[color]?.border || colorStyles.indigo.border"
    >
        <!-- Background Gradient Glow -->
        <div
            class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full bg-gradient-to-br opacity-50 blur-2xl"
            :class="colorStyles[color]?.glow || colorStyles.indigo.glow"
        ></div>

        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-400">{{ title }}</p>
            <div
                v-if="$slots.icon"
                class="flex h-9 w-9 items-center justify-center rounded-xl border border-slate-800"
                :class="colorStyles[color]?.badge || colorStyles.indigo.badge"
            >
                <slot name="icon" />
            </div>
        </div>

        <div class="mt-3 flex items-baseline gap-2">
            <h3 class="text-3xl font-extrabold tracking-tight text-white">{{ value }}</h3>
            <span v-if="subtitle" class="text-xs text-slate-400 font-medium">{{ subtitle }}</span>
        </div>
    </div>
</template>
