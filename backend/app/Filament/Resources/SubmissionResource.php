<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubmissionResource\Pages;
use App\Models\Submission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

// Submissions arrive from the public site's forms. There's no "create" page: staff view a
// submission and track its status. Lists never show a child's name or medical details;
// medical notes are decrypted only on the single-submission screen.
class SubmissionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';
    protected static ?string $navigationLabel = 'Submissions';

    public const STATUSES = [
        'new' => 'New', 'reviewed' => 'Reviewed', 'actioned' => 'Actioned',
        'pending' => 'Payment pending', 'paid' => 'Paid', 'failed' => 'Payment failed', 'review' => 'Payment needs checking',
    ];

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()->columns(3)->schema([
                Forms\Components\TextInput::make('reference')->disabled(),
                Forms\Components\TextInput::make('type')->disabled()->formatStateUsing(fn ($state) => Str::headline($state)),
                Forms\Components\Select::make('status')->options(fn (?Submission $record) => $record?->type === 'donation'
                    ? array_intersect_key(self::STATUSES, array_flip(['pending', 'paid', 'failed', 'review', 'actioned']))
                    : array_intersect_key(self::STATUSES, array_flip(['new', 'reviewed', 'actioned'])))
                    ->required()
                    ->helperText(fn (?Submission $record) => $record?->type === 'donation' ? '"Paid" is set automatically once Paystack confirms the payment.' : null),
            ]),
            Forms\Components\Section::make('Submitted details')->schema([
                Forms\Components\Placeholder::make('details')->hiddenLabel()
                    ->content(fn (?Submission $record) => self::detailsHtml($record?->data ?? [])),
            ]),
            Forms\Components\Section::make('Medical and welfare notes')
                ->description('Confidential. Share only with staff who need it for the player\'s safety.')
                ->icon('heroicon-o-lock-closed')
                ->visible(fn (?Submission $record) => $record?->type === 'enrolment')
                ->schema([
                    Forms\Components\Placeholder::make('medical')->hiddenLabel()
                        ->content(fn (?Submission $record) => new HtmlString(nl2br(e($record?->sensitive['medical_notes'] ?? 'None provided.')))),
                ]),
        ]);
    }

    private static function detailsHtml(array $data): HtmlString
    {
        $rows = collect($data)->map(function ($v, $k) {
            $value = is_bool($v) ? ($v ? 'Yes' : 'No') : (is_array($v) ? json_encode($v) : (string) $v);

            return '<dt style="font-weight:600">'.e(Str::headline($k)).'</dt><dd style="margin:0 0 .6rem">'.nl2br(e($value)).'</dd>';
        })->implode('');

        return new HtmlString("<dl>{$rows}</dl>");
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->copyable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('type')->badge()->formatStateUsing(fn ($state) => Str::headline($state)),
                Tables\Columns\TextColumn::make('summary')->label('Summary')
                    ->state(fn (Submission $record) => $record->summary())->limit(60),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state) => self::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        'new', 'pending', 'review' => 'warning',
                        'reviewed' => 'info',
                        'actioned', 'paid' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('Received')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(['contact' => 'Contact', 'enrolment' => 'Trial application', 'donation' => 'Donation', 'newsletter' => 'Newsletter']),
                Tables\Filters\SelectFilter::make('status')->options(self::STATUSES),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->label('Open'),
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
