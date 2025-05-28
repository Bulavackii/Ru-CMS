<?php

namespace Modules\Accessibility\View\Components;

use Illuminate\View\Component;
use Modules\Accessibility\Models\AccessibilitySetting;

class AccessibilityWidget extends Component
{
    public $settings;

    /**
     * Создание нового экземпляра компонента.
     */
    public function __construct()
    {
        $this->settings = AccessibilitySetting::settings();
    }

    /**
     * Получить представление / содержимое компонента.
     */
    public function render()
    {
        return view('Accessibility::frontend.widget', [
            'settings' => $this->settings,
        ]);
    }
}
