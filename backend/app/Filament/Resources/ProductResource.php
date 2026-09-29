<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Shop';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('sku')->required()->unique(ignoreRecord: true)
                ->helperText('Internal reference code, e.g. ENV-KIT-HOME-26.'),
            Forms\Components\Select::make('tag')
                ->options(['Kit' => 'Kit', 'Training' => 'Training', 'Equipment' => 'Equipment', 'Merch' => 'Merch'])
                ->required(),
            Forms\Components\TextInput::make('price')->required()->numeric()->minValue(0)->prefix('₦'),
            Forms\Components\TagsInput::make('colors')
                ->placeholder('Add a colour and press Enter')
                ->helperText('Leave empty for a single-colour/one-size item.'),
            Forms\Components\FileUpload::make('image_path')
                ->label('Product photo')
                ->image()
                ->directory('products')
                ->imageEditor()
                ->imageCropAspectRatio('1:1'),
            Forms\Components\Toggle::make('in_stock')->default(true)
                ->helperText('Off shows "Currently unavailable" and disables Add to cart on the site.'),
            Forms\Components\TextInput::make('sort_order')->numeric()->default(0)
                ->helperText('Lower numbers show first.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')->label('')->square(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('sku')->label('SKU')->toggleable(),
                Tables\Columns\TextColumn::make('tag')->badge(),
                Tables\Columns\TextColumn::make('price')->money('NGN')->sortable(),
                Tables\Columns\IconColumn::make('in_stock')->label('In stock')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tag')
                    ->options(['Kit' => 'Kit', 'Training' => 'Training', 'Equipment' => 'Equipment', 'Merch' => 'Merch']),
                Tables\Filters\TernaryFilter::make('in_stock'),
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
