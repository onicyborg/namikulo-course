<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ServerTable
{
    public function modalRecords(Builder|Relation $query, mixed $id): Collection
    {
        return is_string($id) && Str::isUuid($id) ? $query->whereKey($id)->get() : collect();
    }

    public function response(Request $request, Builder|Relation $query, array $columns, string $view,
        string $record, array $orderable, string $defaultOrder, string $defaultDirection = 'desc',
        ?int $total = null, array $viewData = []): JsonResponse
    {
        $request->validate([
            'draw' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'start' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'length' => ['required', 'integer', 'in:10,25,50,100'],
            'order' => ['sometimes', 'array', 'max:1'],
            'order.0.column' => ['sometimes', 'integer', 'min:0'],
            'order.0.dir' => ['sometimes', 'in:asc,desc'],
        ]);
        $sort = $orderable[(int) $request->input('order.0.column', -1)] ?? $defaultOrder;
        $direction = $request->input('order.0.dir', $defaultDirection);
        // Column names and search expressions come from the server allow-list only.
        $request->merge(['columns' => array_map(fn ($name) => [
            'data' => $name, 'name' => '', 'searchable' => false, 'orderable' => false,
            'search' => ['value' => '', 'regex' => false],
        ], $columns), 'search' => ['value' => '', 'regex' => false], 'searchPanes' => []]);
        $builder = $query instanceof Relation ? $query->getQuery() : $query;
        $key = $builder->getModel()->getQualifiedKeyName();
        $table = DataTables::eloquent($builder)->filter(fn () => null, false)
            ->order(function ($query) use ($sort, $direction, $key): void {
                $query->reorder();
                if (is_callable($sort)) $sort($query, $direction);
                else $query->orderBy($sort, $direction);
                $query->orderBy($key);
            });
        if ($total !== null) {
            $table->setTotalRecords($total)->setFilteredRecords((clone $builder)->reorder()->count());
        }
        foreach ($columns as $column) {
            $table->addColumn($column, fn ($row) => view($view, $viewData + [$record => $row, 'column' => $column])->render());
        }
        return $table->only($columns)->rawColumns($columns)->toJson()
            ->header('Cache-Control', 'private, no-store');
    }
}
