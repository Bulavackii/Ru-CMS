@extends('layouts.frontend')

@section('title', 'Новости')

@section('content')
    <h1 class="text-3xl font-bold mb-8 text-center">Новости</h1>

    @if ($news->count())
        <div class="flex flex-wrap justify-center gap-6">
            @foreach ($news as $item)
                <div class="w-full sm:w-2/3 md:w-1/2 lg:w-1/3">
                    @include('frontend.partials.news-card', ['news' => $item])
                </div>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $news->links() }}
        </div>
    @else
        <p class="text-center text-gray-500">Новостей пока нет.</p>
    @endif
@endsection
