<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeagueFixtureResource\Pages;
use App\Models\LeagueFixture;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeagueFixtureResource extends Resource
{
    protected static ?string $model = LeagueFixture::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'League';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date')->required(),
            Forms\Components\TimePicker::make('time')->seconds(false)->required(),
            Forms\Components\Select::make('home_team_id')
                ->relationship('homeTeam', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\Select::make('away_team_id')
                ->relationship('awayTeam', 'name')
                ->required()
                ->searchable()
                ->preload()
                ->different('home_team_id')
                ->validationMessages(['different' => 'Home and away team must be different.']),
            Forms\Components\TextInput::make('venue')->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date')
            ->columns([
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('time'),
                Tables\Columns\TextColumn::make('homeTeam.name')->label('Home')->sortable(),
                Tables\Columns\TextColumn::make('awayTeam.name')->label('Away')->sortable(),
                Tables\Columns\TextColumn::make('venue')->searchable(),
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
            'index' => Pages\ListLeagueFixtures::route('/'),
            'create' => Pages\CreateLeagueFixture::route('/create'),
            'edit' => Pages\EditLeagueFixture::route('/{record}/edit'),
        ];
    }
}
