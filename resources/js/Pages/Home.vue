<template>
  <div class="min-h-screen bg-gray-100">
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-3xl font-bold text-gray-900">Day in Review</h1>
      </div>
    </header>
    
    <main>
      <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
          <div class="border-4 border-dashed border-gray-200 rounded-lg p-6">
            <div class="text-center mb-8">
              <h2 class="text-2xl font-semibold mb-4">Your Daily Content Aggregator</h2>
              <p class="text-lg text-gray-600">
                Discover the best content from Reddit and YouTube in one place
              </p>
            </div>
            
            <div class="grid md:grid-cols-2 gap-8 mt-8">
              <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                  <svg class="w-6 h-6 mr-2 text-orange-500" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"></path>
                    <circle cx="12" cy="12" r="3" fill="white"></circle>
                  </svg>
                  Reddit Content
                </h3>
                <p class="text-gray-600 mb-4">
                  Subscribe to your favorite subreddits and get daily updates of the most popular posts.
                </p>
                <div class="mt-4">
                  <button class="px-4 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600 transition">
                    Explore Reddit Content
                  </button>
                </div>
              </div>
              
              <div class="bg-white p-6 rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-4 flex items-center">
                  <svg class="w-6 h-6 mr-2 text-red-600" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M19.615 3.184c-3.604-.246-11.631-.245-15.23 0-3.897.266-4.356 2.62-4.385 8.816.029 6.185.484 8.549 4.385 8.816 3.6.245 11.626.246 15.23 0 3.897-.266 4.356-2.62 4.385-8.816-.029-6.185-.484-8.549-4.385-8.816zm-10.615 12.816v-8l8 3.993-8 4.007z"></path>
                  </svg>
                  YouTube Videos
                </h3>
                <p class="text-gray-600 mb-4">
                  Follow your favorite YouTube channels and discover trending videos in personalized playlists.
                </p>
                <div class="mt-4">
                  <button class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                    Explore YouTube Content
                  </button>
                </div>
              </div>
            </div>
            
            <div class="mt-10 bg-white p-6 rounded-lg shadow-md">
              <h3 class="text-xl font-semibold mb-4">Personalized Playlists</h3>
              <p class="text-gray-600 mb-4">
                Create custom playlists or use auto-generated ones based on your interests and viewing history.
              </p>
              <div class="mt-4">
                <button class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                  Create Your Playlist
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
    
    <footer class="bg-white shadow mt-10 py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-gray-500">
          Day in Review &copy; {{ new Date().getFullYear() }}
        </p>
      </div>
    </footer>
  </div>
</template>

<script setup>
// Component logic can be added here
</script>

<style scoped>
/* Component-specific styles can be added here */
</style>
