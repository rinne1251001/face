{{--
    並び替えできる見出し（th）。ほかの一覧でも使える
    使い方：@include('partials.sort-header', ['key' => 'name', 'label' => '名前'])
      key   … コントローラーの sortColumns() のキー
      label … 見出しの文字
    $sort と $direction（いまの並び順）は、コントローラーから渡したものがそのまま使われる
--}}
@php
    $isCurrent = $sort === $key;
    // いま並んでいる列をもう一度押したら逆順、別の列を押したら昇順から
    $nextDirection = $isCurrent && $direction === 'asc' ? 'desc' : 'asc';
    // 検索の条件は残したまま、並び順を変えて1ページ目に戻す（'page' => null でページ番号を消す）
    $url = request()->fullUrlWithQuery(['sort' => $key, 'direction' => $nextDirection, 'page' => null]);
@endphp
<th class="sortable" aria-sort="{{ $isCurrent ? ($direction === 'asc' ? 'ascending' : 'descending') : 'none' }}">
    <a href="{{ $url }}" id="sort-{{ $key }}" class="sort-link" data-ajax>
        {{ $label }}
        <span @class(['sort-arrow', 'active' => $isCurrent && $direction === 'asc'])>▲</span>
        <span @class(['sort-arrow', 'active' => $isCurrent && $direction === 'desc'])>▼</span>
    </a>
</th>