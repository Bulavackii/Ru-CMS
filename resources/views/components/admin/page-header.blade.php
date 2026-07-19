{{-- Заголовок страницы с breadcrumbs и действиями --}}
@props([
    'title' => '',
    'subtitle' => null,
    'breadcrumbs' => [],
    'actions' => [],
])

<div class="mb-6">
    {{-- Breadcrumbs --}}
    @if(count($breadcrumbs) > 0)
        <x-admin.breadcrumbs :items="$breadcrumbs" />
    @endif

    {{-- Заголовок и действия --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $title }}</h1>
            @if($subtitle)
                <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $subtitle }}</p>
            @endif
        </div>
        
        @if(count($actions) > 0)
            <div class="flex flex-wrap gap-2">
                @foreach($actions as $action)
                    @if(isset($action['type']) && $action['type'] === 'button')
                        <button type="button"
                                onclick="{{ $action['onclick'] ?? '' }}"
                                class="inline-flex items-center gap-2 px-4 py-2 {{ $action['class'] ?? 'bg-blue-600 hover:bg-blue-700 text-white' }} rounded-lg transition">
                            @if(isset($action['icon']))
                                <i class="fas fa-{{ $action['icon'] }}"></i>
                            @endif
                            {{ $action['label'] }}
                        </button>
                    @else
                        <a href="{{ $action['url'] ?? '#' }}"
                           class="inline-flex items-center gap-2 px-4 py-2 {{ $action['class'] ?? 'bg-blue-600 hover:bg-blue-700 text-white' }} rounded-lg transition">
                            @if(isset($action['icon']))
                                <i class="fas fa-{{ $action['icon'] }}"></i>
                            @endif
                            {{ $action['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
</div>




