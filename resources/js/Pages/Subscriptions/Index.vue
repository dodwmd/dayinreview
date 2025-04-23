<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
    subscriptions: {
        type: Array,
        default: () => [],
    },
    filter: {
        type: String,
        default: 'all',
    },
    youtubeConnected: {
        type: Boolean,
        default: false,
    },
    redditConnected: {
        type: Boolean,
        default: false,
    },
});

const channelId = ref('');
const channelIdError = ref('');

const syncYouTubeSubscriptions = () => {
    router.post(route('subscriptions.youtube.sync'));
};

const addYouTubeChannel = () => {
    // Basic validation
    if (!channelId.value.trim()) {
        channelIdError.value = 'YouTube channel ID is required';
        return;
    }
    
    channelIdError.value = '';
    router.post(route('subscriptions.youtube.store'), {
        channel_id: channelId.value,
    });
    
    // Clear input after submission
    channelId.value = '';
};

const unsubscribe = (subscriptionId) => {
    if (confirm('Are you sure you want to unsubscribe?')) {
        router.delete(route('subscriptions.destroy', subscriptionId));
    }
};

const toggleFavorite = (subscriptionId) => {
    router.post(route('subscriptions.toggle-favorite', subscriptionId));
};
</script>

<template>
    <Head title="Subscriptions" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Subscriptions
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Subscription filters -->
                <div class="mb-6 flex space-x-4">
                    <a 
                        :href="route('subscriptions.index')" 
                        :class="{'font-bold underline': filter === 'all'}"
                        class="text-blue-600 hover:text-blue-800"
                    >
                        All
                    </a>
                    <a 
                        :href="route('subscriptions.index', { filter: 'youtube' })" 
                        :class="{'font-bold underline': filter === 'youtube'}"
                        class="text-blue-600 hover:text-blue-800"
                    >
                        YouTube
                    </a>
                    <a 
                        :href="route('subscriptions.index', { filter: 'reddit' })" 
                        :class="{'font-bold underline': filter === 'reddit'}"
                        class="text-blue-600 hover:text-blue-800"
                    >
                        Reddit
                    </a>
                </div>

                <!-- Connection and actions -->
                <div class="mb-6 bg-white p-6 shadow sm:rounded-lg">
                    <div v-if="filter === 'all' || filter === 'youtube'" class="mb-6">
                        <h3 class="mb-2 text-lg font-medium">YouTube Integration</h3>
                        <div v-if="!youtubeConnected" class="mb-4 rounded-md bg-yellow-50 p-4">
                            <p class="text-yellow-700">
                                Connect your YouTube account to manage your subscriptions.
                            </p>
                            <div class="mt-3">
                                <a 
                                    :href="route('auth.google.connect')" 
                                    class="inline-flex items-center rounded-md bg-yellow-100 px-3 py-2 text-sm font-medium text-yellow-800 hover:bg-yellow-200"
                                >
                                    Connect YouTube Account
                                </a>
                            </div>
                        </div>
                        <div v-else>
                            <div class="mb-4 flex items-center">
                                <PrimaryButton @click="syncYouTubeSubscriptions" class="mr-4">
                                    Sync YouTube Subscriptions
                                </PrimaryButton>
                                <span class="text-sm text-gray-600">
                                    Import your existing YouTube subscriptions
                                </span>
                            </div>
                            <div class="mt-6">
                                <InputLabel for="channel_id" value="Add YouTube Channel" />
                                <div class="mt-1 flex">
                                    <TextInput
                                        id="channel_id"
                                        v-model="channelId"
                                        type="text"
                                        class="mt-1 block w-full sm:w-1/2"
                                        placeholder="YouTube Channel ID"
                                    />
                                    <PrimaryButton @click="addYouTubeChannel" class="ml-4 mt-1">
                                        Add
                                    </PrimaryButton>
                                </div>
                                <InputError :message="channelIdError" class="mt-2" />
                                <p class="mt-2 text-sm text-gray-600">
                                    You can find the channel ID in the URL of a YouTube channel page.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div v-if="filter === 'all' || filter === 'reddit'" class="mb-6">
                        <h3 class="mb-2 text-lg font-medium">Reddit Integration</h3>
                        <p class="text-gray-600">
                            Reddit subscription management coming soon.
                        </p>
                    </div>
                </div>

                <!-- Subscription list -->
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="mb-4 text-lg font-medium">
                            <span v-if="filter === 'all'">All Subscriptions</span>
                            <span v-else-if="filter === 'youtube'">YouTube Subscriptions</span>
                            <span v-else>Reddit Subscriptions</span>
                        </h3>

                        <div v-if="subscriptions.length === 0" class="rounded-md bg-gray-50 p-4 text-center">
                            <p class="text-gray-600">
                                No subscriptions found.
                            </p>
                        </div>

                        <div v-else class="mt-6 space-y-4">
                            <div 
                                v-for="subscription in subscriptions" 
                                :key="subscription.id"
                                class="flex items-center justify-between rounded-md border border-gray-200 p-4 hover:bg-gray-50"
                            >
                                <div class="flex items-center">
                                    <img 
                                        v-if="subscription.thumbnail_url" 
                                        :src="subscription.thumbnail_url" 
                                        :alt="subscription.name"
                                        class="mr-4 h-12 w-12 rounded-full object-cover"
                                    />
                                    <div v-else class="mr-4 flex h-12 w-12 items-center justify-center rounded-full bg-gray-200">
                                        <span class="text-lg font-bold text-gray-500">
                                            {{ subscription.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="font-medium">{{ subscription.name }}</h4>
                                        <p class="text-sm text-gray-600">
                                            {{ subscription.subscribable_type }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    <button 
                                        @click="toggleFavorite(subscription.id)" 
                                        class="rounded-full p-2 hover:bg-gray-200"
                                        :title="subscription.is_favorite ? 'Remove from favorites' : 'Add to favorites'"
                                    >
                                        <span v-if="subscription.is_favorite" class="text-yellow-500">★</span>
                                        <span v-else class="text-gray-400">☆</span>
                                    </button>
                                    <DangerButton @click="unsubscribe(subscription.id)">
                                        Unsubscribe
                                    </DangerButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
