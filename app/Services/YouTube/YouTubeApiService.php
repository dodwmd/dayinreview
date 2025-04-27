<?php

namespace App\Services\YouTube;

/**
 * This is an alias class for compatibility.
 * The SubscriptionService is referencing YouTubeApiService, but we have YouTubeService.
 */
class_alias(YouTubeService::class, 'App\Services\YouTube\YouTubeApiService');
