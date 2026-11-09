<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

// Lets the client add and remove their own staff logins. Developer accounts (is_developer)
// are invisible here unless you're a developer yourself — see UserPolicy for the rules.
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Admin users';
    protected static ?string $modelLabel = 'admin user';
    protected static ?int $navigationSort = 100;

    public static function getEloquentQuery(): Builder
    {
        // Staff accounts only — parent-portal accounts are not admin users.
        $query = parent::getEloquentQuery()->where('role', \App\Models\User::STAFF);

        return auth()->user()?->is_developer ? $query : $query->where('is_developer', false);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(255),
            Forms\Components\TextInput::make('email')->email()->required()->maxLength(255)
                ->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')
                ->password()
                ->revealable()
                ->minLength(8)
                ->required(fn (string $operation) => $operation === 'create')
                // Blank on edit = keep the current password. Hashed by the User model's cast.
                ->dehydrated(fn (?string $state) => filled($state))
                ->helperText(fn (string $operation) => $operation === 'edit' ? 'Leave blank to keep the current password.' : null),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\IconColumn::make('is_developer')->label('Developer')->boolean()
                    ->visible(fn () => auth()->user()?->is_developer),
                Tables\Columns\TextColumn::make('created_at')->label('Added')->date(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
