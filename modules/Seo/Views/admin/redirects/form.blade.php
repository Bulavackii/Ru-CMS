@extends('layouts.admin')

@section('content')
<h1 class="text-2xl font-semibold mb-4">Правило редиректа</h1>

@if ($errors->any())
  <div class="mb-4 p-3 rounded border border-red-300 bg-red-50 text-red-800">
    <strong>Проверьте поля:</strong> {{ $errors->first() }}
  </div>
@endif

<form method="post"
      action="{{ isset($item) ? route('seo.redirects.update', $item->id) : route('seo.redirects.store') }}"
      class="grid lg:grid-cols-3 gap-6">
  @csrf
  @if(isset($item)) @method('PUT') @endif

  {{-- Левая колонка: форма --}}
  <div class="lg:col-span-2 space-y-5">

    {{-- From --}}
    <div>
      <label class="block text-sm font-medium">
        From <span class="text-xs text-gray-500">(путь «/old» или полный URL)</span>
      </label>
      <input name="from"
             value="{{ old('from', $item->from ?? '') }}"
             class="mt-1 border p-2 rounded w-full font-mono"
             maxlength="1024"
             required
             placeholder="/old-path или https://site.ru/old-path">
      @error('from')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    {{-- To --}}
    <div>
      <label class="block text-sm font-medium">
        To <span class="text-xs text-gray-500">(для кода 410 не требуется)</span>
      </label>
      <input id="toField"
             name="to"
             value="{{ old('to', $item->to ?? '') }}"
             class="mt-1 border p-2 rounded w-full font-mono"
             maxlength="1024"
             placeholder="/new-path или https://site.ru/new-path">
      @error('to')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    {{-- Code / RegExp --}}
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-sm font-medium">Code</label>
        <select id="codeField" name="code" class="mt-1 border p-2 rounded w-full">
          @foreach(['301','302','410'] as $c)
            <option value="{{ $c }}" @selected(old('code', $item->code ?? '301')==$c)>{{ $c }}</option>
          @endforeach
        </select>
        @error('code')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
        <p class="text-xs text-gray-500 mt-1">
          301 — постоянный, 302 — временный, 410 — ресурс удалён (To не нужен).
        </p>
      </div>

      <div>
        {{-- скрытый инпут, чтобы при снятом чекбоксе отправлялся 0 --}}
        <input type="hidden" name="is_regex" value="0">
        <label class="inline-flex items-center mt-1">
          <input type="checkbox"
                 id="regexField"
                 name="is_regex"
                 value="1"
                 {{ old('is_regex', $item->is_regex ?? false) ? 'checked':'' }}
                 class="mr-2">
          RegExp
        </label>
        @error('is_regex')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
        <p class="text-xs text-gray-500 mt-1">
          При RegExp поле <em>From</em> — шаблон PCRE. В <em>To</em> можно использовать подстановки <code>$1</code>, <code>$2</code> и т.д.
        </p>
      </div>
    </div>

    {{-- Priority --}}
    <div>
      <label class="block text-sm font-medium">
        Priority <span class="text-xs text-gray-500">(0–1000, <strong>чем больше — тем раньше</strong>)</span>
      </label>
      <input type="number"
             name="priority"
             value="{{ old('priority', $item->priority ?? 100) }}"
             class="mt-1 border p-2 rounded w-full"
             min="0" max="1000" step="1">
      @error('priority')<div class="text-red-600 text-xs mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="flex items-center gap-3">
      <button class="px-4 py-2 bg-blue-600 text-white rounded">Сохранить</button>
      <a href="{{ route('seo.redirects.index') }}" class="px-4 py-2 bg-gray-200 rounded">Отмена</a>
    </div>
  </div>

  {{-- Правая колонка: мини-тестер правила --}}
  <aside class="space-y-3">
    <div class="p-3 rounded border bg-white">
      <div class="font-semibold mb-2">Проверить правило</div>
      <label class="block text-sm mb-1">Тестовый URL</label>
      <input id="testUrl" class="border p-2 rounded w-full font-mono" placeholder="https://site.ru/old-path?x=1">
      <div class="flex items-center gap-2 mt-2">
        <button type="button" id="btnTest" class="px-3 py-2 border rounded hover:bg-gray-50">Проверить</button>
        <button type="button" id="btnReset" class="px-3 py-2 border rounded hover:bg-gray-50">Сброс</button>
      </div>
      <div class="mt-3 text-xs text-gray-600 space-y-1">
        <div><strong>Результат:</strong> <span id="testResult" class="font-mono"></span></div>
        <div><strong>Итоговый To:</strong> <span id="testTo" class="font-mono"></span></div>
      </div>
      <hr class="my-3">
      <div class="text-xs text-gray-500">
        Советы: для точного совпадения начинайте с <code>^</code> и заканчивайте <code>$</code>.<br>
        Пример: <code>^/news/(.*)$</code> → <code>/novosti/$1</code>
      </div>
    </div>
  </aside>
</form>

{{-- Небольшая прогрессивная логика --}}
<script>
  (function(){
    var code = document.getElementById('codeField');
    var to   = document.getElementById('toField');

    function toggleTo(){
      if (!code || !to) return;
      var is410 = code.value === '410';
      // Используем readOnly (а не disabled), чтобы значение всё равно отправлялось (пустая строка)
      to.readOnly = is410;
      to.classList.toggle('bg-gray-50', is410);
      if (is410) to.value = '';
    }

    if (code) {
      code.addEventListener('change', toggleTo);
      toggleTo();
    }

    // Мини-тестер
    var btnTest  = document.getElementById('btnTest');
    var btnReset = document.getElementById('btnReset');
    var testUrl  = document.getElementById('testUrl');
    var testRes  = document.getElementById('testResult');
    var testTo   = document.getElementById('testTo');
    var regexChk = document.getElementById('regexField');

    function norm(u){
      try { return (u || '').trim(); } catch(e){ return ''; }
    }

    function buildTarget(from, toVal, url, isRegex){
      if (!from) return '';
      if (code.value === '410') return '(410 Gone)';

      if (isRegex) {
        try {
          var re = new RegExp(from);
          var m  = url.match(re);
          if (!m) return '(не совпало)';
          // поддержка $1...$9
          var replaced = toVal.replace(/\$(\d)/g, function(_, g){ return m[g] || ''; });
          return replaced || '(пусто)';
        } catch(e) {
          return '(ошибка RegExp: ' + e.message + ')';
        }
      } else {
        return toVal || '(пусто)';
      }
    }

    if (btnTest) {
      btnTest.addEventListener('click', function(){
        var f  = norm(document.querySelector('input[name="from"]').value);
        var t  = norm(to.value);
        var u  = norm(testUrl.value);
        var r  = !!(regexChk && regexChk.checked);

        if (!u) { testRes.textContent = 'Укажите тестовый URL'; testTo.textContent = ''; return; }

        var finalTo = buildTarget(f, t, u, r);
        testRes.textContent = r ? 'RegExp: ' + (new RegExp(f)).toString() : 'Строгое совпадение по пути';
        testTo.textContent  = finalTo;
      });
    }

    if (btnReset) {
      btnReset.addEventListener('click', function(){
        testUrl.value = '';
        testRes.textContent = '';
        testTo.textContent  = '';
      });
    }
  })();
</script>
@endsection
