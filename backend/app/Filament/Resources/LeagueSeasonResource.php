<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeagueSeasonResource\Pages;
use App\Models\LeagueSeason;
use App\Services\LeagueStandings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class LeagueSeasonResource extends Resource
{
    protected static ?string $model = LeagueSeason::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'League';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Seasons and table';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Season')->columns(2)->schema([
                Forms\Components\TextInput::make('name')->required()->maxLength(120)->placeholder('2026/27 Season'),
                Forms\Components\TextInput::make('age_group')->maxLength(60)->placeholder('Under-17'),
                Forms\Components\Toggle::make('is_current')
                    ->label('Show this season on the website')
                    ->helperText('Only one season is shown at a time; turning this on turns it off for the others.')
                    ->columnSpanFull(),
                Forms\Components\Select::make('teams')
                    ->relationship('teams', 'name')
                    ->multiple()->preload()->searchable()
                    ->helperText('Teams in this season appear in the table even before their first result.')
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Competition rules')->columns(3)->schema([
                Forms\Components\TextInput::make('points_win')->label('Points for a win')->numeric()->minValue(0)->maxValue(10)->default(3)->required(),
                Forms\Components\TextInput::make('points_draw')->label('Points for a draw')->numeric()->minValue(0)->maxValue(10)->default(1)->required(),
                Forms\Components\TextInput::make('points_loss')->label('Points for a loss')->numeric()->minValue(0)->maxValue(10)->default(0)->required(),
                Forms\Components\Repeater::make('tiebreakers')
                    ->label('When teams are level on points, separate them by (in order)')
                    ->simple(Forms\Components\Select::make('rule')->options(LeagueSeason::TIEBREAKERS)->required()->distinct())
                    ->default(['goal_difference', 'goals_for'])
                    ->reorderable()->addActionLabel('Add tie-breaker')->maxItems(count(LeagueSeason::TIEBREAKERS))
                    ->helperText('If teams are still level after these, they are listed alphabetically.')
                    ->columnSpanFull(),
            ]),
            Forms\Components\Section::make('Table preview')
                ->description('Calculated from published, played matches with the rules above (save to refresh).')
                ->hiddenOn('create')
                ->schema([
                    Forms\Components\Placeholder::make('standings')->hiddenLabel()
                        ->content(fn (?LeagueSeason $record) => $record ? self::standingsHtml($record) : ''),
                ]),
        ]);
    }

    private static function standingsHtml(LeagueSeason $season): HtmlString
    {
        $rows = app(LeagueStandings::class)->for($season->load('teams'));
        if (! $rows) {
            return new HtmlString('<p>No teams or published results yet.</p>');
        }
        $e = fn ($v) => e((string) $v);
        $html = '<table style="width:100%;font-variant-numeric:tabular-nums"><thead><tr><th style="text-align:left">#</th><th style="text-align:left">Team</th><th>P</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>GD</th><th>Pts</th></tr></thead><tbody>';
        foreach ($rows as $i => $r) {
            $html .= '<tr><td>'.($i + 1).'</td><td>'.$e($r['team']).'</td>';
            foreach (['p', 'w', 'd', 'l', 'gf', 'ga', 'gd', 'pts'] as $k) {
                $html .= '<td style="text-align:center">'.$e($r[$k]).'</td>';
            }
            $html .= '</tr>';
        }

        return new HtmlString($html.'</tbody></table>');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('age_group'),
                Tables\Columns\IconColumn::make('is_current')->label('On website')->boolean(),
                Tables\Columns\TextColumn::make('teams_count')->counts('teams')->label('Teams'),
                Tables\Columns\TextColumn::make('matches_count')->counts('matches')->label('Matches'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->modalDescription('Deleting a season also deletes all of its matches.'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSeasons::route('/'),
            'create' => Pages\CreateLeagueSeason::route('/create'),
            'edit' => Pages\EditLeagueSeason::route('/{record}/edit'),
        ];
    }
}
