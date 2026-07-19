@props([
  'name'  => '',
  'label' => '',
  'value' => '#ffffff',
])

<div>
  @if($label)
    <label class="block text-sm font-medium mb-1">{{ $label }}</label>
  @endif

  <div class="flex items-center gap-3">
    <input
      type="color"
      value="{{ $value }}"
      class="h-10 w-14 p-0 border rounded"
      oninput="this.nextElementSibling.value=this.value;window.__syncThemeVars?.()">

    <input
      type="text"
      name="{{ $name }}"
      value="{{ $value }}"
      class="border rounded px-3 py-2 w-full"
      oninput="this.previousElementSibling.value=this.value;window.__syncThemeVars?.()">
  </div>
</div>
