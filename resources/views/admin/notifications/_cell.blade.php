@switch($column)
    @case('title') @include('admin.notifications.cells.title') @break
    @case('summary') @include('admin.notifications.cells.summary') @break
    @case('created') @include('admin.notifications.cells.created') @break
    @case('status') @include('admin.notifications.cells.status') @break
    @case('actions')
    @php($target = ($notificationService ?? app(\App\Services\NotificationService::class))->target($item, $viewer ?? auth()->user())) @include('admin.notifications.cells.actions') @break
@endswitch
