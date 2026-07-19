<div>
    <label class="block text-sm font-medium mb-1">{{ $label ?? '' }}</label>
    <div class="flex items-center gap-3">
        <input type="color"
               value="{{ $value ?? '#ffffff' }}"
               class="color-input border rounded"
               oninput="this.nextElementSibling.value=this.value;window.__syncThemeVars()">
        <input type="text"
               name="{{ $name ?? '' }}"
               value="{{ $value ?? '#ffffff' }}"
               class="border rounded px-3 py-2 w-full"
               oninput="this.previousElementSibling.value=this.value;window.__syncThemeVars()">
    </div>
</div>
