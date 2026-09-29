<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeagueTeamResource\Pages;
use App\Models\LeagueTeam;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeagueTeamResource extends Resource
{
    protected static ?string $model = LeagueTeam::class;

    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'League';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationLabel = 'Table';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required()->unique(ignoreRecord: true),
            Forms\Components\Toggle::make('is_ennavizion')
                ->label('This is our team')
                ->helperText('Highlights this row in the public league table.'),
            Forms\Components\Grid::make(5)->schema([
                Forms\Components\TextInput::make('played')->numeric()->minValue(0)->default(0)->required(),
                Forms\Components\TextInput::make('won')->numeric()->minValue(0)->default(0)->required(),
                Forms\Components\TextInput::make('drawn')->numeric()->minValue(0)->default(0)->required(),
                Forms\Components\TextInput::make('lost')->numeric()->minValue(0)->default(0)->required(),
            ]),
            Forms\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('goals_for')->label('Goals for')->numeric()->minValue(0)->default(0)->required(),
                Forms\Components\TextInput::make('goals_against')->label('Goals against')->numeric()->minValue(0)->default(0)->required(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('won', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\IconColumn::make('is_ennavizion')->label('Us')->boolean(),
                Tables\Columns\TextColumn::make('played')->label('P'),
                Tables\Columns\TextColumn::make('won')->label('W'),
                Tables\Columns\TextColumn::make('drawn')->label('D'),
                Tables\Columns\TextColumn::make('lost')->label('L'),
                Tables\Columns\TextColumn::make('goal_difference')->label('GD'),
                Tables\Columns\TextColumn::make('points')->label('Pts')->weight('bold'),
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
            'index' => Pages\ListLeagueTeams::route('/'),
            'create' => Pages\CreateLeagueTeam::route('/create'),
            'edit' => Pages\EditLeagueTeam::route('/{record}/edit'),
        ];
    }
}
