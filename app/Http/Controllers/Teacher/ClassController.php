<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Concerns\UsesAcademicYear;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Services\CheckinWindow;
use App\Services\SchoolCalendar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

// 受け持ちクラスの出席状況（その日の授業ごと）。担任も、表から出欠を変更できる
class ClassController extends Controller
{
    use UsesAcademicYear; // ensureEditable() / ensureDateEditable()（読み取り専用の年度は変更させない）

    // 表の選択肢の順（未記録の「none」は先頭に別で出す）
    private const STATUS_ORDER = ['present', 'absent', 'excused', 'late'];

    public function index(Request $request): View
    {
        $teacher = $request->user('teacher');
        // 今年度の受け持ちクラスだけ
        $classes = $teacher->schoolClasses()
            ->where('school_classes.academic_year_id', AcademicYear::currentOrNewest()?->id)
            ->withCount('students')
            ->orderBy('name')
            ->get();

        return view('teacher.classes.index', compact('classes'));
    }

    public function show(Request $request, SchoolClass $schoolClass, SchoolCalendar $calendar): View
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->hasSchoolClass($schoolClass), 403, 'このクラスの受け持ちではありません。');

        $request->validate(['date' => ['nullable', 'date_format:Y-m-d']]);
        $date = Carbon::parse($request->query('date', today()->toDateString()));
        $dayOfWeek = $date->dayOfWeekIso;

        // 表の行（クラスの生徒）・授業・列（授業 × 時限）
        [$students, $subjects, $columns] = $this->tableFor($schoolClass, $date);

        // 「生徒ID-授業ID-時限」 => 出席記録
        $attendances = Attendance::query()
            ->where('date', $date->toDateString())
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->get()
            ->keyBy(fn ($a) => "{$a->student_id}-{$a->subject_id}-{$a->period}");

        // 記録がない生徒を「欠席」と表示するコマ（「授業ID-時限」の一覧）
        //   受付が終わっていて、出欠の記録が（このクラス以外の生徒も含めて）1件以上あるコマ
        //   記録が1件もないコマは、受付をしていないとみなして「未記録」のまま（出席一覧・分析と同じ）
        $closedLessons = Attendance::query()
            ->where('date', $date->toDateString())
            ->whereIn('subject_id', $subjects->pluck('id'))
            ->distinct()
            ->get(['subject_id', 'period'])
            ->filter(fn ($a) => CheckinWindow::for($date, (int) $a->period)->hasClosed(now()))
            ->map(fn ($a) => "{$a->subject_id}-{$a->period}")
            ->values()
            ->all();

        // 出欠を変更できる日か（未来の日と、読み取り専用の年度（クラスの年度・その日の年度）は変更できない）
        $lockedYear = collect([$schoolClass->academicYear, AcademicYear::containing($date)])
            ->first(fn (?AcademicYear $year) => $year !== null && ! $year->isEditable());
        $lockReason = match (true) {
            $date->isAfter(today()) => '未来の日付の出欠は、まだ変更できません。',
            $lockedYear !== null => "{$lockedYear->label}は読み取り専用のため、出欠を変更できません。",
            default => null,
        };

        return view('teacher.classes.show', [
            'schoolClass' => $schoolClass,
            'date' => $date,
            'lockReason' => $lockReason, // null なら変更できる
            // 表の選択肢（値 => 表示）。未記録（none）の表示は、コマごとに画面で決める
            'statusOptions' => array_combine(self::STATUS_ORDER, array_map(fn ($key) => Attendance::STATUSES[$key], self::STATUS_ORDER)),
            'dayLabel' => Subject::DAYS[$dayOfWeek],
            'dayNote' => $calendar->dayNote($date),
            'students' => $students,
            'subjects' => $subjects,
            'columns' => $columns, // ← これがなかったので $columns が未定義になっていた
            'attendances' => $attendances,
            'closedLessons' => $closedLessons,
            // 自分の担当授業なら、列の見出しから授業の画面へ移動できるようにする
            'mySubjectIds' => array_map('intval', $teacher->subjects()->pluck('subjects.id')->all()),
        ]);
    }

    // 担任が表から出欠を変更する（変更したセルだけが送られてくる）
    //   statuses[生徒ID][授業ID][時限] = present / absent / excused / late、または none（記録を消して未記録に戻す）
    public function update(Request $request, SchoolClass $schoolClass): RedirectResponse
    {
        $teacher = $request->user('teacher');
        abort_unless($teacher->hasSchoolClass($schoolClass), 403, 'このクラスの受け持ちではありません。');

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => ['array'],
            'statuses.*.*' => ['array'],
            'statuses.*.*.*' => ['required', Rule::in([...array_keys(Attendance::STATUSES), 'none'])],
        ], [
            'date.before_or_equal' => '未来の日付の出欠は、まだ変更できません。',
            'statuses.*.*.*.in' => '出欠の選び方が正しくありません。',
        ]);
        $date = Carbon::parse($data['date']);
        // 読み取り専用の年度の出欠は変更させない（クラスの年度と、その日の年度の両方）
        $this->ensureEditable($schoolClass->academicYear);
        $this->ensureDateEditable($date);

        // 変更してよいセル：この日の表に出ているもの（このクラスの生徒 × その生徒が受講する授業 × このクラスの時限）
        [$students, , $columns] = $this->tableFor($schoolClass, $date);
        $allowed = [];
        foreach ($students as $student) {
            foreach ($columns as ['subject' => $subject, 'period' => $period]) {
                if ($student->subjects->contains('id', $subject->id)) {
                    $allowed["{$student->id}-{$subject->id}-{$period}"] = true;
                }
            }
        }

        $changes = [];
        foreach ($data['statuses'] ?? [] as $studentId => $bySubject) {
            foreach ($bySubject as $subjectId => $byPeriod) {
                foreach ($byPeriod as $period => $status) {
                    if (! isset($allowed["{$studentId}-{$subjectId}-{$period}"])) {
                        throw ValidationException::withMessages([
                            'statuses' => 'この日の表にない授業・時限が含まれていたため、反映しませんでした。表が変わっています。確認してから、もう一度反映してください。',
                        ]);
                    }
                    $changes[] = [(int) $studentId, (int) $subjectId, (int) $period, (string) $status];
                }
            }
        }

        // 今の記録と違うものだけを書き換える（同じなら何もしない。顔認証の記録もそのまま残る）
        $count = DB::transaction(function () use ($changes, $date, $teacher) {
            $count = 0;
            foreach ($changes as [$studentId, $subjectId, $period, $status]) {
                $keys = ['student_id' => $studentId, 'subject_id' => $subjectId, 'date' => $date->toDateString(), 'period' => $period];
                $current = Attendance::where($keys)->first();

                if ($status === 'none') {
                    if ($current !== null) {
                        $current->delete();
                        $count++;
                    }
                } elseif ($current?->status !== $status) {
                    // 担当教師の手動登録と同じく「手動」・変更した教師を記録する（照合の記録 match_log_id は残る）
                    Attendance::updateOrCreate($keys, ['status' => $status, 'method' => 'manual', 'teacher_id' => $teacher->id]);
                    $count++;
                }
            }

            return $count;
        });

        return redirect()
            ->route('teacher.classes.show', ['schoolClass' => $schoolClass, 'date' => $date->toDateString()])
            ->with('status', $count > 0 ? "出欠を{$count}件変更しました。" : '変更はありませんでした。');
    }

    // 表の行・授業・列（画面の表示と、変更してよいセルの確認の両方で使う）
    //   students … クラスの生徒（出席番号の順）。それぞれが受講している授業（subjects）も読み込む
    //   subjects … このクラスの誰かが受講していて、その日に授業があるもの
    //   columns  … 「授業 × 時限」（3〜5限の授業なら3列）。時限の順
    /** @return array{0: Collection<int, Student>, 1: Collection<int, Subject>, 2: Collection<int, array{subject: Subject, period: int}>} */
    private function tableFor(SchoolClass $schoolClass, Carbon $date): array
    {
        // クラスの生徒と、それぞれがその曜日に受講している授業
        $students = $schoolClass->students()
            ->with('subjects.schedules')
            ->orderByRaw(Enrollment::ATTEND_NUMBER_AS_NUMBER) // 出席番号を数値に変換して並べる（9 → 11 の順）
            ->orderBy('attend_number')                        // 同じ数値（9 と 09 など）は文字の順
            ->get();

        // このクラスの誰かが受講している、その曜日の授業
        // （flatMap->subjects と同じ意味。こう書くと、中身が Subject だと PHPStan に伝わる）
        $subjects = $students->flatMap(fn (Student $student) => $student->subjects)
            ->unique('id')
            ->filter(fn ($subject) => $subject->periodsOn($date) !== []) // その日に授業があるものに絞る
            ->values();

        // 表の列：「授業 × 時限」（3〜5限の授業なら3列）。時限の順に並べる
        // （クラスの授業変更で、このクラスが受けない時限は出さない）
        $columns = $subjects
            ->flatMap(fn ($subject) => collect($subject->periodsOn($date))
            ->filter(fn ($period) => in_array($schoolClass->id, $subject->classIdsOn($date, $period), true))
            ->map(fn ($period) => ['subject' => $subject, 'period' => $period]))
            // 時限の順 → 同じ時限なら授業名の順（2つ目は2つの列を比べる関数。[...] で囲まずにそのまま渡す）
            ->sortBy([['period', 'asc'], fn (array $a, array $b) => $a['subject']->name <=> $b['subject']->name])
            ->values();

        return [$students, $subjects, $columns];
    }
}