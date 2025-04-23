<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Toggle from '@/Components/Toggle.vue';

const props = defineProps({
    playlist: {
        type: Object,
        required: true,
    },
    youtubeConnected: {
        type: Boolean,
        default: false,
    },
});

const isPublic = ref(props.playlist.is_public);
const watchedVideos = ref(new Set(props.playlist.videos
    .filter(video => video.pivot.watched)
    .map(video => video.id)));

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

// Format duration in minutes and seconds
const formatDuration = (seconds) => {
    if (!seconds) return '';
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
};

// Toggle playlist visibility
const toggleVisibility = () => {
    isPublic.value = !isPublic.value;
    router.patch(route('playlists.update-visibility', props.playlist.id), {
        is_public: isPublic.value
    });
};

// Mark video as watched
const markAsWatched = (videoId) => {
    if (watchedVideos.value.has(videoId)) return;
    
    watchedVideos.value.add(videoId);
    router.post(route('playlists.mark-watched', {
        id: props.playlist.id,
        videoId: videoId
    }));
};

// Group videos by source (trending vs subscription)
const groupedVideos = () => {
    const trending = [];
    const subscriptions = [];
    
    props.playlist.videos.forEach(video => {
        if (video.pivot.source === 'trending') {
            trending.push(video);
        } else {
            subscriptions.push(video);
        }
    });
    
    return { trending, subscriptions };
};

// Get YouTube video URL
const getYoutubeUrl = (videoId) => {
    return `https://www.youtube.com/watch?v=${videoId}`;
};

// Group videos by source
const { trending, subscriptions } = groupedVideos();
</script>

<template>
    <Head :title="`Playlist - ${formatDate(playlist.date)}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ formatDate(playlist.date) }}
                </h2>
                <Link 
                    :href="route('playlists.index')" 
                    class="text-sm text-blue-600 hover:text-blue-800"
                >
                    Back to Playlists
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Playlist Controls -->
                <div class="mb-6 rounded-lg bg-white p-6 shadow">
                    <div class="flex flex-col space-y-4 sm:flex-row sm:items-center sm:justify-between sm:space-y-0">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">Playlist Controls</h3>
                            <p class="text-sm text-gray-600">
                                {{ playlist.videos ? playlist.videos.length : 0 }} videos | 
                                {{ watchedVideos.size }} watched
                            </p>
                        </div>
                        
                        <div class="flex items-center space-x-4">
                            <div class="flex items-center">
                                <span class="mr-2 text-sm text-gray-600">Private</span>
                                <Toggle v-model:checked="isPublic" @change="toggleVisibility" />
                                <span class="ml-2 text-sm text-gray-600">Public</span>
                            </div>
                            
                            <div v-if="youtubeConnected && playlist.youtube_playlist_id">
                                <a 
                                    :href="`https://www.youtube.com/playlist?list=${playlist.youtube_playlist_id}`" 
                                    target="_blank"
                                    class="inline-flex items-center rounded-md bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100"
                                >
                                    Open in YouTube
                                </a>
                            </div>
                            
                            <div v-else-if="youtubeConnected && !playlist.youtube_playlist_id">
                                <span class="text-sm text-gray-600">YouTube sync available in settings</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Playlist Content -->
                <div class="space-y-6">
                    <!-- Subscription Videos Section -->
                    <div v-if="subscriptions.length > 0" class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">From Your Subscriptions</h3>
                        
                        <div class="divide-y divide-gray-200">
                            <div 
                                v-for="video in subscriptions" 
                                :key="video.id"
                                class="flex flex-col py-4 sm:flex-row sm:items-center"
                            >
                                <div class="mr-4 flex-shrink-0">
                                    <a :href="getYoutubeUrl(video.youtube_id)" target="_blank">
                                        <img 
                                            :src="video.thumbnail_url" 
                                            :alt="video.title"
                                            class="h-24 w-40 rounded-md object-cover"
                                        />
                                    </a>
                                </div>
                                
                                <div class="flex flex-1 flex-col mt-2 sm:mt-0">
                                    <a 
                                        :href="getYoutubeUrl(video.youtube_id)" 
                                        target="_blank"
                                        class="text-base font-medium text-gray-900 hover:text-blue-600"
                                    >
                                        {{ video.title }}
                                    </a>
                                    
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ video.channel_title }} | {{ formatDuration(video.duration_seconds) }}
                                    </p>
                                    
                                    <div class="mt-2 flex items-center space-x-2">
                                        <button 
                                            v-if="!watchedVideos.has(video.id)"
                                            @click="markAsWatched(video.id)"
                                            class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200"
                                        >
                                            Mark Watched
                                        </button>
                                        <span 
                                            v-else
                                            class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700"
                                        >
                                            Watched
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Trending Videos Section -->
                    <div v-if="trending.length > 0" class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-4 text-lg font-medium text-gray-900">Trending Videos</h3>
                        
                        <div class="divide-y divide-gray-200">
                            <div 
                                v-for="video in trending" 
                                :key="video.id"
                                class="flex flex-col py-4 sm:flex-row sm:items-center"
                            >
                                <div class="mr-4 flex-shrink-0">
                                    <a :href="getYoutubeUrl(video.youtube_id)" target="_blank">
                                        <img 
                                            :src="video.thumbnail_url" 
                                            :alt="video.title"
                                            class="h-24 w-40 rounded-md object-cover"
                                        />
                                    </a>
                                </div>
                                
                                <div class="flex flex-1 flex-col mt-2 sm:mt-0">
                                    <a 
                                        :href="getYoutubeUrl(video.youtube_id)" 
                                        target="_blank"
                                        class="text-base font-medium text-gray-900 hover:text-blue-600"
                                    >
                                        {{ video.title }}
                                    </a>
                                    
                                    <p class="mt-1 text-sm text-gray-600">
                                        {{ video.channel_title }} | {{ formatDuration(video.duration_seconds) }}
                                    </p>
                                    
                                    <div class="mt-2 flex items-center space-x-2">
                                        <button 
                                            v-if="!watchedVideos.has(video.id)"
                                            @click="markAsWatched(video.id)"
                                            class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-200"
                                        >
                                            Mark Watched
                                        </button>
                                        <span 
                                            v-else
                                            class="inline-flex items-center rounded-md bg-green-100 px-2 py-1 text-xs font-medium text-green-700"
                                        >
                                            Watched
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
