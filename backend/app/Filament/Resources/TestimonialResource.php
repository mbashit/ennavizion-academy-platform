<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TestimonialResource\Pages;
use App\Models\Testimonial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TestimonialResource extends Resource
{
    protected static ?string $model = Testimonial::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('quote')->required()->rows(3),
            Forms\Components\TextInput::make('name')->required(),
            Forms\Components\TextInput::make('role')->required()->placeholder('Parent of an Elite Pathway player'),
            Forms\Components\FileUpload::make('image_path')
                ->label('Photo')
                ->image()
                ->disk('public') // served to the static site via the /storage symlink
                ->directory('testimonials')
                ->imageEditor()
                ->imageCropAspectRatio('1:1'),
            Forms\Components\Section::make('Before this goes live')
                ->description('A clearly-worded placeholder can be published while unverified. A specific success story (a name, a claimed trial or scholarship) should stay unpublished until verified.')
                ->schema([
                    Forms\Components\Toggle::make('is_verified')
                        ->label('This is a real, consented quote')
                        ->helperText('The named person actually said this and agreed to it being published.')
                        ->default(false),
                    Forms\Components\Toggle::make('is_published')
                        ->label('Show on the public site')
                        ->default(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quote')->limit(50),
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('role'),
                Tables\Columns\IconColumn::make('is_verified')->boolean(),
                Tables\Columns\IconColumn::make('is_published')->boolean(),
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
            'index' => Pages\ListTestimonials::route('/'),
            'create' => Pages\CreateTestimonial::route('/create'),
            'edit' => Pages\EditTestimonial::route('/{record}/edit'),
        ];
    }
}
