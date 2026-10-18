<strong>{{ $post->lesson?->title ?? 'Materi tidak tersedia' }}</strong>
<small class="d-block text-muted">{{ $post->lesson?->course?->title ?? 'Course tidak tersedia' }} · {{ $post->parent_id ? 'Balasan' : 'Pertanyaan' }}</small>
