<span>{{ $post->user?->name ?? 'Pengguna' }}</span>
<small class="d-block text-muted"><time datetime="{{ $post->created_at->copy()->utc()->toIso8601String() }}" data-local-datetime>{{ $post->created_at->copy()->utc()->format('d M Y H:i') }} UTC</time></small>
