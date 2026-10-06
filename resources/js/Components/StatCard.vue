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

const iconStyles = {
    blue: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30',
    green: 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30',
    amber: 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30',
    red: 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30',
    slate: 'text-slate-600 dark:text-slate-400 bg-slate-100 dark:bg-slate-800',
    indigo: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30',
    emerald: 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/30',
    rose: 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/30',
    sky: 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30',
};
</script>

<template>
    <div class="bg-white dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 p-5 transition-colors">
        <div class="flex items-center justify-between">
            <p class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ title }}</p>
            <div
                v-if="$slots.icon"
                class="flex h-9 w-9 items-center justify-center rounded-lg"
                :class="iconStyles[color] || iconStyles.blue"
            >
                <slot name="icon" />
            </div>
        </div>

        <div class="mt-3 flex items-baseline gap-2">
            <h3 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ value }}</h3>
            <span v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400 font-normal">{{ subtitle }}</span>
        </div>
    </div>
</template>
