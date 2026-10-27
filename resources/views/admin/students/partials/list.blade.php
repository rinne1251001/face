{{-- 生徒一覧の表の部分。並び替え・ページ送りのときは、ここだけを Ajax で入れ替える --}}
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
                @include('partials.sort-header', ['key' => 'class', 'label' => 'クラス'])
                <th>状態</th>
                <th>顔写真</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $student)
                <tr>
                    <td>{{ $student->student_number }}</td>
                    <td>{{ $student->name }}</td>
                    <td>{{ $student->schoolClass->name }}</td>
                    <td><span class="badge-status {{ $student->is_active ? 'active' : 'inactive' }}">{{ $student->is_active ? '有効' : '停止中' }}</span></td>
                    <td>
                        @if ($student->face_samples_count > 0)
                            {{ $student->face_samples_count }}枚
                        @else
                            <span class="badge-photo-none">未登録</span>
                        @endif
                    </td>
                    <td class="action-links">
                        <a href="{{ route('admin.students.edit', $student) }}" class="btn-action">編集</a>
                        <a href="{{ route('admin.faces.create', $student) }}">顔を登録</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6">該当する生徒はいません。</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination">
    @if ($students->previousPageUrl())<a href="{{ $students->previousPageUrl() }}" id="page-prev" class="page-link" data-ajax>&laquo; 前へ</a>@endif
    <span class="page-link active">{{ $students->currentPage() }} / {{ $students->lastPage() }}</span>
    @if ($students->nextPageUrl())<a href="{{ $students->nextPageUrl() }}" id="page-next" class="page-link" data-ajax>次へ &raquo;</a>@endif
</div>