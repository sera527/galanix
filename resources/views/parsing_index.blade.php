@extends('app')

@section('content')
    <h2>Дати проведення парсінгів</h2>
    <table class="table">
        <thead>
        <tr>
            <th>№</th>
            <th>Дата</th>
        </tr>
        </thead>
        <tbody>
        @foreach($parsings as $parsing)
            <tr><td>{{ $parsing->id }}</td><td><a href="/parsing/{{ $parsing->id }}">{{ $parsing->datetime }}</a></td></tr>
        @endforeach
        </tbody>
    </table>
    <h2><a href="/">Новий парсінг</a></h2>
@endsection