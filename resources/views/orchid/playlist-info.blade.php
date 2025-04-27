<div class="bg-white rounded shadow-sm p-4 mb-4">
    <div class="row">
        <div class="col-md-8">
            <h4>{{ $playlist->title }}</h4>
            @if($playlist->description)
                <p class="text-muted">{{ $playlist->description }}</p>
            @endif
            
            <div class="d-flex align-items-center mb-2">
                <span class="badge {{ $playlist->type === 'auto' ? 'bg-info' : 'bg-primary' }} me-2">
                    {{ $playlist->type === 'auto' ? 'Auto-Generated' : 'Custom' }}
                </span>
                
                @if($playlist->is_public)
                    <span class="badge bg-success me-2">Public</span>
                @else
                    <span class="badge bg-secondary me-2">Private</span>
                @endif
                
                @if($playlist->categories->count() > 0)
                    @foreach($playlist->categories as $category)
                        <span class="badge bg-secondary me-1">{{ $category->name }}</span>
                    @endforeach
                @endif
            </div>
            
            <div class="text-muted small">
                <strong>Owner:</strong> {{ $playlist->user ? $playlist->user->name : 'System' }} |
                <strong>Created:</strong> {{ $playlist->created_at->format('Y-m-d') }} |
                <strong>Updated:</strong> {{ $playlist->updated_at->format('Y-m-d') }}
            </div>
        </div>
        
        <div class="col-md-4 text-end">
            <div class="mb-2">
                <span class="h3">{{ $items->total() }}</span>
                <span class="text-muted">videos</span>
            </div>
            
            <div>
                @php
                    $watchedCount = $items->filter(function($item) {
                        return $item->watched;
                    })->count();
                    
                    $totalCount = $items->count();
                    $percentage = $totalCount > 0 ? round(($watchedCount / $totalCount) * 100) : 0;
                @endphp
                
                <div class="progress" style="height: 8px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $percentage }}%;" 
                        aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="text-muted small mt-1">
                    {{ $watchedCount }} of {{ $totalCount }} watched ({{ $percentage }}%)
                </div>
            </div>
        </div>
    </div>
</div>
