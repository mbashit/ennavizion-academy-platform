<?php

namespace App\Filament\Portal\Pages\Auth;

use App\Models\User;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * Parent sign-up. Adds a phone number and a guardian confirmation to Filament's registration,
 * and always creates a *parent* account — the role is set here, never from form input.
 */
class Register extends BaseRegister
{
    public function form(Form $form): Form
    {
        return $form->schema([
            $this->getNameFormComponent()->label('Your full name'),
            $this->getEmailFormComponent(),
            TextInput::make('phone')->label('Phone number')->tel()->required()
                ->regex('/^[+0-9 ()\-]{7,30}$/')->maxLength(30)
                ->validationMessages(['regex' => 'Use digits, spaces, + or brackets.']),
            $this->getPasswordFormComponent(),
            $this->getPasswordConfirmationFormComponent(),
            Checkbox::make('guardian_confirmed')->required()->accepted()->dehydrated(false)
                ->label(new HtmlString('I\'m a parent or legal guardian aged 18 or over, and I\'ve read the <a href="'.e(rtrim(config('services.frontend.url'), '/')).'/privacy.html" target="_blank" rel="noopener" style="text-decoration:underline">privacy notice</a>.')),
        ]);
    }

    protected function handleRegistration(array $data): Model
    {
        $user = new User;
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => $data['password'],
        ]);
        $user->role = User::PARENT;
        $user->save();

        return $user;
    }
}
