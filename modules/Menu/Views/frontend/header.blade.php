@php
    // Фолбэк, если по какой-то причине не сработал View::composer
    // (например, кеш шаблонов/служб): берём меню прямо отсюда.
    if (!isset($menus)) {
        $menus = \Modules\Menu\Models\Menu::query()
            ->where('active', true)
            ->where('position', 'header')
            ->with([
                'items' => fn($q) => $q->where('active', true)->orderBy('order'),
                'items.children' => fn($q) => $q->where('active', true)->orderBy('order'),
                'items.linkedPage',
            ])->get();
    }
@endphp

<nav class="w-full border-y border-gray-200 dark:border-gray-700 bg-white/90 dark:bg-gray-900/90 backdrop-blur" x-data>
  <div class="max-w-screen-xl mx-auto px-4">
    {{-- убираем маркеры, отступы, делаем гибкую линию меню --}}
    <ul class="flex flex-wrap items-center gap-4 py-3 m-0 list-none">
      @foreach ($menus as $menu)
        @foreach ($menu->items->where('parent_id', null)->where('active', true) as $item)
          @php
              $hasChildren = $item->activeChildren && $item->activeChildren->count() > 0;

              $link = match($item->type) {
                  'url'      => $item->url ?? '#',
                  'page'     => optional($item->linkedPage)?->slug
                                ? route('frontend.pages.show', optional($item->linkedPage)->slug)
                                : '#',
                  'category' => url('/?category=' . $item->linked_id),
                  default    => '#',
              };
              
              $linkAttrs = [];
              if ($item->target) {
                  $linkAttrs['target'] = $item->target;
              }
              if ($item->rel) {
                  $linkAttrs['rel'] = $item->rel;
              }
              $linkAttrsStr = collect($linkAttrs)->map(fn($v, $k) => "$k=\"$v\"")->join(' ');
              
              $cssClasses = 'px-3 py-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800 transition text-gray-700 dark:text-gray-200';
              if ($item->css_class) {
                  $cssClasses .= ' ' . $item->css_class;
              }
          @endphp

          <li class="relative {{ $item->css_class ?? '' }}" x-data="{ open:false }" @mouseenter="open=true" @mouseleave="open=false">
            <div class="flex items-center gap-1">
              @if($hasChildren)
                {{-- для пунктов с детьми: клик раскрывает подменю вместо перехода --}}
                <a href="#"
                   class="{{ $cssClasses }}"
                   @click.prevent="open = !open"
                   aria-haspopup="true"
                   :aria-expanded="open.toString()">
                  @if($item->icon)
                    <span class="mr-1">@themeIcon($item->icon)</span>
                  @endif
                  {{ $item->title }}
                </a>
                <button class="p-2 -ml-2 rounded-md hover:bg-gray-100 dark:hover:bg-gray-800"
                        @click.stop="open = !open"
                        aria-label="Открыть подменю">▾</button>
              @else
                {{-- обычный пункт без детей --}}
                <a href="{{ $link }}"
                   class="{{ $cssClasses }}"
                   {!! $linkAttrsStr !!}>
                  @if($item->icon)
                    <span class="mr-1">@themeIcon($item->icon)</span>
                  @endif
                  {{ $item->title }}
                </a>
              @endif
            </div>

            @if($hasChildren)
              {{-- выпадашка; первая строка — «Перейти: Родитель» для явного перехода --}}
              <ul x-cloak x-show="open" x-transition.origin.top.left
                  class="absolute left-0 mt-2 min-w-[14rem] z-[1000] bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-lg rounded-md p-1 list-none">
                <li>
                  <a href="{{ $link }}"
                     class="block px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200"
                     {!! $linkAttrsStr !!}>
                    Перейти: {{ $item->title }}
                  </a>
                </li>

                @foreach ($item->activeChildren as $child)
                  @php
                      $childLink = match($child->type) {
                          'url'      => $child->url ?? '#',
                          'page'     => optional($child->linkedPage)?->slug
                                        ? route('frontend.pages.show', optional($child->linkedPage)->slug)
                                        : '#',
                          'category' => url('/?category=' . $child->linked_id),
                          default    => '#',
                      };
                      
                      $childAttrs = [];
                      if ($child->target) {
                          $childAttrs['target'] = $child->target;
                      }
                      if ($child->rel) {
                          $childAttrs['rel'] = $child->rel;
                      }
                      $childAttrsStr = collect($childAttrs)->map(fn($v, $k) => "$k=\"$v\"")->join(' ');
                      
                      $childCssClasses = 'block px-3 py-2 rounded hover:bg-gray-100 dark:hover:bg-gray-800 text-gray-700 dark:text-gray-200';
                      if ($child->css_class) {
                          $childCssClasses .= ' ' . $child->css_class;
                      }
                  @endphp
                  <li>
                    <a href="{{ $childLink }}"
                       class="{{ $childCssClasses }}"
                       {!! $childAttrsStr !!}>
                      @if($child->icon)
                        <span class="mr-1">@themeIcon($child->icon)</span>
                      @endif
                      {{ $child->title }}
                    </a>
                  </li>
                @endforeach
              </ul>
            @endif
          </li>
        @endforeach
      @endforeach
    </ul>
  </div>
</nav>

{{-- чтобы выпадашка не мигала до инициализации Alpine --}}
<style>[x-cloak]{display:none!important}</style>
