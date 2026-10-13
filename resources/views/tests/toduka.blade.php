@extends('layouts.toduka')
@section('title', 'toduka')
@section('content')
@php
$students = \App\Models\Student::all();
@endphp

@foreach ($students as $student)
    <p>{{ $student->student_number }}</p>
@endforeach
    <p>ここにページ毎に違うコードを書く</p>

@endsection