@forelse ($logs as $log)
    <tr>
        <td>{{ $log->student_name ?? '（なし）' }}</td>
        <td class="{{ $log->accepted ? 'ok' : 'error' }}">{{ $log->accepted ? '本人と判定' : '不合格' }}</td>
        <td>{{ $log->similarity === null ? '-' : number_format($log->similarity, 3) }}</td>
        <td>{{ $log->margin === null ? '-' : number_format($log->margin, 3) }}</td>
        <td>{{ $log->created_at->format('m/d H:i:s') }}</td>
    </tr>
@empty
    <tr><td colspan="5">まだ照合ログがありません。</td></tr>
@endforelse