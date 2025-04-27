<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlaylistItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaylistItemController extends Controller
{
    /**
     * Toggle the watched status of a playlist item.
     *
     * @return JsonResponse
     */
    public function __invoke(Request $request, string $id)
    {
        $item = PlaylistItem::findOrFail($id);
        $item->is_watched = ! $item->is_watched;
        $item->save();

        return response()->json([
            'success' => true,
            'watched' => $item->is_watched,
            'message' => $item->is_watched ? 'Video marked as watched' : 'Video marked as unwatched',
        ]);
    }
}
