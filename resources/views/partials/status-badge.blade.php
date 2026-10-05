{{-- 出席の状態を色付きで表示する。$attendance が null なら「未記録」 --}}
<span class="badge badge-{{ $attendance?->status ?? 'none' }}">{{ $attendance?->statusLabel() ?? '未記録' }}</span>
