<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Get schema of youtube_videos table
$youtube_videos_columns = Schema::getColumnListing('youtube_videos');
var_dump($youtube_videos_columns);

// Get schema of playlists table
$playlists_columns = Schema::getColumnListing('playlists');
var_dump($playlists_columns);
