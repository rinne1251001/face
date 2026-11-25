{{-- 出席率のセル
     $cell      … ['rate' => 64, 'warning' => true]（AttendanceStats::row() の1マス。記録がなければ rate は null）
     $highlight … true なら、出席率が7割未満・前の月より10ポイント以上下がったセルの背景に色を付ける（省略すると true） --}}
<td @style(['background-color: var(--color-sub)' => ($highlight ?? true) && $cell['warning']])>{{ $cell['rate'] === null ? '—' : $cell['rate'].'%' }}</td>