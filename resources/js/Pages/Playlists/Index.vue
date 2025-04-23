<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    playlists: {
        type: Array,
        default: () => [],
    },
    youtubeConnected: {
        type: Boolean,
        default: false,
    },
});

const loading = ref(false);

const generatePlaylist = () => {
    loading.value = true;
    router.post(route('playlists.generate'), {}, {
        onFinish: () => {
            loading.value = false;
        }
    });
};

// Format date in a readable way
const formatDate = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};
</script>

<template>
    <Head title="My Playlists" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                My Playlists
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Generate Playlist Section -->
                <div class="mb-6 rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Generate Daily Playlist</h3>
                    
                    <div v-if="!youtubeConnected" class="mb-4 rounded-md bg-yellow-50 p-4">
                        <p class="text-yellow-700">
                            Connect your YouTube account to enhance your playlist experience and sync playlists with YouTube.
                        </p>
                        <div class="mt-3">
                            <Link 
                                :href="route('auth.google.connect')" 
                                class="inline-flex items-center rounded-md bg-yellow-100 px-3 py-2 text-sm font-medium text-yellow-800 hover:bg-yellow-200"
                            >
                                Connect YouTube Account
                            </Link>
                        </div>
                    </div>
                    
                    <div class="flex items-center space-x-4">
                        <PrimaryButton @click="generatePlaylist" :disabled="loading">
                            <span v-if="loading">Generating...</span>
                            <span v-else>Generate Today's Playlist</span>
                        </PrimaryButton>
                        <p class="text-sm text-gray-600">
                            Create a personalized playlist with trending content and videos from your subscriptions.
                        </p>
                    </div>
                </div>
                
                <!-- Playlists List -->
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-medium text-gray-900">Your Playlists</h3>
                    
                    <div v-if="playlists.length === 0" class="rounded-md bg-gray-50 p-4 text-center">
                        <p class="text-gray-600">
                            You don't have any playlists yet. Generate your first playlist above!
                        </p>
                    </div>
                    
                    <div v-else class="divide-y divide-gray-200">
                        <div 
                            v-for="playlist in playlists" 
                            :key="playlist.id"
                            class="flex items-center justify-between py-4"
                        >
                            <div>
                                <h4 class="font-medium">{{ formatDate(playlist.date) }}</h4>
                                <p class="text-sm text-gray-600">
                                    {{ playlist.videos ? playlist.videos.length : 0 }} videos
                                </p>
                                <div class="mt-1">
                                    <span 
                                        :class="[
                                            'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                            playlist.is_public ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                        ]"
                                    >
                                        {{ playlist.is_public ? 'Public' : 'Private' }}
                                    </span>
                                    <span 
                                        v-if="playlist.youtube_playlist_id"
                                        class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800"
                                    >
                                        YouTube
                                    </span>
                                </div>
                            </div>
                            <div>
                                <Link 
                                    :href="route('playlists.show', playlist.id)" 
                                    class="inline-flex items-center rounded-md bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 hover:bg-blue-100"
                                >
                                    View Playlist
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
