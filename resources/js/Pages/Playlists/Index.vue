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
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    My Playlists
                </h2>
                <PrimaryButton @click="generatePlaylist" :disabled="loading" class="py-2">
                    <span v-if="loading">Generating...</span>
                    <span v-else>Generate New Playlist</span>
                </PrimaryButton>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Generate Playlist Section -->
                <div class="mb-6 rounded-lg bg-white p-6 shadow">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Daily Playlist Generator</h3>
                        <span class="text-sm text-gray-500">Create a personalized mix of content</span>
                    </div>
                    
                    <div v-if="!youtubeConnected" class="mb-4 mt-4 rounded-md bg-yellow-50 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm text-yellow-700">
                                    Connect your YouTube account to enhance your playlist experience and sync playlists with YouTube.
                                </p>
                                <div class="mt-3">
                                    <Link 
                                        :href="route('profile.edit')" 
                                        class="inline-flex items-center rounded-md bg-yellow-100 px-3 py-2 text-sm font-medium text-yellow-800 hover:bg-yellow-200"
                                    >
                                        Connect YouTube Account
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 flex items-center space-x-4">
                        <div class="text-sm text-gray-600">
                            <p class="mb-2">Your daily playlist includes:</p>
                            <ul class="list-inside list-disc space-y-1">
                                <li>Trending videos from your favorite subreddits</li>
                                <li>Recent uploads from your YouTube subscriptions</li>
                                <li>Personalized content based on your viewing habits</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Playlists List -->
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900">Your Playlists</h3>
                    </div>
                    
                    <div v-if="playlists.length === 0" class="p-6 text-center">
                        <div class="mx-auto w-24 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                            </svg>
                        </div>
                        <p class="mt-4 text-gray-600">
                            You don't have any playlists yet. Generate your first playlist!
                        </p>
                        <div class="mt-6">
                            <PrimaryButton @click="generatePlaylist" :disabled="loading">
                                <span v-if="loading">Generating...</span>
                                <span v-else>Generate Your First Playlist</span>
                            </PrimaryButton>
                        </div>
                    </div>
                    
                    <div v-else class="grid gap-6 p-6 sm:grid-cols-2 lg:grid-cols-3">
                        <div 
                            v-for="playlist in playlists" 
                            :key="playlist.id"
                            class="group overflow-hidden rounded-lg border border-gray-200 transition-all hover:shadow-md"
                        >
                            <!-- Playlist Thumbnail -->
                            <div class="aspect-video w-full overflow-hidden bg-gray-100">
                                <img 
                                    v-if="playlist.thumbnail_url" 
                                    :src="playlist.thumbnail_url" 
                                    :alt="playlist.name"
                                    class="h-full w-full object-cover transition-all duration-200 group-hover:scale-105"
                                >
                                <div v-else class="flex h-full items-center justify-center bg-gray-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </div>
                            </div>
                            
                            <!-- Playlist Info -->
                            <div class="p-4">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <h4 class="font-medium line-clamp-1">{{ playlist.name }}</h4>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ playlist.video_count }} videos
                                        </p>
                                    </div>
                                    <div>
                                        <span 
                                            :class="[
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                                                playlist.is_public ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                                            ]"
                                        >
                                            {{ playlist.is_public ? 'Public' : 'Private' }}
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mt-4 text-right">
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
        </div>
    </AuthenticatedLayout>
</template>
