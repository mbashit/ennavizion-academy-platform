<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GalleryItemResource\Pages;
use App\Models\GalleryItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class GalleryItemResource extends Resource
{
    protected static ?string $model = GalleryItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\FileUpload::make('image_path')
                ->label('Photo')
                ->image()
                ->disk('public') // served to the static site via the /storage symlink
                ->directory('gallery')
                ->imageEditor()
                ->imageCropAspectRatio('1:1')
                ->helperText('Square (1:1) crop recommended. Leave empty to show a placeholder on the site.'),
            Forms\Components\Select::make('category')
                ->options(['training' => 'Training', 'matches' => 'Matches', 'events' => 'Events'])
                ->required(),
            Forms\Components\TextInput::make('caption')->required()->maxLength(120),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0)
                ->helperText('Lower numbers show first.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')->label('Photo')->square(),
                Tables\Columns\TextColumn::make('caption')->searchable(),
                Tables\Columns\TextColumn::make('category')->badge(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->options(['training' => 'Training', 'matches' => 'Matches', 'events' => 'Events']),
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
            'index' => Pages\ListGalleryItems::route('/'),
            'create' => Pages\CreateGalleryItem::route('/create'),
            'edit' => Pages\EditGalleryItem::route('/{record}/edit'),
        ];
    }
}
