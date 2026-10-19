@switch($column)
    @case('number') @include('admin.orders.cells.number') @break
    @case('member') @include('admin.orders.cells.member') @break
    @case('course') @include('admin.orders.cells.course') @break
    @case('amount') @include('admin.orders.cells.amount') @break
    @case('status') @include('admin.orders.cells.status') @break
    @case('attempts') @include('admin.orders.cells.attempts') @break
    @case('created') @include('admin.orders.cells.created') @break
    @case('actions') @include('admin.orders.cells.actions') @break
@endswitch
