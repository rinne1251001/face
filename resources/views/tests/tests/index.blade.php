{{ $subjects->count() }}件
{{ $classes->count() }}件
{{ $subjects->isNotEmpty() }}
@if (isset($students))
    <p>変数$studentsは存在する</p>

@endif
@foreach($subjects as $subject)
{{ $subject->name }}{{ $subject->id }}
<a href="{{ route('tests.show', $subject) }}">{{ $subject->name }}</a>
<a href="{{ route('tests.edit', $subject) }}">{{ $subject->name }}</a>
<form method="POST" action="{{ route('tests.destroy', $subject) }}">
    @csrf
    @method('DELETE')
    <button>削除</button>
</form>

<br>

@endforeach
@foreach($classes as $class)
{{ $class->name }}{{ $class->created_at?->format('Y年m月d日H時i分s秒') }}
@endforeach
<a href="{{ route('tests.create') }}">クリエイト</a>


