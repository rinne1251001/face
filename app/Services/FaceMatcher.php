<?php

namespace App\Services;

use App\Models\FaceSample;
use App\Models\Student;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

/**
 * ベクトルの正規化、コサイン類似度計算、キャッシュ制御
 */
class FaceMatcher
{
    /**
     * 受け取った配列を検証し、長さ1に正規化した float 配列を返す
     * 明るい部屋は数値が大きくなり暗い部屋では小さくなるので、1に揃えることで比較しやすくする
     * 1に揃えるとコサイン類似度（2つのベクトルの方向の近さを表す指標）をかけ算で求められるようになる
     * $raw（顔の特徴を入れたデータ配列）が正しい配列か検証するためにmixedというデータ型で受け取っている
     */
    public static function toUnitVector(mixed $raw, string $field = 'embedding'): array
    {
        // 配列でない、連想配列（リスト形式でない）、次元数（要素数）が設定範囲外なら終了
        $count = is_array($raw) ? count($raw) : 0;
        if (! is_array($raw) || ! array_is_list($raw) || $count < config('face.min_dimension') || $count > config('face.max_dimension')) {
            throw ValidationException::withMessages([$field => '特徴量の形式が正しくありません。']);
        }

        // すべての要素の二乗和を求める
        $sum = 0.0;
        foreach ($raw as $x) {
            if (! is_int($x) && ! is_float($x)) {
                throw ValidationException::withMessages([$field => '特徴量に数値以外が含まれています。']);
            }
            $sum += $x * $x;
        }
        if ($sum <= 0.0 || ! is_finite($sum)) {
            throw ValidationException::withMessages([$field => '特徴量が不正です。']);
        }

        // ルートをかぶせて実際のベクトルの長さに戻す
        $norm = sqrt($sum);

        // 特徴ベクトルを1にする（例：[3, 4]→[0.6, 0.8]のように同じ比率で三平方の定理で計算すると1になる）
        return array_map(fn ($x) => $x / $norm, $raw);
    }

    /**
     * 画面に返す生徒の情報（特徴量などは含めない）
     * 照合テストと出席受付の両方で使う
     */
    public static function summary(?Student $student): ?array
    {
        if (! $student) {
            return null;
        }

        return [
            'id' => $student->id,
            'name' => $student->name,
            'student_number' => $student->student_number,
            'class_name' => $student->schoolClass?->name,
        ];
    }

    /**
     * 登録データと照合し、最も近い人物と判定結果を返す
     * 正規化済み同士なので、ドット積 = コサイン類似度
     */
    public function match(array $unitQuery, string $modelVersion): array
    {
        /**
         * キャッシュに保存した登録データと同じ精度（32bit）に揃えるため、照合する側も32bitに変換する
         *
         * ①64bitのデータを32bitの圧縮されたデータに変換
         * ②unpack()でPHPの配列に復元してPHPが計算で扱える数値配列に戻す
         * ③その数値配列の要素数を取得する
         *
         * なお、キーは1始まりになる
         */
        $q = unpack('g*', pack('g*', ...$unitQuery));
        $dim = count($q);
        $scores = [];

        /**
         * 全員分と照合してスコアを$scores配列に代入
         * indexメソッドに人物ID => 登録されたバイナリベクトル（２進数のデータの並び）の配列を取得し人物ごとにループ
         */
        foreach ($this->index($modelVersion) as $studentId => $vectors) {
            $best = null;
            foreach ($vectors as $packed) {
                $v = unpack('g*', $packed);
                if (count($v) !== $dim) {
                    continue;
                }
                $s = 0.0;
                for ($i = 1; $i <= $dim; $i++) {
                    $s += $q[$i] * $v[$i];
                }
                $best = max($best ?? $s, $s); // 人物ごとに最も近いサンプルを採用
            }
            if ($best !== null) {
                $scores[$studentId] = $best;
            }
        }

        // 降順に並び替えて上位３つのスコアと人物IDを取得
        arsort($scores);
        $top = array_slice($scores, 0, 3, true);
        $ids = array_keys($top);

        // 1位の人物ID＆スコアを取得 or 該当者がいなければnull
        $bestId = $ids[0] ?? null;
        $bestScore = $bestId !== null ? $top[$bestId] : null;

        // 1位と2位のスコアの差を計算
        $margin = isset($ids[1]) ? $bestScore - $top[$ids[1]] : null;

        // 1位のスコアが閾値(config/face.phpに明記)以上＆1位と2位の人物の類似度の差が閾値以上、2位が存在しないならtrue
        $accepted = $bestScore !== null
            && $bestScore >= config('face.threshold')
            && ($margin === null || $margin >= config('face.margin'));

        // 一回のクエリで3人分取得しidをキーにしたコレクションにする
        $students = Student::with('schoolClass')->whereIn('id', $ids)->get()->keyBy('id');

        // 顔照合の結果を取りまとめた連想配列（レスポンスデータ）
        return [
            // 本人かどうか（true/false）
            'accepted' => $accepted,
            // 1位のStudentオブジェクト
            'student' => $accepted ? $students->get($bestId) : null,
            // 1位の人物ID
            'best_student_id' => $bestId,
            // 1位のスコア
            'similarity' => $bestScore,
            // 1位と2位のスコア差
            'margin' => $margin,
            // 3位までの候補者情報の配列
            'candidates' => array_map(
                fn ($id) => ['student' => $students->get($id), 'similarity' => $top[$id]],
                $ids,
            ),
        ];
    }

    /**
     * 照合用データ（人物ID => float32バイナリの配列）
     * バイナリを含むので file キャッシュに保存する
     */
    public function index(string $modelVersion): array
    {
        // データベースに保存されている顔データをfileに永久キャッシュする
        return Cache::store('file')->rememberForever($this->cacheKey($modelVersion), function () use ($modelVersion) {
            $index = [];
            // 人物の顔データを500件ずつ取得
            FaceSample::query()
                ->select(['id', 'student_id', 'embedding'])
                ->where('model_version', $modelVersion)
                ->whereHas('student', fn ($q) => $q->where('is_active', true))
                ->lazyById(500)
                ->each(function (FaceSample $sample) use (&$index) {
                    $index[$sample->student_id][] = pack('g*', ...$sample->embedding);
                });

            return $index;
        });
    }

    /**
     * 顔写真の追加・削除や人物の有効/無効化が更新された際、古い照合インデックスキャッシュを削除して最新状態に更新する
     * 登録・削除・人物の更新のあとに呼ぶ
     */
    public function flush(): void
    {
        Cache::store('file')->forget($this->cacheKey(config('face.model_version')));
    }

    // モデルバージョンごとのキャッシュ用キー文字列を生成
    private function cacheKey(string $modelVersion): string
    {
        return 'face_index:'.$modelVersion;
    }
}
