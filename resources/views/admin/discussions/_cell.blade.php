@switch($column)
    @case('lesson') @include('admin.discussions.cells.lesson') @break
    @case('author') @include('admin.discussions.cells.author') @break
    @case('body') @include('admin.discussions.cells.body') @break
    @case('status') @include('admin.discussions.cells.status') @break
    @case('actions') @include('admin.discussions.cells.actions') @break
@endswitch
