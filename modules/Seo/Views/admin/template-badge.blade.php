@php
  /**
   * Параметры (все необязательные):
   * - $label   string   Текст бейджа (по умолчанию 'SEO')
   * - $type    string   Стиль цвета: default|success|warning|muted|info|danger (default)
   * - $variant string   Вариант: solid|subtle|outline (subtle)
   * - $size    string   Размер: sm|md (sm)
   * - $pill    bool     Закругление как «пилюля» (true)
   * - $icon    string   Иконка: check|info|warn|dot (нет)
   * - $href    string   Если задан — рендерим <a> вместо <span>
   * - $title   string   Заголовок/подсказка (title и aria-label)
   * - $class   string   Доп. классы снаружи (добавятся в конец)
   */

  $label   = isset($label) && $label !== '' ? (string)$label : 'SEO';
  $type    = $type    ?? 'default';
  $variant = $variant ?? 'subtle';
  $size    = $size    ?? 'sm';
  $pill    = array_key_exists('pill', get_defined_vars()) ? (bool)$pill : true;
  $icon    = $icon    ?? null;
  $href    = $href    ?? null;
  $title   = $title   ?? null;
  $class   = $class   ?? '';

  // Палитры (base -> [bg, text, ring])
  $palette = [
    'default' => ['bg' => 'gray',  'text' => 'gray',  'ring' => 'gray'],
    'muted'   => ['bg' => 'gray',  'text' => 'gray',  'ring' => 'gray'],
    'success' => ['bg' => 'green', 'text' => 'green', 'ring' => 'green'],
    'warning' => ['bg' => 'amber', 'text' => 'amber', 'ring' => 'amber'],
    'info'    => ['bg' => 'blue',  'text' => 'blue',  'ring' => 'blue'],
    'danger'  => ['bg' => 'red',   'text' => 'red',   'ring' => 'red'],
  ];
  $p = $palette[$type] ?? $palette['default'];

  // Варианты оформления
  $variants = [
    'solid' => "bg-{$p['bg']}-600 text-white dark:bg-{$p['bg']}-500",
    'subtle'=> "bg-{$p['bg']}-100 text-{$p['text']}-800 dark:bg-{$p['bg']}-900 dark:text-{$p['text']}-200",
    'outline'=>"ring-1 ring-{$p['ring']}-300 text-{$p['text']}-700 dark:text-{$p['text']}-200 dark:ring-{$p['ring']}-700",
  ];
  $variantClasses = $variants[$variant] ?? $variants['subtle'];

  // Размеры
  $sizes = [
    'sm' => 'text-xs px-2 py-1',
    'md' => 'text-sm px-3 py-1.5',
  ];
  $sizeClasses = $sizes[$size] ?? $sizes['sm'];

  // Общие классы
  $rounded = $pill ? 'rounded-full' : 'rounded';
  $base = "inline-flex items-center gap-1 select-none {$rounded} {$sizeClasses} {$variantClasses} " .
          "transition focus:outline-none focus:ring-2 focus:ring-offset-1 dark:focus:ring-offset-0 " .
          "focus:ring-{$p['ring']}-300";

  // Иконка (необязательная, компактный SVG без внешних зависимостей)
  $icons = [
    'check' => 'M4.5 8.75 7 11.25l4.5-4.5',
    'info'  => 'M9 3.75a5.25 5.25 0 1 1 0 10.5a5.25 5.25 0 0 1 0-10.5Zm.75 3.75h-1.5v1.5h1.5V7.5Zm0 2.25h-1.5v3h1.5v-3Z',
    'warn'  => 'M8.257 3.099c.366-.73 1.42-.73 1.786 0l5.94 11.86c.33.66-.15 1.441-.893 1.441H3.21c-.743 0-1.223-.78-.893-1.44l5.94-11.86ZM9 6.75v3h-1.5v-3H9Zm0 5.25H7.5v1.5H9v-1.5Z',
    'dot'   => 'M9 7.5a1.5 1.5 0 1 1 0 3a1.5 1.5 0 0 1 0-3Z',
  ];
  $iconPath = $icons[$icon] ?? null;

  // Теги/атрибуты
  $tag = $href ? 'a' : 'span';
  $attrs = [
    'class' => trim($base . ' ' . $class),
    'title' => $title ?: $label,
    'aria-label' => $title ?: $label,
  ];
  if ($href) {
    $attrs['href'] = $href;
    // если абсолютная ссылка — откроем в новой вкладке
    if (preg_match('~^https?://~i', $href)) {
      $attrs['target'] = '_blank';
      $attrs['rel'] = 'noopener';
    }
  }

  // Рендер helper
  $renderAttrs = function(array $a) {
    return collect($a)->map(function($v, $k){
      return $v === null ? '' : $k.'="'.e($v, false).'"';
    })->implode(' ');
  };
@endphp

<{{ $tag }} {!! $renderAttrs($attrs) !!}>
  @if($iconPath)
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18" class="h-[0.95em] w-[0.95em]" aria-hidden="true" focusable="false">
      <path d="{{ $iconPath }}" fill="currentColor" stroke="currentColor" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  @endif
  <span>{{ e($label) }}</span>
</{{ $tag }}>
