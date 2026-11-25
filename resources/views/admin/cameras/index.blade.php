{{ $cameras->count() }}件
<a href="{{ route('admin.cameras.create') }}">カメラを追加</a>
@foreach($cameras as $camera)
    <a href="{{ route('admin.cameras.show', $camera) }}">
        {{ $camera->classroom->name ?? 'カメラ #' . $camera->id }}
    </a>
    <a href="{{ route('admin.cameras.edit', $camera) }}">編集</a>
@endforeach