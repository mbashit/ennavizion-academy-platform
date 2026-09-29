<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionResource\Pages;
use App\Models\Submission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

// Submissions arrive from the public site's forms (contact/enrol/donate/newsletter) — there's
// deliberately no "create" page here, only viewing and marking status, same as admin.html's
// old localStorage-based demo viewer but backed by a real database.
class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationLabel = 'Submissions';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('type')->disabled(),
            Forms\Components\KeyValue::make('data')
                ->label('Submitted data')
                ->disabled()
                ->columnSpanFull(),
            Forms\Components\Select::make('status')
                ->options(['new' => 'New', 'reviewed' => 'Reviewed', 'actioned' => 'Actioned'])
                ->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('data')
                    ->label('Summary')
                    ->state(fn (Submission $record) => collect($record->data)
                        ->only(['name', 'donor_name', 'guardian_name', 'player_first', 'email', 'donor_email', 'guardian_email'])
                        ->filter()
                        ->implode(' · '))
                    ->limit(60),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state) => match ($state) {
                        'new' => 'warning',
                        'reviewed' => 'info',
                        'actioned' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['contact' => 'Contact', 'enrolment' => 'Enrolment', 'donation' => 'Donation', 'newsletter' => 'Newsletter']),
                Tables\Filters\SelectFilter::make('status')
                    ->options(['new' => 'New', 'reviewed' => 'Reviewed', 'actioned' => 'Actioned']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('View / update status'),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubmissions::route('/'),
            'edit' => Pages\EditSubmission::route('/{record}/edit'),
        ];
    }
}
