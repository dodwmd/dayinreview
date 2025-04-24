<script setup>
import { ref, onMounted, computed } from 'vue';
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
const currentVideo = ref(props.playlist.videos.length > 0 ? props.playlist.videos[0] : null);
const player = ref(null);
const isPlayerReady = ref(false);

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
const groupedVideos = computed(() => {
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
});

// Get YouTube video URL
const getYoutubeUrl = (videoId) => {
    return `https://www.youtube.com/watch?v=${videoId}`;
};

// Play video in the YouTube player
const playVideo = (video) => {
    currentVideo.value = video;
    
    if (player.value && isPlayerReady.value && video.youtube_id) {
        player.value.loadVideoById(video.youtube_id);
        
        // Mark as watched after a delay to ensure they've at least started watching
        setTimeout(() => {
            if (!watchedVideos.value.has(video.id)) {
                markAsWatched(video.id);
            }
        }, 5000);
    }
};

// Initialize YouTube Player API
const initYouTubePlayer = () => {
    if (!currentVideo.value) return;
    
    // Create YouTube player
    player.value = new YT.Player('youtube-player', {
        height: '360',
        width: '640',
        videoId: currentVideo.value.youtube_id,
        playerVars: {
            'autoplay': 1,
            'modestbranding': 1,
            'rel': 0
        },
        events: {
            'onReady': onPlayerReady,
            'onStateChange': onPlayerStateChange
        }
    });
};

// YouTube player event handlers
const onPlayerReady = (event) => {
    isPlayerReady.value = true;
    event.target.playVideo();
};

const onPlayerStateChange = (event) => {
    // Mark video as watched when it ends (state = 0)
    if (event.data === 0 && currentVideo.value) {
        if (!watchedVideos.value.has(currentVideo.value.id)) {
            markAsWatched(currentVideo.value.id);
        }
        
        // Play the next video if available
        const currentIndex = props.playlist.videos.findIndex(v => v.id === currentVideo.value.id);
        if (currentIndex < props.playlist.videos.length - 1) {
            playVideo(props.playlist.videos[currentIndex + 1]);
        }
    }
};

// Load YouTube API
onMounted(() => {
    // Only load if we have videos
    if (props.playlist.videos.length === 0) return;
    
    // Load YouTube IFrame API
    const tag = document.createElement('script');
    tag.src = 'https://www.youtube.com/iframe_api';
    const firstScriptTag = document.getElementsByTagName('script')[0];
    firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    
    // Initialize player when API is ready
    window.onYouTubeIframeAPIReady = initYouTubePlayer;
});

// Helper to check if a video is the current one
const isCurrentVideo = (videoId) => {
    return currentVideo.value && currentVideo.value.id === videoId;
};
</script>

<template>
    <Head :title="`Playlist - ${formatDate(playlist.date)}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ playlist.name }}
                </h2>
                <Link 
                    :href="route('playlists.index')" 
                    class="text-sm text-blue-600 hover:text-blue-800"
                >
                    Back to Playlists
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <!-- Main content area (player + current video info) -->
                    <div class="lg:col-span-2">
                        <!-- YouTube Player -->
                        <div v-if="playlist.videos.length > 0" class="mb-6 overflow-hidden rounded-lg bg-black shadow-lg">
                            <div id="youtube-player" class="aspect-video w-full"></div>
                        </div>

                        <!-- Current Video Info -->
                        <div v-if="currentVideo" class="mb-6 rounded-lg bg-white p-6 shadow-lg">
                            <h3 class="text-xl font-bold text-gray-900">{{ currentVideo.title }}</h3>
                            
                            <div class="mt-2 flex items-center space-x-2">
                                <span class="text-sm text-gray-600">{{ currentVideo.channel_title }}</span>
                                <span class="text-sm text-gray-400">•</span>
                                <span class="text-sm text-gray-600">{{ formatDuration(currentVideo.duration_seconds) }}</span>
                                
                                <span 
                                    v-if="watchedVideos.has(currentVideo.id)"
                                    class="ml-2 inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800"
                                >
                                    Watched
                                </span>
                            </div>
                            
                            <p class="mt-4 text-gray-700 text-sm whitespace-pre-line">{{ currentVideo.description }}</p>
                        </div>

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
                                    
                                    <div v-if="youtubeConnected">
                                        <a 
                                            :href="getYoutubeUrl(currentVideo?.youtube_id)" 
                                            target="_blank"
                                            class="inline-flex items-center rounded-md bg-red-50 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-100"
                                        >
                                            Open in YouTube
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Playlist sidebar -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-8">
                            <div class="rounded-lg bg-white shadow overflow-hidden">
                                <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                    <h3 class="text-base font-medium text-gray-900">Up Next</h3>
                                </div>

                                <!-- Video list -->
                                <div class="divide-y divide-gray-200 overflow-y-auto" style="max-height: 70vh;">
                                    <div 
                                        v-for="video in playlist.videos" 
                                        :key="video.id"
                                        :class="[
                                            'p-4 hover:bg-gray-50 cursor-pointer transition-colors',
                                            isCurrentVideo(video.id) ? 'bg-blue-50' : ''
                                        ]"
                                        @click="playVideo(video)"
                                    >
                                        <div class="flex">
                                            <div class="relative flex-shrink-0 mr-3">
                                                <img 
                                                    :src="video.thumbnail_url" 
                                                    :alt="video.title"
                                                    class="h-20 w-36 object-cover rounded"
                                                />
                                                <div 
                                                    v-if="watchedVideos.has(video.id)" 
                                                    class="absolute bottom-0 right-0 bg-black bg-opacity-70 px-2 py-0.5 text-white text-xs rounded-tl"
                                                >
                                                    Watched
                                                </div>
                                                <div class="absolute bottom-0 left-0 bg-black bg-opacity-70 px-2 py-0.5 text-white text-xs rounded-tr">
                                                    {{ formatDuration(video.duration_seconds) }}
                                                </div>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-900 line-clamp-2">
                                                    {{ video.title }}
                                                </p>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    {{ video.channel_title }}
                                                </p>
                                                <span 
                                                    :class="[
                                                        'mt-1 inline-block px-2 py-0.5 text-xs rounded-full',
                                                        video.pivot.source === 'trending' 
                                                            ? 'bg-orange-100 text-orange-800' 
                                                            : 'bg-indigo-100 text-indigo-800'
                                                    ]"
                                                >
                                                    {{ video.pivot.source === 'trending' ? 'Trending' : 'Subscription' }}
                                                </span>
                                            </div>
                                        </div>
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
