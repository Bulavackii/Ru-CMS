@extends('layouts.frontend')

@section('title', __('frontend.news.title'))

@section('content')
    {{--
        Лента новостей на отдельной странице.

        Вёрстка не своя, а та же, что на главной: шаблон
        frontend/templates/default.blade.php. Раньше здесь лежала ПОЛНАЯ
        копия карточки и логики определения обложки — копии разошлись, и
        одна и та же новость выглядела на главной и здесь по-разному
        (тут, например, обрезка держалась на line-clamp, которого в сборке
        Tailwind нет). Одна вёрстка — одно место правки.
    --}}
    @include('frontend.templates.default', [
        'newsList' => $newsList,
        'title' => $title ?? __('frontend.news.title'),
    ])
@endsection
