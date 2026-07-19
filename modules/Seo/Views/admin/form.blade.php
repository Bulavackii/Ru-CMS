<form method="post" action="{{ $action }}" class="space-y-6">
  @csrf
  @php
    /** @var string $method */
    $method = strtoupper($method ?? 'POST');
    $isCreate = $method === 'POST';
  @endphp
  @if($method !== 'POST') @method($method) @endif

  {{-- Основные URL-поля --}}
  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium">
        Slug
        <span class="text-xs text-gray-500">(путь или полный URL)</span>
      </label>
      <input
        name="slug"
        value="{{ old('slug', $item->slug ?? '') }}"
        class="border p-2 rounded w-full"
        maxlength="1024"
        @if($isCreate) required @endif
        aria-describedby="slug-help"
      >
      <div id="slug-help" class="text-xs text-gray-500 mt-1">
        Примеры: <code>/news/my-post</code> или <code>https://site.ru/news/my-post</code>.
        Абсолютный URL будет сохранён как есть; относительный — как путь.
      </div>
      @error('slug')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div>
      <label class="block text-sm font-medium">
        Canonical
        <span class="text-xs text-gray-500">(можно оставить пустым)</span>
      </label>
      <input
        name="canonical"
        value="{{ old('canonical', $item->canonical ?? '') }}"
        class="border p-2 rounded w-full"
        maxlength="1024"
        aria-describedby="canonical-help"
      >
      <div id="canonical-help" class="text-xs text-gray-500 mt-1">
        Если указать относительный путь — при сохранении станет абсолютным (на основе <code>APP_URL</code>).
        Если оставить пустым — каноникал не будет задан.
      </div>
      @error('canonical')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>
  </div>

  {{-- Title / H1 --}}
  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <label class="block text-sm font-medium">Title</label>
      <input
        name="title"
        value="{{ old('title', $item->title ?? '') }}"
        class="border p-2 rounded w-full"
        maxlength="255"
        placeholder="Короткий заголовок для &lt;title&gt;"
      >
      @error('title')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div>
      <label class="block text-sm font-medium">H1</label>
      <input
        name="h1"
        value="{{ old('h1', $item->h1 ?? '') }}"
        class="border p-2 rounded w-full"
        maxlength="255"
        placeholder="Основной заголовок страницы (видимый H1)"
      >
      @error('h1')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>
  </div>

  {{-- Description --}}
  <div>
    <label class="block text-sm font-medium">Description</label>
    <input
      name="description"
      value="{{ old('description', $item->description ?? '') }}"
      class="border p-2 rounded w-full"
      maxlength="512"
      placeholder="Краткое описание для мета-тега description"
    >
    @error('description')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
  </div>

  {{-- Robots --}}
  <div class="grid md:grid-cols-2 gap-4">
    <div>
      <input type="hidden" name="robots_index" value="0">
      <label class="inline-flex items-center">
        <input
          type="checkbox"
          name="robots_index"
          value="1"
          class="mr-2"
          {{ old('robots_index', $item->robots_index ?? true) ? 'checked' : '' }}
        >
        index
      </label>
      @error('robots_index')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div>
      <input type="hidden" name="robots_follow" value="0">
      <label class="inline-flex items-center">
        <input
          type="checkbox"
          name="robots_follow"
          value="1"
          class="mr-2"
          {{ old('robots_follow', $item->robots_follow ?? true) ? 'checked' : '' }}
        >
        follow
      </label>
      @error('robots_follow')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>
  </div>
  <div class="text-xs text-gray-500 -mt-2">
    Итоговая директива будет: <code>
      {{ (old('robots_index', $item->robots_index ?? true) ? 'index' : 'noindex') }},
      {{ (old('robots_follow', $item->robots_follow ?? true) ? 'follow' : 'nofollow') }}
    </code>
  </div>

  {{-- OG / Twitter --}}
  <div class="border rounded p-4 space-y-3">
    <h3 class="font-semibold">OG / Twitter</h3>
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium">og:title</label>
        <input
          name="og_title"
          value="{{ old('og_title', $item->og['og:title'] ?? '') }}"
          class="border p-2 rounded w-full"
          maxlength="255"
        >
        @error('og_title')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="block text-sm font-medium">og:description</label>
        <input
          name="og_description"
          value="{{ old('og_description', $item->og['og:description'] ?? '') }}"
          class="border p-2 rounded w-full"
          maxlength="512"
        >
        @error('og_description')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="block text-sm font-medium">og:image (URL)</label>
        <input
          name="og_image"
          value="{{ old('og_image', $item->og['og:image'] ?? '') }}"
          class="border p-2 rounded w-full"
          maxlength="1024"
          placeholder="https://example.com/image.jpg"
        >
        <div class="text-xs text-gray-500 mt-1">Рекомендуется абсолютный URL.</div>
        @error('og_image')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="block text-sm font-medium">twitter:card</label>
        <input
          name="twitter_card"
          value="{{ old('twitter_card', $item->og['twitter:card'] ?? '') }}"
          class="border p-2 rounded w-full"
          maxlength="50"
          placeholder="summary или summary_large_image"
        >
        @error('twitter_card')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="block text-sm font-medium">twitter:title</label>
        <input
          name="twitter_title"
          value="{{ old('twitter_title', $item->og['twitter:title'] ?? '') }}"
          class="border p-2 rounded w-full"
          maxlength="255"
        >
        @error('twitter_title')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="block text-sm font-medium">twitter:description</label>
        <input
          name="twitter_description"
          value="{{ old('twitter_description', $item->og['twitter:description'] ?? '') }}"
          class="border p-2 rounded w-full"
          maxlength="512"
        >
        @error('twitter_description')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
      </div>

      <div>
        <label class="block text-sm font-medium">twitter:image (URL)</label>
        <input
          name="twitter_image"
          value="{{ old('twitter_image', $item->og['twitter:image'] ?? '') }}"
          class="border p-2 rounded w-full"
          maxlength="1024"
          placeholder="https://example.com/image.jpg"
        >
        <div class="text-xs text-gray-500 mt-1">Рекомендуется абсолютный URL.</div>
        @error('twitter_image')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
      </div>
    </div>
  </div>

  {{-- JSON-LD --}}
  <div class="border rounded p-4">
    <h3 class="font-semibold mb-2">JSON-LD</h3>
    <textarea
      name="jsonld_raw"
      rows="8"
      class="w-full border p-2 rounded font-mono"
      placeholder='{"@context":"https://schema.org","@type":"Organization","name":"..."}'
    >{{ old('jsonld_raw', isset($item->jsonld) ? json_encode($item->jsonld, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) : '') }}</textarea>
    @error('jsonld_raw')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    <p class="text-xs text-gray-500 mt-2">
      Вставьте валидный JSON. При сохранении он будет распарсен; если парсинг неудачен или колонка <code>jsonld</code> отсутствует — значение будет проигнорировано.
    </p>
  </div>

  <button class="px-4 py-2 bg-blue-600 text-white rounded">Сохранить</button>
</form>
