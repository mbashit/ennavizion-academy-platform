<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PostResource\Pages;
use App\Models\Post;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PostResource extends Resource
{
    protected static ?string $model = Post::class;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'News';
    protected static ?string $modelLabel = 'news article';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $state, Forms\Set $set, string $operation) =>
                        $operation === 'create' ? $set('slug', Str::slug($state)) : null),
                Forms\Components\TextInput::make('slug')->required()->unique(ignoreRecord: true),
                Forms\Components\Grid::make(3)->schema([
                    Forms\Components\DatePicker::make('published_at')->required()->default(now()),
                    // Free text, not a fixed list — categories are whatever's actually in use
                    // across posts, same as the original js/data.js behaviour.
                    Forms\Components\TextInput::make('category')->required()->placeholder('Academy, Matches, Community…'),
                    Forms\Components\TextInput::make('minutes')->label('Read time (min)')->numeric()->minValue(1)->default(3)->required(),
                ]),
                Forms\Components\Textarea::make('excerpt')->required()->maxLength(500)->rows(2)->columnSpanFull(),
                Forms\Components\RichEditor::make('body')->required()->columnSpanFull(),
                Forms\Components\FileUpload::make('image_path')
                    ->label('Image')
                    ->image()
                    ->disk('public') // served to the static site via the /storage symlink
                    ->directory('news')
                    ->imageEditor()
                    ->imageCropAspectRatio('16:10')
                    ->helperText('16:10 crop recommended, matches the news card aspect ratio.'),
                Forms\Components\TextInput::make('image_alt')->label('Image alt text')
                    ->helperText('Describe what the photo shows — not "image" or the filename.'),
                Forms\Components\Toggle::make('is_published')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('published_at', 'desc')
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')->label(''),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('category')->badge(),
                Tables\Columns\TextColumn::make('published_at')->date()->sortable(),
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
            'index' => Pages\ListPosts::route('/'),
            'create' => Pages\CreatePost::route('/create'),
            'edit' => Pages\EditPost::route('/{record}/edit'),
        ];
    }
}
