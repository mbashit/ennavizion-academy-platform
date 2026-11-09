<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\PlayerResource\Pages;
use App\Models\Player;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** "My players": a parent's own children. Never shows another family's records. */
class PlayerResource extends Resource
{
    protected static ?string $model = Player::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'My players';
    protected static ?string $modelLabel = 'player';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Player details')->columns(2)->schema([
                Forms\Components\TextInput::make('first_name')->required()->maxLength(100)->notRegex('/[<>]/'),
                Forms\Components\TextInput::make('last_name')->required()->maxLength(100)->notRegex('/[<>]/'),
                Forms\Components\DatePicker::make('dob')->label('Date of birth')->required()
                    ->maxDate(now()->subYears(3))->minDate(now()->subYears(25)),
                Forms\Components\Select::make('position')->label('Preferred position')
                    ->options(array_combine(Player::POSITIONS, Player::POSITIONS)),
                Forms\Components\TextInput::make('current_club')->label('Current club or school team')->maxLength(255)->notRegex('/[<>]/')->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Medical needs and injuries')
                ->description('Optional. Stored encrypted and seen only by academy staff who need it for your child\'s safety.')
                ->schema([
                    Forms\Components\Textarea::make('medical_notes')->hiddenLabel()->rows(3)->maxLength(2000),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No players yet')
            ->emptyStateDescription('Add your child to apply for a trial.')
            ->columns([
                Tables\Columns\TextColumn::make('full_name')->label('Name'),
                Tables\Columns\TextColumn::make('dob')->label('Date of birth')->date('j M Y'),
                Tables\Columns\TextColumn::make('age')->label('Age'),
                Tables\Columns\TextColumn::make('position')->placeholder('—'),
                Tables\Columns\TextColumn::make('applications_count')->counts('applications')->label('Applications'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlayers::route('/'),
            'create' => Pages\CreatePlayer::route('/create'),
            'edit' => Pages\EditPlayer::route('/{record}/edit'),
        ];
    }
}
