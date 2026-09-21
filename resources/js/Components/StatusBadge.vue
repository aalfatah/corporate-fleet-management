<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: {
        type: String,
        required: true,
    },
    size: {
        type: String,
        default: 'md', // 'sm' | 'md' | 'lg'
    },
});

const config = computed(() => {
    switch (props.status) {
        // Green
        case 'available':
            return { label: 'Available', bg: 'bg-emerald-500/15', text: 'text-emerald-400', border: 'border-emerald-500/30', dot: 'bg-emerald-400' };
        case 'completed':
            return { label: 'Completed', bg: 'bg-emerald-500/15', text: 'text-emerald-400', border: 'border-emerald-500/30', dot: 'bg-emerald-400' };

        // Yellow
        case 'pending_approval':
            return { label: 'Pending Approval', bg: 'bg-amber-500/15', text: 'text-amber-400', border: 'border-amber-500/30', dot: 'bg-amber-400' };

        // Blue / Indigo
        case 'approved':
            return { label: 'Approved', bg: 'bg-blue-500/15', text: 'text-blue-400', border: 'border-blue-500/30', dot: 'bg-blue-400' };
        case 'assigned':
            return { label: 'Assigned', bg: 'bg-indigo-500/15', text: 'text-indigo-400', border: 'border-indigo-500/30', dot: 'bg-indigo-400' };
        case 'in_progress':
        case 'in_use':
            return { label: 'In Progress', bg: 'bg-sky-500/15', text: 'text-sky-400', border: 'border-sky-500/30', dot: 'bg-sky-400' };

        // Red
        case 'rejected':
            return { label: 'Rejected', bg: 'bg-rose-500/15', text: 'text-rose-400', border: 'border-rose-500/30', dot: 'bg-rose-400' };
        case 'cancelled':
            return { label: 'Cancelled', bg: 'bg-slate-500/15', text: 'text-slate-400', border: 'border-slate-500/30', dot: 'bg-slate-400' };
        case 'maintenance':
            return { label: 'Maintenance', bg: 'bg-rose-500/15', text: 'text-rose-400', border: 'border-rose-500/30', dot: 'bg-rose-400' };

        default:
            return { label: props.status, bg: 'bg-slate-700/50', text: 'text-slate-300', border: 'border-slate-600/40', dot: 'bg-slate-400' };
    }
});

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'px-2 py-0.5 text-xs';
        case 'lg':
            return 'px-3 py-1.5 text-sm font-semibold';
        default:
            return 'px-2.5 py-1 text-xs font-medium';
    }
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1.5 rounded-full border backdrop-blur-sm transition-all"
        :class="[config.bg, config.text, config.border, sizeClass]"
    >
        <span class="h-1.5 w-1.5 rounded-full animate-pulse" :class="config.dot"></span>
        <span>{{ config.label }}</span>
    </span>
</template>
