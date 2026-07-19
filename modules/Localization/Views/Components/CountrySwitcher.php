<?php

namespace Modules\Localization\Views\Components;

use Illuminate\View\Component;
use Modules\Localization\Services\LocalizationService;

class CountrySwitcher extends Component
{
    public $countries;
    public $currentCountry;
    public $localizationService;

    public function __construct(LocalizationService $localizationService)
    {
        $this->localizationService = $localizationService;
        $this->countries = $localizationService->getCountries();
        
        // Определить текущую страну
        $countryCode = session('country_code', config('localization.default_country', 'RU'));
        $this->currentCountry = $localizationService->getCountryByCode($countryCode);
    }

    public function render()
    {
        return view('Localization::components.country-switcher');
    }
}




