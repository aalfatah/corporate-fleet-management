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
        // Success (Completed/Approved/Available): bg-green-100 text-green-700 / dark:bg-green-900/30 dark:text-green-400
        case 'available':
            return { label: 'Available', classes: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' };
        case 'approved':
            return { label: 'Approved', classes: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' };
        case 'completed':
            return { label: 'Completed', classes: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' };

        // Warning (Pending/In Progress/In Use): bg-amber-100 text-amber-700 / dark:bg-amber-900/30 dark:text-amber-400
        case 'pending_approval':
            return { label: 'Pending Approval', classes: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' };
        case 'in_progress':
        case 'in_use':
            return { label: 'In Progress', classes: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' };

        // Info (Assigned): bg-blue-100 text-blue-700 / dark:bg-blue-900/30 dark:text-blue-400
        case 'assigned':
            return { label: 'Assigned', classes: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400' };

        // Danger (Rejected/Cancelled/Maintenance): bg-red-100 text-red-700 / dark:bg-red-900/30 dark:text-red-400
        case 'rejected':
            return { label: 'Rejected', classes: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
        case 'cancelled':
            return { label: 'Cancelled', classes: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };
        case 'maintenance':
            return { label: 'Maintenance', classes: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' };

        default:
            return { label: props.status, classes: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300' };
    }
});

const sizeClass = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'px-2 py-0.5 text-xs';
        case 'lg':
            return 'px-3 py-1 text-sm';
        default:
            return 'px-2.5 py-0.5 text-xs';
    }
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full font-semibold transition-colors"
        :class="[config.classes, sizeClass]"
    >
        {{ config.label }}
    </span>
</template>
