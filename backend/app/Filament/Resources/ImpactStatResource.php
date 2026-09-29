<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImpactStatResource\Pages;
use App\Models\ImpactStat;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ImpactStatResource extends Resource
{
    protected static ?string $model = ImpactStat::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Community impact stats';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('value')->required()->placeholder('XX+ or ₦X.XM until confirmed'),
            Forms\Components\TextInput::make('label')->required()->placeholder('Players supported'),
            Forms\Components\Toggle::make('is_confirmed')
                ->label('This is a real, confirmed figure')
                ->helperText('Leave off while "value" is still a placeholder like "XX+".')
                ->default(false),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\TextColumn::make('value'),
                Tables\Columns\TextColumn::make('label'),
                Tables\Columns\IconColumn::make('is_confirmed')->boolean(),
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
            'index' => Pages\ListImpactStats::route('/'),
            'create' => Pages\CreateImpactStat::route('/create'),
            'edit' => Pages\EditImpactStat::route('/{record}/edit'),
        ];
    }
}
