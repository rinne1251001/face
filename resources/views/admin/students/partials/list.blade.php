{{-- 生徒一覧の表の部分。並び替え・ページ送りのときは、ここだけを Ajax で入れ替える
     クラス・出席番号・学年は、選んでいる年度（$year）の在籍の値 --}}
<div class="table-info">
    @if ($students->total() > 0)
        <span>全 <strong>{{ $students->total() }}</strong> 件中 {{ $students->firstItem() }}〜{{ $students->lastItem() }} 件を表示</span>
    @endif
</div>

<div class="table-wrapper">
    <table class="student-table">
        <thead>
            <tr>
                {{-- 並び替えを増やすときは、<th> をこの形に変えて、コントローラーの sortColumns() に1行足す --}}
                @include('partials.sort-header', ['key' => 'student_number', 'label' => '学籍番号'])
                @include('partials.sort-header', ['key' => 'name', 'label' => '名前'])
                <th>学年</th>
                @include('partials.sort-header', ['key' => 'class', 'label' => 'クラス'])
                @include('partials.sort-header', ['key' => 'attend_number', 'label' => '出席番号'])
                <th>状態</th>
                <th>顔写真</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                {{-- この年度の在籍（一覧の生徒は、必ずこの年度に在籍している） --}}
                @php $enrollment = $student->enrollmentFor($year); @endphp
                <tr>
                    <td>{{ $student->student_number }}</td>
                    <td>{{ $student->name }}</td>
                    <td>{{ $enrollment->grade }}年</td>
                    <td>{{ $enrollment->schoolClass?->name ?? '未定' }}</td>
                    <td>{{ $enrollment->attend_number ?? '未定' }}</td>
                    <td><span class="badge-status {{ $student->is_active ? 'active' : 'inactive' }}">{{ $student->is_active ? '有効' : '停止中' }}</span></td>
                    <td>
                        @if ($student->face_samples_count > 0)
                            {{ $student->face_samples_count }}枚
                        @else
                            <span class="badge-photo-none">未登録</span>
                        @endif
                    </td>
                    <td class="action-links">
                        {{-- 読み取り専用の年度では、見るだけの画面になる --}}
                        <a href="{{ route('admin.students.edit', [$student, 'year' => $year->year]) }}" class="btn-action">{{ $year->isEditable() ? '編集' : '詳細' }}</a>
                        <a href="{{ route('admin.faces.create', $student) }}">顔を登録</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="8">該当する生徒はいません。</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    @if ($students->previousPageUrl())<a href="{{ $students->previousPageUrl() }}" id="page-prev" class="page-link" data-ajax>&laquo; 前へ</a>@endif
    <span class="page-link active">{{ $students->currentPage() }} / {{ $students->lastPage() }}</span>
    @if ($students->nextPageUrl())<a href="{{ $students->nextPageUrl() }}" id="page-next" class="page-link" data-ajax>次へ &raquo;</a>@endif
</div>