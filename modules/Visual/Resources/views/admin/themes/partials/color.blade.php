{{-- Поле цвета: образец и шестнадцатеричное значение рядом.

     Образец — настоящий input[type=color], а не картинка: системная палитра
     привычнее самодельной и умеет пипетку. Значение рядом набирается руками,
     когда цвет нужно вписать точно — из фирменного стиля, например. --}}
<div class="thm-color">
    <label class="thm-label">{{ $label ?? '' }}</label>

    <div class="thm-color__row">
        <input type="color"
               class="thm-color__dot"
               value="{{ $value ?? '#ffffff' }}"
               aria-label="{{ $label ?? '' }}"
               oninput="this.nextElementSibling.value=this.value;window.__syncThemeVars()">

        <input type="text"
               name="{{ $name ?? '' }}"
               value="{{ $value ?? '#ffffff' }}"
               class="admin-field thm-color__hex"
               spellcheck="false"
               oninput="this.previousElementSibling.value=this.value;window.__syncThemeVars()">
    </div>
</div>
