<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeagueResultResource\Pages;
use App\Models\LeagueResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeagueResultResource extends Resource
{
    protected static ?string $model = LeagueResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationGroup = 'League';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')->required(),
            Forms\Components\Select::make('home_team_id')
                ->relationship('homeTeam', 'name')
                ->required()->searchable()->preload(),
            Forms\Components\TextInput::make('home_score')->required()->numeric()->minValue(0),
            Forms\Components\Select::make('away_team_id')
                ->relationship('awayTeam', 'name')
                ->required()->searchable()->preload()
                ->different('home_team_id')
                ->validationMessages(['different' => 'Home and away team must be different.']),
            Forms\Components\TextInput::make('away_score')->required()->numeric()->minValue(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('homeTeam.name')->label('Home')->sortable(),
                Tables\Columns\TextColumn::make('home_score')->label('H'),
                Tables\Columns\TextColumn::make('away_score')->label('A'),
                Tables\Columns\TextColumn::make('awayTeam.name')->label('Away')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeagueResults::route('/'),
            'create' => Pages\CreateLeagueResult::route('/create'),
            'edit' => Pages\EditLeagueResult::route('/{record}/edit'),
        ];
    }
}
