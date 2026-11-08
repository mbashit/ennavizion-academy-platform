<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeagueMatchResource\Pages;
use App\Models\LeagueMatch;
use App\Models\LeagueSeason;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// One screen for fixtures and results: a match is a fixture until it's marked "Played" with
// both scores. "Publish on the website" is the approval step — unpublished matches are
// invisible to visitors and don't count toward the table.
class LeagueMatchResource extends Resource
{
    protected static ?string $model = LeagueMatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'League';
    protected static ?int $navigationSort = 3;
    protected static ?string $navigationLabel = 'Fixtures and results';
    protected static ?string $modelLabel = 'match';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(3)->schema([
                Forms\Components\Select::make('league_season_id')->label('Season')
                    ->relationship('season', 'name')->required()->preload()
                    ->default(fn () => LeagueSeason::current()?->id),
                Forms\Components\DatePicker::make('date')->required(),
                Forms\Components\TimePicker::make('time')->seconds(false),
                Forms\Components\TextInput::make('venue')->maxLength(120)->columnSpan(3),
                Forms\Components\Select::make('home_team_id')->label('Home team')
                    ->relationship('homeTeam', 'name')->required()->searchable()->preload()->columnSpan(2),
                Forms\Components\TextInput::make('home_score')->label('Home score')->numeric()->minValue(0)->maxValue(99)
                    ->requiredIf('status', 'played')->validationMessages(['required_if' => 'Enter both scores for a played match.']),
                Forms\Components\Select::make('away_team_id')->label('Away team')
                    ->relationship('awayTeam', 'name')->required()->searchable()->preload()->columnSpan(2)
                    ->different('home_team_id')->validationMessages(['different' => 'Home and away team must be different.']),
                Forms\Components\TextInput::make('away_score')->label('Away score')->numeric()->minValue(0)->maxValue(99)
                    ->requiredIf('status', 'played')->validationMessages(['required_if' => 'Enter both scores for a played match.']),
                Forms\Components\Select::make('status')->options(LeagueMatch::STATUSES)->default('scheduled')->required()->live(),
                Forms\Components\Toggle::make('is_published')->label('Publish on the website')
                    ->helperText(fn (Get $get) => $get('status') === 'played'
                        ? 'Publishing a played match adds its result to the league table.'
                        : 'Published fixtures appear in "Upcoming fixtures".')
                    ->columnSpan(2),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('homeTeam.name')->label('Home'),
                Tables\Columns\TextColumn::make('score')->label('Score')
                    ->state(fn (LeagueMatch $m) => $m->status === 'played' ? "{$m->home_score} – {$m->away_score}" : 'vs'),
                Tables\Columns\TextColumn::make('awayTeam.name')->label('Away'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state) => LeagueMatch::STATUSES[$state] ?? $state),
                Tables\Columns\IconColumn::make('is_published')->label('Published')->boolean(),
                Tables\Columns\TextColumn::make('season.name')->label('Season')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('league_season_id')->label('Season')->relationship('season', 'name'),
                Tables\Filters\SelectFilter::make('status')->options(LeagueMatch::STATUSES),
                Tables\Filters\TernaryFilter::make('is_published')->label('Published'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('publish')->label('Publish')->icon('heroicon-o-eye')
                        ->action(fn ($records) => $records->each->update(['is_published' => true])),
                    Tables\Actions\BulkAction::make('unpublish')->label('Unpublish')->icon('heroicon-o-eye-slash')
                        ->action(fn ($records) => $records->each->update(['is_published' => false])),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMatches::route('/'),
            'create' => Pages\CreateLeagueMatch::route('/create'),
            'edit' => Pages\EditLeagueMatch::route('/{record}/edit'),
        ];
    }
}
