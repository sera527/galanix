@extends('app')

@section('content')
    <h2>Дата проведення парсінгу: {{ $parsing->datetime }}</h2>
    <table class="table">
        <thead>
        <tr>
            <th>Час новини</th>
            <th>Заголовок</th>
            <th>Мітки</th>
        </tr>
        </thead>
        <tbody>
        @foreach($articles as $article)
            <tr @if($article->is_important)class="info"@endif><td>{{ $article->time }}</td><td><a href="{{ $article->url }}">{{ $article->title }}</a></td><td>
                @foreach($article->markers as $marker)
                    @if ($loop->last)
                        {{ $marker->name }}
                    @else
                        {{ $marker->name }},
                    @endif
                @endforeach
                </td></tr>
        @endforeach
        </tbody>
    </table>
    <h2><a href="/parsings">Список парсінгів</a></h2>
    <h2><a href="/">Новий парсінг</a></h2>
@endsection