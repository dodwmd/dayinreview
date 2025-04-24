<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const props = defineProps({
    recentPlaylists: {
        type: Array,
        default: () => [],
    },
    subscriptionStats: {
        type: Object,
        default: () => ({
            youtube: 0,
            reddit: 0,
        }),
    },
    youtubeConnected: {
        type: Boolean,
        default: false,
    }
});

const totalSubscriptions = computed(() => {
    return props.subscriptionStats.youtube + props.subscriptionStats.reddit;
});
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Welcome card -->
                <div class="mb-6 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h2 class="mb-4 text-2xl font-bold text-gray-800">Welcome to Day in Review</h2>
                        <p class="mb-4 text-gray-600">
                            Your personalized content hub for YouTube and Reddit content.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-3">
                            <Link :href="route('playlists.generate')" method="post" as="button">
                                <PrimaryButton>
                                    Generate Today's Playlist
                                </PrimaryButton>
                            </Link>
                            <Link :href="route('subscriptions.index')">
                                <PrimaryButton v-if="totalSubscriptions === 0" class="bg-indigo-600 hover:bg-indigo-700">
                                    Add Subscriptions
                                </PrimaryButton>
                            </Link>
                            <Link :href="route('profile.edit')" v-if="!youtubeConnected">
                                <PrimaryButton class="bg-red-600 hover:bg-red-700">
                                    Connect YouTube Account
                                </PrimaryButton>
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Stats cards -->
                <div class="mb-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Playlists card -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-700">Your Playlists</h3>
                                <Link :href="route('playlists.index')" class="text-sm text-indigo-600 hover:text-indigo-800">
                                    View All
                                </Link>
                            </div>
                            <div class="text-3xl font-bold text-gray-800">{{ recentPlaylists.length }}</div>
                            <p class="text-sm text-gray-500">Recent playlists</p>
                        </div>
                    </div>

                    <!-- YouTube card -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-700">YouTube Subscriptions</h3>
                                <Link :href="route('subscriptions.index', { filter: 'youtube' })" class="text-sm text-indigo-600 hover:text-indigo-800">
                                    Manage
                                </Link>
                            </div>
                            <div class="text-3xl font-bold text-gray-800">{{ subscriptionStats.youtube }}</div>
                            <p class="text-sm text-gray-500">Channels followed</p>
                            <div v-if="!youtubeConnected" class="mt-2">
                                <Link :href="route('profile.edit')">
                                    <span class="text-sm text-red-600 hover:text-red-800">
                                        Connect your YouTube account
                                    </span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Reddit card -->
                    <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="mb-2 flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-700">Reddit Subscriptions</h3>
                                <Link :href="route('subscriptions.index', { filter: 'reddit' })" class="text-sm text-indigo-600 hover:text-indigo-800">
                                    Manage
                                </Link>
                            </div>
                            <div class="text-3xl font-bold text-gray-800">{{ subscriptionStats.reddit }}</div>
                            <p class="text-sm text-gray-500">Subreddits followed</p>
                        </div>
                    </div>
                </div>

                <!-- Recent playlists -->
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="mb-4 flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-700">Recent Playlists</h3>
                            <Link :href="route('playlists.index')" class="text-sm text-indigo-600 hover:text-indigo-800">
                                View All Playlists
                            </Link>
                        </div>
                        
                        <div v-if="recentPlaylists.length > 0" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <Link 
                                v-for="playlist in recentPlaylists" 
                                :key="playlist.id" 
                                :href="route('playlists.show', playlist.id)"
                                class="group overflow-hidden rounded-lg border border-gray-200 transition-shadow hover:shadow-md"
                            >
                                <div class="aspect-video w-full overflow-hidden bg-gray-100">
                                    <img 
                                        v-if="playlist.thumbnail_url" 
                                        :src="playlist.thumbnail_url" 
                                        :alt="playlist.name"
                                        class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
                                    >
                                    <div v-else class="flex h-full items-center justify-center bg-gray-200">
                                        <span class="text-gray-500">No thumbnail</span>
                                    </div>
                                </div>
                                <div class="p-4">
                                    <h4 class="mb-1 text-sm font-medium text-gray-900 line-clamp-1">{{ playlist.name }}</h4>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500">
                                            {{ playlist.video_count }} videos
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ new Date(playlist.created_at).toLocaleDateString() }}
                                        </span>
                                    </div>
                                </div>
                            </Link>
                        </div>
                        
                        <div v-else class="rounded-lg border border-dashed border-gray-300 p-6 text-center">
                            <p class="mb-4 text-gray-500">You don't have any playlists yet</p>
                            <Link :href="route('playlists.generate')" method="post" as="button">
                                <PrimaryButton>
                                    Generate Your First Playlist
                                </PrimaryButton>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
