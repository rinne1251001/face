{{-- 出席の状態を色付きで表示する
     $attendance      … 出席記録（記録がなければ null）
     $absentIfMissing … true なら、記録がないことを「欠席」と表示する（受付が終わった授業で、顔認証をしなかった生徒）
                        省略したとき・false のときは「未記録」 --}}
@if ($attendance)
    <span class="badge badge-{{ $attendance->status }}">{{ $attendance->statusLabel() }}</span>
@elseif ($absentIfMissing ?? false)
    <span class="badge badge-absent">欠席</span><span class="muted">（顔認証なし）</span>
@else
    <span class="badge badge-none">未記録</span>
@endif