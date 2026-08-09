{{-- Фон или знак других тем — чтобы забрать себе.

     Владелец: «хочу зайти и скачать — установить фоновое изображение от
     Индиго на любую тему». Без этого списка пришлось бы открыть другую тему,
     скачать оттуда, вернуться и загрузить — а по дороге легко сохранить
     чужую тему по ошибке.

     Блок только СКАЧИВАЕТ. Он ничего не меняет в текущей теме: файл
     сохраняется на диск, а дальше владелец загружает его обычной кнопкой
     выше. Так механизм замены картинки остаётся ровно тем же, каким был.

     Ожидает: $library — набор тем (см. ThemesController::assetLibrary),
              $kind    — 'background' или 'logo'. --}}

@php
    $available = ($library ?? collect())->filter(fn ($item) => $kind === 'logo' ? $item->logo : $item->background);
@endphp

@if($available->isNotEmpty())
    <details class="thm-borrow">
        <summary>
            <i class="fas fa-download"></i>
            {{ $kind === 'logo' ? 'Знаки других тем' : 'Узоры других тем' }}
            <span class="opacity-60">— скачать и загрузить сюда ({{ $available->count() }})</span>
        </summary>

        <div class="thm-borrow__list">
            @foreach($available as $item)
                @php $url = $kind === 'logo' ? $item->logo : $item->background; @endphp
                <div class="thm-borrow__row">
                    <span class="thm-borrow__dot" style="background: {{ $item->primary }}"></span>

                    @if($kind === 'logo')
                        <img class="thm-borrow__logo" src="{{ $url }}" alt="{{ $item->title }}" loading="lazy">
                    @else
                        <span class="thm-borrow__tile" style="background-image:url('{{ $url }}')"></span>
                    @endif

                    <span class="thm-borrow__name">{{ $item->title }}</span>

                    <a class="thm-btn" href="{{ $url }}" download
                       title="Скачать файл темы «{{ $item->title }}»">
                        <i class="fas fa-download"></i> Скачать
                    </a>
                </div>
            @endforeach
        </div>
    </details>
@endif
