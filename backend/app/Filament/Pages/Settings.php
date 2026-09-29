<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

// Single key/value settings form — mirrors the SITE object at the top of js/data.js
// (contact details, WhatsApp number, socials, league season name) so these can be edited
// without touching code. Keys map 1:1 to SiteSetting rows; see SiteSetting::get()/put().
class Settings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static string $view = 'filament.pages.settings';

    public ?array $data = [];

    public function mount(): void
    {
        $keys = [
            'site_name', 'domain', 'tagline', 'email', 'phone', 'whatsapp',
            'address', 'hours', 'currency', 'league_season',
            'social_instagram', 'social_x', 'social_youtube', 'social_facebook',
        ];

        $this->data = collect($keys)
            ->mapWithKeys(fn ($key) => [$key => SiteSetting::get($key)])
            ->toArray();

        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Section::make('Academy details')->schema([
                    TextInput::make('site_name')->label('Site name')->required(),
                    TextInput::make('domain'),
                    TextInput::make('tagline'),
                    TextInput::make('league_season')->placeholder('2026/27 Season'),
                    TextInput::make('currency')->placeholder('₦')->maxLength(3),
                ])->columns(2),
                Section::make('Contact details')->schema([
                    TextInput::make('email')->email(),
                    TextInput::make('phone'),
                    TextInput::make('whatsapp')->helperText('Digits only, with country code — e.g. 2348000000000.'),
                    TextInput::make('address'),
                    TextInput::make('hours')->placeholder('Mon–Sat, 7:00 – 18:00'),
                ])->columns(2),
                Fieldset::make('Social links')->schema([
                    TextInput::make('social_instagram')->label('Instagram')->url(),
                    TextInput::make('social_x')->label('X')->url(),
                    TextInput::make('social_youtube')->label('YouTube')->url(),
                    TextInput::make('social_facebook')->label('Facebook')->url(),
                ])->columns(2),
            ]);
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($state as $key => $value) {
            SiteSetting::put($key, $value);
        }

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
