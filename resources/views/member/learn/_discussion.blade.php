<section class="learn-discussion" id="discussion" aria-labelledby="discussion-title">
    <div class="discussion-heading"><div><h2 id="discussion-title">Diskusi materi</h2><p>Ajukan pertanyaan atau bantu menjawab pertanyaan member lain.</p></div><span>{{ $discussionThreads->total() }} pertanyaan</span></div>
    <x-flash-message />
    @forelse($discussionThreads as $thread)
        <div class="discussion-thread">
            @include('member.learn._discussion-post', ['post' => $thread, 'root' => $thread, 'isReply' => false])
            @foreach($thread->replies as $reply)
                @include('member.learn._discussion-post', ['post' => $reply, 'root' => $thread, 'isReply' => true])
            @endforeach
            <div class="discussion-thread-footer">
                @if($thread->visible_replies_count > 3)<a href="{{ route('learn.discussion.thread', [$lesson, $thread]) }}" data-discussion-dialog>Lihat semua {{ $thread->visible_replies_count }} balasan</a>@endif
                <a href="{{ route('learn.discussion.thread', [$lesson, $thread]) }}?reply_to={{ $thread->id }}#reply-form" data-discussion-dialog>Tulis balasan</a>
            </div>
        </div>
    @empty
        <p class="discussion-empty">Belum ada pertanyaan. Mulai percakapan tentang materi ini.</p>
    @endforelse
    @if($discussionThreads->hasPages())<div class="discussion-pagination">{{ $discussionThreads->links('pagination::bootstrap-4') }}</div>@endif

    <form method="POST" action="{{ route('learn.discussion.store', $lesson) }}" class="discussion-form" id="question-form">
        @csrf
        <h3>Ajukan pertanyaan</h3>
        <div class="form-group">
            <label for="question-body">Pertanyaan Anda</label>
            <x-discussion-editor id="question-body" :lesson-id="$lesson->id" :value="old('parent_id') ? '' : old('body', '')" :invalid="$errors->has('body') && !old('parent_id')" help="question-help" />
            <small id="question-help" class="form-text">10–2.000 karakter. Tulis pertanyaan tentang materi ini.</small>
            @if($errors->has('body') && !old('parent_id'))<div id="question-error" class="invalid-feedback">{{ $errors->first('body') }}</div>@endif
        </div>
        <button class="btn btn-primary" type="submit">Kirim pertanyaan</button>
    </form>
</section>
