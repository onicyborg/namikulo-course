<div class="discussion-thread-dialog-content" data-thread-url="{{ route('learn.discussion.thread', [$lesson, $root]) }}">
    <div class="discussion-thread">
        @include('member.learn._discussion-post', ['post' => $root, 'root' => $root, 'isReply' => false])
        @foreach($replies as $reply)
            @include('member.learn._discussion-post', ['post' => $reply, 'root' => $root, 'isReply' => true])
        @endforeach
    </div>
    @if($replies->hasPages())<div class="discussion-pagination">{{ $replies->appends(['dialog' => !empty($inDialog) ? 1 : null])->links('pagination::bootstrap-4') }}</div>@endif
    @if($root->status === \App\Enums\DiscussionPostStatus::Visible)
        <form method="POST" action="{{ route('learn.discussion.store', $lesson) }}" class="discussion-form" id="{{ !empty($inDialog) ? 'dialog-' : '' }}reply-form" data-discussion-reply-form>
            @csrf
            <input type="hidden" name="parent_id" value="{{ $root->id }}">
            <input type="hidden" name="reply_to_id" value="{{ $replyTo?->id ?? $root->id }}">
            <h2>Tulis balasan</h2>
            @include('member.learn._reply-quote', ['quoted' => $replyTo ?? $root])
            <div class="form-group">
                <label for="{{ !empty($inDialog) ? 'dialog-' : '' }}reply-body">Balasan Anda</label>
                <x-discussion-editor :id="!empty($inDialog) ? 'dialog-reply-body' : 'reply-body'" :lesson-id="$lesson->id" :value="!empty($inDialog) ? '' : old('body', '')" :invalid="empty($inDialog) && $errors->has('body')" :help="!empty($inDialog) ? 'dialog-reply-help' : 'reply-help'" />
                <small id="{{ !empty($inDialog) ? 'dialog-' : '' }}reply-help" class="form-text">10–2.000 karakter. Balasan dapat dibaca semua member yang memiliki akses.</small>
                @if(empty($inDialog))
                    @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @error('parent_id')<div class="text-danger">{{ $message }}</div>@enderror
                    @error('reply_to_id')<div class="text-danger">{{ $message }}</div>@enderror
                @endif
            </div>
            <p class="discussion-dialog-feedback" role="status" aria-live="polite" data-discussion-feedback hidden></p>
            <button class="btn btn-primary" type="submit">Kirim balasan</button>
        </form>
    @else
        <p class="discussion-empty">Pertanyaan ini disembunyikan. Pulihkan sebelum menambah balasan.</p>
    @endif
</div>
