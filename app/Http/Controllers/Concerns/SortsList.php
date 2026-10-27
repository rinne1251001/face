<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

// 一覧の並び替え（URL の ?sort=キー&direction=asc|desc を読んで orderBy する）
// 使い方：コントローラーで use SortsList; と書いて、$this->applySort(...) を呼ぶ
trait SortsList
{
    /**
     * @param  array<string, mixed>  $columns  並び替えできる列（キー => 列名 または サブクエリ）
     * @return array{0: string, 1: string}  [実際に使った列のキー, 'asc' か 'desc']
     */
    protected function applySort(Builder $query, Request $request, array $columns, string $default): array
    {
        // $columns にあるキーしか使わない（URL に好きな列名を書かれても大丈夫なように）
        $sort = $request->query('sort');
        if (! is_string($sort) || ! array_key_exists($sort, $columns)) {
            $sort = $default;
        }
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        $query->orderBy($columns[$sort], $direction);

        return [$sort, $direction];
    }
}