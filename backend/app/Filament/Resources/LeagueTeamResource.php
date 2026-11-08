<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeagueTeamResource\Pages;
use App\Models\LeagueTeam;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Teams only hold a name and whether they're Ennavizion's own. Their record in the table
// is calculated from published match results — see LeagueStandings.
class LeagueTeamResource extends Resource
{
    protected static ?string $model = LeagueTeam::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'League';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationLabel = 'Teams';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->maxLength(120)->unique(ignoreRecord: true),
            Forms\Components\Toggle::make('is_ennavizion')
                ->label("This is one of Ennavizion's teams")
                ->helperText('Highlights this team in the public league table.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\IconColumn::make('is_ennavizion')->label('Ennavizion')->boolean(),
                Tables\Columns\TextColumn::make('seasons.name')->label('Seasons')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Deleting a team also deletes all of its matches.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeagueTeams::route('/'),
            'create' => Pages\CreateLeagueTeam::route('/create'),
            'edit' => Pages\EditLeagueTeam::route('/{record}/edit'),
        ];
    }
}
