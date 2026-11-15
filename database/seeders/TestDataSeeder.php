<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Database\Factories\AcademicYearFactory;
use Database\Factories\ClassroomFactory;
use Database\Factories\DepartmentFactory;
use Database\Factories\EnrollmentFactory;
use Database\Factories\SchoolClassFactory;
use Database\Factories\StudentFactory;
use Database\Factories\SubjectFactory;
use Database\Factories\Support\JapaneseName;
use Database\Factories\TeacherFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

// たくさんのテストデータ（3年度分・生徒500人・教師50人・授業56×3年度・今年度の過去6週間の出席記録）
// 実行：php artisan migrate:fresh --seeder=TestDataSeeder
// ログイン：管理者 admin ／ 教師 teacher・t002〜t050（パスワードはすべて 12345678）
class TestDataSeeder extends Seeder
{
    private const YEARS = [2024, 2025, 2026];

    private const STUDENT_TOTAL = 500;   // 卒業・退学した生徒も含めた人数

    private const TEACHER_TOTAL = 50;

    private const RETIRED_TEACHERS = 3;  // 最後の3人は前の年度で退職（今年度はログイン不可・担当なし）

    private const WITHDRAW_RATE = 3;     // 年度末に退学する割合（%）

    private const REPEAT_RATE = 2;       // 年度末に留年する割合（%）

    private const UNDECIDED = 3;         // 最後の年度の新入生のうち、クラス・出席番号が未定の人数

    private const MAX_SUBJECTS = 4;      // 1人の教師が1年度に担当する授業の上限

    // 学科 => [何年で卒業するか, 学籍番号に入れる学科の番号]
    private const DEPARTMENTS = [
        'Tライセンス科' => [1, 1],
        '情報処理科' => [2, 2],
        'Web技術科' => [2, 3],
        'ビジネス科' => [2, 4],
        '先端ITシステム科' => [3, 5],
        '情報セキュリティ学科' => [4, 6],
        '実践AI科' => [4, 7],
    ];

    // クラス => [ホームルームの教室, その学年でこのクラスに入る学科]（学年はクラス名の最初の数字）
    private const CLASSES = [
        '1T1' => ['501教室', ['Tライセンス科']],
        '1T2' => ['502教室', ['情報処理科', 'Web技術科']],
        '1T3' => ['503教室', ['先端ITシステム科']],
        '1T4' => ['504教室', ['情報セキュリティ学科', '実践AI科']],
        '1B1' => ['505教室', ['ビジネス科']],
        '2T1' => ['601教室', ['情報処理科', 'Web技術科']],
        '2T2' => ['602教室', ['先端ITシステム科']],
        '2T3' => ['603教室', ['情報セキュリティ学科', '実践AI科']],
        '2B1' => ['604教室', ['ビジネス科']],
        '3T1' => ['701教室', ['先端ITシステム科']],
        '3T2' => ['702教室', ['情報セキュリティ学科']],
        '3T3' => ['402教室', ['実践AI科']],
        '4T1' => ['401教室', ['情報セキュリティ学科']],
        '4T2' => ['403教室', ['実践AI科']],
    ];

    // クラスの教室のほかの教室 => 階
    private const OTHER_ROOMS = ['職員室' => 1, '大会議室' => 3];

    // 複数のクラスが一緒に受ける授業の教室（1つのクラスだけの授業は、そのクラスの教室）
    private const LECTURE_ROOM = '大会議室';

    // 授業：[名前, 受けるクラス, 期間（year：通年 / first：前期 / second：後期）, 週に何回, 1回に何時限]
    // 年度ごとに同じ名前で作る（年度更新でコピーしたのと同じ形）。曜日・時限は重ならないように自動で決める
    private const SUBJECTS = [
        // 1年：共通
        ['コンピュータ概論', ['1T1', '1T2', '1T3', '1T4'], 'first', 1, 2],
        ['情報倫理', ['1T1', '1T2', '1T3', '1T4', '1B1'], 'second', 1, 1],
        ['キャリアデザインⅠ', ['1T2', '1T3', '1T4', '1B1'], 'year', 1, 1],
        ['英語Ⅰ', ['1T2', '1T3', '1T4', '1B1'], 'year', 1, 2],
        // 1T1（Tライセンス科）
        ['ITパスポート対策', ['1T1'], 'first', 2, 2],
        ['基本情報技術者対策', ['1T1'], 'year', 2, 2],
        ['アルゴリズムとデータ構造', ['1T1'], 'year', 1, 2],
        ['表計算演習', ['1T1'], 'second', 1, 2],
        // 1T2（情報処理科・Web技術科）
        ['プログラミング基礎Ⅰ', ['1T2'], 'first', 2, 2],
        ['プログラミング基礎Ⅱ', ['1T2'], 'second', 2, 2],
        ['Webデザイン基礎', ['1T2'], 'year', 1, 2],
        ['ネットワーク基礎', ['1T2'], 'first', 1, 2],
        ['データベース基礎', ['1T2'], 'second', 1, 2],
        // 1T3（先端ITシステム科）
        ['Javaプログラミング基礎', ['1T3'], 'year', 2, 2],
        ['Linux基礎', ['1T3'], 'first', 1, 2],
        ['ハードウェア基礎', ['1T3'], 'second', 1, 2],
        // 1T4（情報セキュリティ学科・実践AI科）
        ['Pythonプログラミング基礎', ['1T4'], 'year', 2, 2],
        ['線形代数', ['1T4'], 'year', 1, 2],
        ['情報セキュリティ基礎', ['1T4'], 'first', 1, 2],
        // 1B1（ビジネス科）
        ['簿記Ⅰ', ['1B1'], 'year', 2, 2],
        ['ビジネス文書作成', ['1B1'], 'first', 1, 2],
        ['表計算実務', ['1B1'], 'second', 1, 2],
        ['秘書検定対策', ['1B1'], 'second', 1, 2],
        // 2年：共通
        ['キャリアデザインⅡ', ['2T1', '2T2', '2T3', '2B1'], 'year', 1, 1],
        ['就職対策講座', ['2T1', '2T2', '2T3', '2B1'], 'first', 1, 1],
        // 2T1（情報処理科・Web技術科）
        ['Webアプリケーション開発', ['2T1'], 'year', 2, 2],
        ['PHPプログラミング', ['2T1'], 'first', 1, 2],
        ['JavaScript演習', ['2T1'], 'second', 1, 2],
        ['Web卒業制作', ['2T1'], 'second', 1, 3],
        // 2T2（先端ITシステム科）
        ['システム設計', ['2T2'], 'year', 1, 2],
        ['Javaプログラミング応用', ['2T2'], 'year', 1, 2],
        ['クラウド基礎', ['2T2'], 'first', 1, 2],
        ['データベース設計', ['2T2'], 'second', 1, 2],
        // 2T3（情報セキュリティ学科・実践AI科）
        ['機械学習入門', ['2T3'], 'year', 1, 2],
        ['確率・統計', ['2T3'], 'year', 1, 2],
        ['ネットワークセキュリティ', ['2T3'], 'first', 1, 2],
        ['サーバー構築演習', ['2T3'], 'second', 1, 2],
        // 2B1（ビジネス科）
        ['簿記Ⅱ', ['2B1'], 'year', 1, 2],
        ['マーケティング', ['2B1'], 'first', 1, 2],
        ['経営学概論', ['2B1'], 'second', 1, 2],
        ['ビジネス企画演習', ['2B1'], 'second', 1, 3],
        // 3年
        ['インターンシップ準備', ['3T1', '3T2', '3T3'], 'first', 1, 1],
        ['チーム開発演習', ['3T1'], 'year', 2, 3],
        ['プロジェクトマネジメント', ['3T1'], 'first', 1, 2],
        ['システム開発卒業研究', ['3T1'], 'second', 1, 3],
        ['セキュリティ診断演習', ['3T2'], 'year', 1, 3],
        ['暗号技術', ['3T2'], 'first', 1, 2],
        ['フォレンジック基礎', ['3T2'], 'second', 1, 2],
        ['深層学習', ['3T3'], 'year', 1, 3],
        ['データ分析演習', ['3T3'], 'first', 1, 2],
        ['自然言語処理', ['3T3'], 'second', 1, 2],
        // 4年
        ['卒業研究', ['4T1', '4T2'], 'year', 2, 3],
        ['インシデント対応演習', ['4T1'], 'first', 1, 3],
        ['セキュリティ法規', ['4T1'], 'second', 1, 2],
        ['AIシステム開発', ['4T2'], 'first', 1, 3],
        ['AI倫理', ['4T2'], 'second', 1, 2],
    ];

    // ---- 作ったデータ（あとの処理で使う） ----
    /** @var array<int, AcademicYear> 年度の数字 => 年度 */
    private array $years = [];

    /** @var array<string, Department> */
    private array $departments = [];

    /** @var array<string, Classroom> */
    private array $rooms = [];

    /** @var array<int, array<string, SchoolClass>> 年度の数字 => クラス名 => クラス */
    private array $classes = [];

    /** @var array<int, Teacher> */
    private array $teachers = [];

    /** @var array<int, array<int, list<int>>> 年度の数字 => クラスID => そのクラスの生徒ID */
    private array $members = [];

    /** @var array<int, array<string, int>> 年度の数字 => クラス名 => 担任（$teachers の番号） */
    private array $homerooms = [];

    public function run(): void
    {
        fake()->seed(20260401); // 乱数の種を固定する（何度実行しても同じデータになる）
        JapaneseName::reset();

        // まとめて1つのトランザクションにする（途中で失敗したら何も残らない。MySQL では速くもなる）
        DB::transaction(function () {
            $this->call(AdminSeeder::class);
            $this->createYears();
            $this->createDepartments();
            $this->createRooms();
            $this->createClasses();
            $this->createTeachers();
            $this->createStudents();
            $this->assignHomerooms();
            $this->createSubjects();
        });

        $this->report();

        // 今年度の、過去数週間分の出席記録（分析の画面の確認用）
        $this->call(TestAttendanceSeeder::class);
    }

    private function createYears(): void
    {
        foreach (self::YEARS as $year) {
            $this->years[$year] = AcademicYearFactory::new()->forYear($year)->createOne();
        }
    }

    private function createDepartments(): void
    {
        foreach (self::DEPARTMENTS as $name => [$years]) {
            $this->departments[$name] = DepartmentFactory::new()->createOne(['name' => $name, 'years' => $years]);
        }
    }

    private function createRooms(): void
    {
        foreach (self::CLASSES as [$room]) {
            $this->rooms[$room] = ClassroomFactory::new()->createOne(['name' => $room]); // 階は名前の最初の数字
        }
        foreach (self::OTHER_ROOMS as $room => $floor) {
            $this->rooms[$room] = ClassroomFactory::new()->createOne(['name' => $room, 'floor' => $floor]);
        }
    }

    // クラスは年度ごとに別の行で作る（同じ「1T1」でも2024年度と2025年度は別のクラス）
    private function createClasses(): void
    {
        foreach ($this->years as $number => $year) {
            foreach (self::CLASSES as $name => [$room]) {
                $this->classes[$number][$name] = SchoolClassFactory::new()->createOne([
                    'academic_year_id' => $year->id,
                    'name' => $name,
                    'grade' => (int) $name[0],
                    'classroom_id' => $this->rooms[$room]->id,
                ]);
            }
        }
    }

    private function createTeachers(): void
    {
        for ($i = 1; $i <= self::TEACHER_TOTAL; $i++) {
            $factory = TeacherFactory::new();
            if ($i > self::TEACHER_TOTAL - self::RETIRED_TEACHERS) {
                $factory = $factory->cannotLogin();
            }
            // 1人目は、いつものテスト用のログインID（teacher）にする
            $this->teachers[$i] = $factory->createOne(['login_id' => $i === 1 ? 'teacher' : sprintf('t%03d', $i)]);
        }
    }

    // 生徒：学科ごと・入学年度ごとのまとまりを作り、年度末の進級・卒業・留年・退学をたどって在籍を作る
    private function createStudents(): void
    {
        $first = min(self::YEARS);
        $last = max(self::YEARS);

        // 学年 => 学科 => クラス名
        /** @var array<int, array<string, string>> $classOf */
        $classOf = [];
        foreach (self::CLASSES as $name => [, $departments]) {
            foreach ($departments as $department) {
                $classOf[(int) $name[0]][$department] = $name;
            }
        }
        // どの学年にもクラスがあるか（CLASSES を書き換えたときの確認）
        foreach (self::DEPARTMENTS as $department => [$years]) {
            for ($grade = 1; $grade <= $years; $grade++) {
                if (! isset($classOf[$grade][$department])) {
                    throw new \RuntimeException("{$department}の{$grade}年生のクラスが CLASSES にありません。");
                }
            }
        }

        // 最初の年度に在籍している学年の人から、最後の年度の新入生までのまとまり
        // 例：4年制の学科なら、最初の年度に4年生の人（3年前に入学）から
        $cohorts = [];
        foreach (self::DEPARTMENTS as $department => [$years]) {
            for ($entry = $first - $years + 1; $entry <= $last; $entry++) {
                $cohorts[] = [$department, $entry];
            }
        }

        // 500人を、まとまりごとにできるだけ同じ人数に分ける（余りは、ばらばらのまとまりに1人ずつ）
        $sizes = array_fill(0, count($cohorts), intdiv(self::STUDENT_TOTAL, count($cohorts)));
        foreach (array_slice($this->shuffled(array_keys($cohorts)), 0, self::STUDENT_TOTAL % count($cohorts)) as $index) {
            $sizes[$index]++;
        }

        $rows = []; // 在籍：[生徒, 年度の数字, 学年, クラス名, 結果]
        foreach ($cohorts as $index => [$department, $entry]) {
            [$years, $code] = self::DEPARTMENTS[$department];

            for ($i = 1; $i <= $sizes[$index]; $i++) {
                // 1年ずつたどる：[年度の数字, 学年, 年度末の結果]
                $path = [];
                $left = false; // 卒業・退学したか
                $grade = max(1, $first - $entry + 1); // 最初に在籍する年度の学年（その年度に入学した人は1年生）
                for ($year = max($entry, $first); $year <= $last; $year++) {
                    $result = null;
                    $next = $grade;
                    if ($year < $last) {
                        // 年度末の結果（最後の年度＝今年度は、まだ決まっていない）
                        $roll = fake()->numberBetween(1, 100);
                        $action = match (true) {
                            $roll <= self::WITHDRAW_RATE => 'withdraw',
                            $roll <= self::WITHDRAW_RATE + self::REPEAT_RATE => 'repeat',
                            default => null,
                        };
                        [$result, $next] = Enrollment::decideNext($grade, $years, $action);
                    }
                    $path[] = [$year, $grade, $result];
                    if (in_array($result, ['graduated', 'withdrawn'], true)) {
                        $left = true;
                        break;
                    }
                    $grade = $next ?? $grade;
                }

                // 卒業・退学した生徒は、照合の対象から外す（is_active = false）
                $student = ($left ? StudentFactory::new()->inactive() : StudentFactory::new())->createOne([
                    // 学籍番号：入学年度の下2桁 ＋ 学科の番号 ＋ 連番4桁（例：2630012）
                    'student_number' => sprintf('%02d%d%04d', $entry % 100, $code, $i),
                    'department_id' => $this->departments[$department]->id,
                ]);

                foreach ($path as [$year, $grade, $result]) {
                    $rows[] = [$student, $year, $grade, $classOf[$grade][$department], $result];
                }
            }
        }

        // 最後の年度の新入生の何人かは、クラス・出席番号が未定（あとから入学した人）。生徒ID => true
        $newcomers = array_filter($rows, fn ($row) => $row[1] === $last && $row[2] === 1);
        $undecided = array_flip(array_slice($this->shuffled(array_map(fn ($row) => $row[0]->id, $newcomers)), 0, self::UNDECIDED));

        // 出席番号：年度・クラスごとに、ふりがなの順で 1, 2, 3 …
        usort($rows, fn ($a, $b) => [$a[1], $a[3], $a[0]->furigana, $a[0]->id] <=> [$b[1], $b[3], $b[0]->furigana, $b[0]->id]);
        $numbers = []; // 年度 => クラス名 => 最後に付けた出席番号
        foreach ($rows as [$student, $year, $grade, $className, $result]) {
            $factory = EnrollmentFactory::new()->result($result);

            $class = null;
            if (! ($year === $last && isset($undecided[$student->id]))) {
                $class = $this->classes[$year][$className];
                $numbers[$year][$className] = ($numbers[$year][$className] ?? 0) + 1;
                $factory = $factory->inClass($class, (string) $numbers[$year][$className]);
            }

            $factory->createOne([
                'student_id' => $student->id,
                'academic_year_id' => $this->years[$year]->id,
                'grade' => $grade,
            ]);

            if ($class !== null) {
                $this->members[$year][$class->id][] = $student->id;
            }
        }
    }

    // 担任：年度ごと・クラスごとに1人（teacher は毎年 1T1 の担任）
    private function assignHomerooms(): void
    {
        foreach ($this->years as $number => $year) {
            $candidates = $this->shuffled(array_keys($this->activeTeachers($number)));
            $candidates = array_values(array_diff($candidates, [1]));

            foreach (array_keys(self::CLASSES) as $name) {
                $teacherIndex = $name === '1T1' ? 1 : (array_shift($candidates) ?? throw new \RuntimeException('担任にする教師が足りません。TEACHER_TOTAL を増やしてください。'));
                $this->homerooms[$number][$name] = $teacherIndex;
                DB::table('school_class_teacher')->insert([
                    'school_class_id' => $this->classes[$number][$name]->id,
                    'teacher_id' => $this->teachers[$teacherIndex]->id,
                ]);
            }
        }
    }

    // その年度に働いている教師（退職した教師は、最後の年度にはいない）
    /** @return array<int, Teacher> */
    private function activeTeachers(int $year): array
    {
        return array_filter(
            $this->teachers,
            fn ($index) => $year < max(self::YEARS) || $index <= self::TEACHER_TOTAL - self::RETIRED_TEACHERS,
            ARRAY_FILTER_USE_KEY,
        );
    }

    // 授業：年度ごとに作り、クラス・教室・教師が同じ時間に重ならないように曜日と時限を決める
    private function createSubjects(): void
    {
        $periods = 6; // 1〜6限に入れる（7限は使わない）

        foreach ($this->years as $number => $year) {
            $busy = [];  // 使用中の時間 "c:クラスID|期|曜日|時限" など
            $load = array_fill_keys(array_keys($this->activeTeachers($number)), 0); // 教師ごとの担当数

            foreach (self::SUBJECTS as [$name, $classNames, $term, $times, $length]) {
                $classes = array_map(fn ($c) => $this->classes[$number][$c], $classNames);
                $room = count($classes) === 1 ? $this->rooms[self::CLASSES[$classNames[0]][0]] : $this->rooms[self::LECTURE_ROOM];
                $halves = ['year' => [1, 2], 'first' => [1], 'second' => [2]][$term]; // 前期=1・後期=2

                // その時間に使う「クラス・教室・教師」の印
                $keys = function (int $day, int $start, array $owners) use ($halves, $length) {
                    $keys = [];
                    foreach ($halves as $half) {
                        for ($p = $start; $p < $start + $length; $p++) {
                            foreach ($owners as $owner) {
                                $keys[] = "{$owner}|{$half}|{$day}|{$p}";
                            }
                        }
                    }

                    return $keys;
                };
                $isFree = fn (array $keys) => array_intersect_key(array_flip($keys), $busy) === [];
                $owners = array_merge(array_map(fn ($c) => "c:{$c->id}", $classes), ["r:{$room->id}"]);

                // 教師の候補：担当の少ない順（同じ数なら、ばらばら）
                // 1つのクラスだけの授業で、そのクラスの担任がまだ何も担当していなければ、担任を先にする
                // （teacher は画面の確認用に、1T1 の授業を3つまで先に受け持つ）
                $candidates = $this->shuffled(array_keys(array_filter($load, fn ($n) => $n < self::MAX_SUBJECTS)));
                usort($candidates, fn ($a, $b) => $load[$a] <=> $load[$b]);
                $homeroom = count($classes) === 1 ? $this->homerooms[$number][$classNames[0]] : null;
                if ($homeroom !== null && $load[$homeroom] < ($homeroom === 1 ? 3 : 1)) {
                    $candidates = array_merge([$homeroom], array_diff($candidates, [$homeroom]));
                }

                // 曜日・時限の候補（ばらばらの順に試す）
                $slots = [];
                for ($day = 1; $day <= 5; $day++) {
                    for ($start = 1; $start + $length - 1 <= $periods; $start++) {
                        $slots[] = [$day, $start];
                    }
                }
                $slots = $this->shuffled($slots);

                $chosen = null;
                foreach ($candidates as $teacherIndex) {
                    $picked = [];
                    foreach ($slots as [$day, $start]) {
                        if (isset($picked[$day])) {
                            continue; // 同じ授業は1日に1回まで（時間割の決まり）
                        }
                        if ($isFree($keys($day, $start, [...$owners, "t:{$teacherIndex}"]))) {
                            $picked[$day] = $start;
                            if (count($picked) === $times) {
                                break;
                            }
                        }
                    }
                    if (count($picked) === $times) {
                        $chosen = [$teacherIndex, $picked];
                        break;
                    }
                }
                if ($chosen === null) {
                    throw new \RuntimeException("{$number}年度の「{$name}」を入れる時間がありません。SUBJECTS の回数・時限数を減らしてください。");
                }
                [$teacherIndex, $picked] = $chosen;

                // 3時限続く演習は、半分くらいの授業に2人目の教師（補助）を付ける
                $teacherIndexes = [$teacherIndex];
                if ($length >= 3 && fake()->boolean()) {
                    foreach ($candidates as $assistant) {
                        $free = $assistant !== $teacherIndex && collect($picked)->every(
                            fn ($start, $day) => $isFree($keys($day, $start, ["t:{$assistant}"])),
                        );
                        if ($free) {
                            $teacherIndexes[] = $assistant;
                            break;
                        }
                    }
                }

                // 授業を作る（通年は期間を空にする＝年度の間ずっと）
                $factory = SubjectFactory::new();
                $factory = match ($term) {
                    'first' => $factory->firstHalf(),
                    'second' => $factory->secondHalf(),
                    default => $factory,
                };
                $subject = $factory->createOne([
                    'academic_year_id' => $year->id,
                    'name' => $name,
                    'grade' => $classes[0]->grade,
                ]);

                foreach ($picked as $day => $start) {
                    $subject->schedules()->create([
                        'day_of_week' => $day,
                        'period_start' => $start,
                        'period_end' => $start + $length - 1,
                        'classroom_id' => $room->id,
                    ]);
                    foreach ($teacherIndexes as $t) {
                        foreach ($keys($day, $start, [...$owners, "t:{$t}"]) as $key) {
                            $busy[$key] = true;
                        }
                    }
                }

                foreach ($teacherIndexes as $t) {
                    $load[$t]++;
                    DB::table('subject_teacher')->insert(['subject_id' => $subject->id, 'teacher_id' => $this->teachers[$t]->id]);
                }

                // 受講する生徒：その年度に、受けるクラスに在籍している生徒（クラス未定の生徒は、まだ入れない）
                $studentIds = collect($classes)->flatMap(fn ($c) => $this->members[$number][$c->id] ?? []);
                foreach ($studentIds->chunk(500) as $chunk) {
                    DB::table('student_subject')->insert(
                        $chunk->map(fn ($id) => ['student_id' => $id, 'subject_id' => $subject->id])->all(),
                    );
                }
            }
        }
    }

    // 並びをばらばらにした配列（シーダーの最初に乱数の種を固定しているので、毎回同じ並びになる）
    /**
     * @template T
     * @param  array<array-key, T>  $items
     * @return list<T>
     */
    private function shuffled(array $items): array
    {
        $items = array_values($items);
        shuffle($items);

        return $items;
    }

    // 作ったデータの数を表示する
    private function report(): void
    {
        if ($this->command === null) {
            return;
        }

        $rows = [];
        foreach ($this->years as $number => $year) {
            $rows[] = [
                "{$number}年度",
                SchoolClass::where('academic_year_id', $year->id)->count(),
                Subject::where('academic_year_id', $year->id)->count(),
                Enrollment::where('academic_year_id', $year->id)->count(),
                Enrollment::where('academic_year_id', $year->id)->where('result', 'graduated')->count(),
                Enrollment::where('academic_year_id', $year->id)->where('result', 'withdrawn')->count(),
                Enrollment::where('academic_year_id', $year->id)->where('result', 'repeated')->count(),
            ];
        }
        $this->command->table(['年度', 'クラス', '授業', '在籍', '卒業', '退学', '留年'], $rows);
        $this->command->info(sprintf(
            '生徒 %d人（在校 %d人・卒業 %d人・退学 %d人） ／ 教師 %d人（ログイン不可 %d人） ／ 学科 %d ／ 教室 %d',
            Student::count(),
            Student::where('is_active', true)->count(),
            Enrollment::where('result', 'graduated')->count(),
            Enrollment::where('result', 'withdrawn')->count(),
            Teacher::count(),
            Teacher::where('can_login', false)->count(),
            Department::count(),
            Classroom::count(),
        ));
    }
}