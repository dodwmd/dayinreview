<template>
    <div>
        <transition
            leave-active-class="transition ease-in duration-300"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <p
                v-show="on"
                class="text-sm text-gray-600"
            >
                <slot />
            </p>
        </transition>
    </div>
</template>

<script setup>
defineProps({
    on: {
        type: Boolean,
        default: false,
    },
});
</script>
