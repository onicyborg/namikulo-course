@props(['rows', 'columns', 'cells', 'record', 'source', 'filters' => [], 'sortable' => '', 'orderColumn' => '', 'direction' => 'desc', 'statuses' => [], 'roles' => [], 'extraFilters' => [], 'cellData' => []])
@php($tableId = 'table-'.str_replace('.', '-', $cells))
<div class="nc-server-table nc-data-table-area" data-admin-table-area>
    <div class="nc-server-filters" data-admin-table-filters role="search">
        <div><label for="{{ $tableId }}-size">Tampilkan data</label><select id="{{ $tableId }}-size" name="per_page" class="form-control">@foreach([10,25,50,100] as $size)<option value="{{ $size }}" @selected((int)($filters['per_page'] ?? 10) === $size)>{{ $size }} data</option>@endforeach</select></div>
        <div class="nc-server-search"><label for="{{ $tableId }}-search">Cari</label><input id="{{ $tableId }}-search" name="q" type="search" maxlength="120" class="form-control" value="{{ $filters['q'] ?? '' }}" placeholder="Cari data…"></div>
        @foreach(['role' => ['Peran', $roles], 'status' => ['Status', $statuses]] + $extraFilters as $key => [$label, $options])
        @if($options)<div><label for="{{ $tableId }}-{{ $key }}">{{ $label }}</label><select id="{{ $tableId }}-{{ $key }}" name="{{ $key }}" class="form-control"><option value="">Semua {{ strtolower($label) }}</option>@foreach($options as $value => $text)<option value="{{ $value }}" @selected(($filters[$key] ?? '') === (string)$value)>{{ $text }}</option>@endforeach</select></div>@endif
        @endforeach
        <button type="button" class="nc-table-action" data-admin-table-reset data-action-tooltip="Reset filter" aria-label="Reset filter"><x-dashboard.icon name="rotate-ccw" /></button>
    </div>
    <noscript><p role="status">Aktifkan JavaScript untuk mencari dan berpindah halaman tabel.</p></noscript>
    <p role="status" data-admin-table-feedback hidden></p>
    <div class="nc-server-table-stage" data-server-table-stage>
        <div class="table-responsive" data-server-table-scroll><table id="{{ $tableId }}" class="table table-striped nc-admin-table nd-table" data-admin-data-table data-nc-source-url="{{ $source }}" data-nc-columns="{{ implode(',', array_keys($columns)) }}" data-nc-sortable="{{ $sortable }}" data-nc-order-column="{{ $orderColumn }}" data-nc-order-direction="{{ $direction }}" data-nc-display-start="{{ ($rows->currentPage()-1)*$rows->perPage() }}"><thead><tr>@foreach($columns as $label)<th scope="col">{{ $label }}</th>@endforeach</tr></thead><tbody>
    @foreach($rows as $row)<tr>@foreach($columns as $column => $label)<td>@include($cells, array_merge($cellData, [$record => $row, 'column' => $column]))</td>@endforeach</tr>@endforeach
        </tbody></table></div>
        <div class="nc-server-table-loader" data-server-table-loader role="status" aria-live="polite" aria-label="Memuat data tabel" hidden>
            <div class="nc-server-table-loader-card">
                <span class="nc-server-table-loader-mark" aria-hidden="true">
                    <x-dashboard.icon name="book-open" />
                    <span class="nc-server-table-loader-page"></span>
                </span>
                <span class="nc-server-table-loader-copy">Memuat data<span class="nc-server-table-loader-dots" aria-hidden="true"></span></span>
            </div>
        </div>
    </div>
    @include('admin.partials.data-table-pagination', ['paginator' => $rows])
</div>
