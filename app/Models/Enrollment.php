<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// 在籍（1人の生徒 × 1つの年度 で1件）
// その年度の学年・クラス・出席番号を持つ。クラスと出席番号は、決まるまで null のまま
class Enrollment extends Model
{
    // 出席番号を数値に変換する SQL（並べ替えに使う。例：orderByRaw(Enrollment::ATTEND_NUMBER_AS_NUMBER)）
    //   出席番号の列は文字列なので、そのまま並べると「1, 10, 11, 2, 9」のように文字の順になってしまう
    //   UNSIGNED INTEGER は MySQL・MariaDB・SQLite のどれでも使える書き方（MySQL は CAST(… AS INTEGER) が使えない）
    //   数字でない出席番号（A1 など）は 0 になり、先頭に並ぶ
    public const ATTEND_NUMBER_AS_NUMBER = 'CAST(enrollments.attend_number AS UNSIGNED INTEGER)';

    // 年度末の結果（DBに入る値 => 画面に出す名前）。年度更新をするまでは null
    public const RESULTS = [
        'promoted' => '進級',
        'repeated' => '留年',
        'graduated' => '卒業',
        'withdrawn' => '退学',
    ];

    protected $fillable = ['student_id', 'academic_year_id', 'grade', 'school_class_id', 'attend_number', 'result'];

    protected function casts(): array
    {
        return [
            'student_id' => 'integer',
            'academic_year_id' => 'integer',
            'grade' => 'integer',
            'school_class_id' => 'integer', // 未定（null）のときは null のまま
        ];
    }

    /** @return BelongsTo<Student, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    // クラス（未定なら null）
    /** @return BelongsTo<SchoolClass, $this> */
    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    // 次の年度にどうなるかを決める
    //   $grade  … 今の学年
    //   $years  … 学科の修業年限（この学年を終えたら卒業）
    //   $action … 個別調整（'repeat' ＝ 留年、'withdraw' ＝ 退学、null ＝ 調整なし）
    //   $repeatGrade … 留年のときの、次の年度の学年（指定がなければ今の学年のまま）
    // 返すもの：[年度末の結果, 次の年度の学年]（次の年度に在籍しないときは、学年が null）
    /** @return array{0: string, 1: int|null} */
    public static function decideNext(int $grade, int $years, ?string $action = null, ?int $repeatGrade = null): array
    {
        return match (true) {
            $action === 'withdraw' => ['withdrawn', null],
            // 留年：学科の修業年限より上の学年にはしない
            $action === 'repeat' => ['repeated', max(1, min($repeatGrade ?? $grade, $years))],
            // 最後の学年を終えたら卒業（学科ごとに違う）
            $grade >= $years => ['graduated', null],
            default => ['promoted', $grade + 1],
        };
    }
}