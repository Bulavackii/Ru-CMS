{{-- Breadcrumbs компонент для навигации --}}
@props(['items' => []])

@if(count($items) > 0)
<nav aria-label="Breadcrumb" class="mb-4">
    <ol class="flex items-center space-x-2 text-sm">
        @foreach($items as $index => $item)
            <li class="flex items-center">
                @if($index > 0)
                    <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                @endif
                @if(isset($item['url']) && !$loop->last)
                    <a href="{{ $item['url'] }}" 
                       class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition">
                        {{ $item['label'] }}
                    </a>
                @else
                    <span class="text-gray-900 dark:text-white font-medium">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
@endif




