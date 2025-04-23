<script setup>
import { router } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    youtubeConnected: {
        type: Boolean,
        required: true,
    },
    redditConnected: {
        type: Boolean,
        required: true,
    },
    status: {
        type: String,
    },
});

const connectYouTube = () => {
    window.location.href = route('auth.google.connect');
};

const disconnectYouTube = () => {
    router.delete(route('auth.google.disconnect'));
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                External Accounts
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                Connect your YouTube and Reddit accounts to enhance your Day in Review experience.
            </p>
        </header>

        <div class="mt-6 space-y-6">
            <!-- YouTube Connection -->
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-medium text-gray-900">YouTube</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ youtubeConnected ? 'Connected' : 'Not connected' }}
                    </p>
                </div>
                <div>
                    <PrimaryButton v-if="!youtubeConnected" @click="connectYouTube">
                        Connect
                    </PrimaryButton>
                    <DangerButton v-else @click="disconnectYouTube">
                        Disconnect
                    </DangerButton>
                </div>
            </div>

            <!-- Reddit Connection - Placeholder for future implementation -->
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-medium text-gray-900">Reddit</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        {{ redditConnected ? 'Connected' : 'Not connected' }}
                    </p>
                </div>
                <div>
                    <PrimaryButton v-if="!redditConnected" disabled>
                        Connect (Coming Soon)
                    </PrimaryButton>
                    <DangerButton v-else disabled>
                        Disconnect (Coming Soon)
                    </DangerButton>
                </div>
            </div>

            <div class="mt-4">
                <ActionMessage :on="status === 'youtube-connected'">
                    YouTube account successfully connected.
                </ActionMessage>
                <ActionMessage :on="status === 'youtube-disconnected'">
                    YouTube account successfully disconnected.
                </ActionMessage>
            </div>
        </div>
    </section>
</template>
