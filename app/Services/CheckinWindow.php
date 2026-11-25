<?php

namespace App\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

// ある日・ある時限の、顔認証の受付時間と、出席・遅刻の判定（時刻は config/school.php）
//   授業開始の30分前 〜 開始時刻   … 出席（例：1限なら 8:30 〜 9:00）
//   開始時刻を過ぎてから 〜 終了時刻 … 遅刻（例：9:01 〜 9:50）
//   それより前・後                 … 受け付けない
// 時刻は「分」で比べる（9:00:40 は 9:00 として出席にする。時計の表示と合わせるため）
// 顔認証をしなかった生徒は、受付が終わった（hasClosed）時点で欠席として扱う
// 公欠と、受付時間の外の出欠は、教師が出席一覧から手動で登録する
//
// 使い方：$window = CheckinWindow::for(today(), 1); $window->statusAt(now()); // 'present' / 'late' / null
final class CheckinWindow
{
    private function __construct(
        public readonly Carbon $opensAt,  // 受付開始（この分から出席）
        public readonly Carbon $startsAt, // 授業開始（この分までは出席、次の分から遅刻）
        public readonly Carbon $closesAt, // 授業終了（この分まで遅刻として受け付ける）
    ) {}

    public static function for(CarbonInterface $date, int $period): self
    {
        $times = config("school.periods.{$period}")
            ?? throw new \LogicException("{$period}限の時刻が config/school.php にありません。");

        $startsAt = Carbon::parse($date->toDateString().' '.$times['start']);

        return new self(
            $startsAt->copy()->subMinutes(config()->integer('school.checkin.opens_before', 30)),
            $startsAt,
            Carbon::parse($date->toDateString().' '.$times['end']),
        );
    }

    // その時刻に顔認証したときの状態：'present'（出席）か 'late'（遅刻）。受付時間の外なら null
    public function statusAt(CarbonInterface $time): ?string
    {
        $minute = $this->minute($time);

        return match (true) {
            $minute->lt($this->opensAt) => null,      // 受付前
            $minute->lte($this->startsAt) => 'present',
            $minute->lte($this->closesAt) => 'late',
            default => null,                          // 受付終了後
        };
    }

    // 受付が始まったか
    public function hasOpened(CarbonInterface $time): bool
    {
        return $this->minute($time)->gte($this->opensAt);
    }

    // 受付が終わったか（終わったあとは、顔認証をしなかった生徒を欠席として扱う）
    public function hasClosed(CarbonInterface $time): bool
    {
        return $this->minute($time)->gt($this->closesAt);
    }

    // 画面表示用：「8:30〜9:00 は出席、9:01〜9:50 は遅刻」
    public function label(): string
    {
        return $this->opensAt->format('G:i').'〜'.$this->startsAt->format('G:i').' は出席、'
            .$this->startsAt->copy()->addMinute()->format('G:i').'〜'.$this->closesAt->format('G:i').' は遅刻';
    }

    // 秒を切り捨てた時刻（元の時刻は変えない）
    private function minute(CarbonInterface $time): Carbon
    {
        return Carbon::instance($time)->copy()->startOfMinute();
    }
}