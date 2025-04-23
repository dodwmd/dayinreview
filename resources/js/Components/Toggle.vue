<script setup>
import { computed } from 'vue';

const props = defineProps({
    checked: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:checked', 'change']);

const toggle = () => {
    if (props.disabled) return;
    emit('update:checked', !props.checked);
    emit('change', !props.checked);
};

const toggleClasses = computed(() => {
    return [
        'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-600 focus:ring-offset-2',
        props.checked ? 'bg-blue-600' : 'bg-gray-200',
        props.disabled ? 'cursor-not-allowed opacity-70' : '',
    ];
});

const knobClasses = computed(() => {
    return [
        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
        props.checked ? 'translate-x-5' : 'translate-x-0',
    ];
});
</script>

<template>
    <button
        type="button"
        :class="toggleClasses"
        :disabled="disabled"
        @click="toggle"
        role="switch"
        :aria-checked="checked"
    >
        <span 
            aria-hidden="true" 
            :class="knobClasses"
        ></span>
    </button>
</template>
