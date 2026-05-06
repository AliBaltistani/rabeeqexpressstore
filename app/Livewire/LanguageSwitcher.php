<?php

namespace App\Livewire;

use App\Models\Language;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    public string $currentLocale = 'en';

    public function mount(): void
    {
        $this->currentLocale = App::getLocale();
    }

    public function switchLanguage(string $code): void
    {
        // Validate the language code exists and is active
        $language = Language::where('code', $code)->where('is_active', true)->first();

        if (!$language) {
            return;
        }

        // Set the session language
        Session::put('language', $code);

        // Determine RTL from language record
        $isRtl = strtoupper($language->direction) === 'RTL';
        Session::put('is_rtl', $isRtl);

        // Update the admin's language preference in DB
        $admin = auth()->guard('admin')->user();
        if ($admin) {
            $admin->update(['language_preference' => $code]);
        }

        // Set locale immediately
        App::setLocale($code);
        $this->currentLocale = $code;

        // Full redirect to apply RTL/LTR direction change
        $this->redirect(request()->header('Referer', '/admin'), navigate: true);
    }

    public function render()
    {
        $languages = Language::where('is_active', true)->get();

        return view('livewire.language-switcher', [
            'languages' => $languages,
        ]);
    }
}
