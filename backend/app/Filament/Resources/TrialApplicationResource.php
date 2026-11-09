<?php

namespace App\Filament\Resources;

use App\Filament\Portal\Resources\TrialApplicationResource as PortalTrialApplicationResource;
use App\Filament\Resources\TrialApplicationResource\Pages;
use App\Models\TrialApplication;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

/**
 * Staff view of parent-portal trial applications. Changing the status, trial date/place or the
 * message to the parent emails the parent automatically (TrialApplication model events).
 * Staff notes are internal and never shown to parents.
 */
class TrialApplicationResource extends Resource
{
    protected static ?string $model = TrialApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'Parent portal';
    protected static ?string $navigationLabel = 'Trial applications';
    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $n = TrialApplication::where('status', 'submitted')->count();

        return $n ? (string) $n : null;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Application')->columns(3)->schema([
                Forms\Components\Placeholder::make('reference')->content(fn (?TrialApplication $r) => $r?->reference),
                Forms\Components\Placeholder::make('submitted')->content(fn (?TrialApplication $r) => $r?->created_at?->format('j M Y, g:ia')),
                Forms\Components\Placeholder::make('programme')->content(fn (?TrialApplication $r) => trim(($r?->program_name).($r?->centre ? " · {$r->centre}" : ''))),
                Forms\Components\Placeholder::make('player')->content(fn (?TrialApplication $r) => $r ? "{$r->player->full_name} — age {$r->player->age} (born {$r->player->dob->format('j M Y')})" : null),
                Forms\Components\Placeholder::make('position_club')->label('Position / club')
                    ->content(fn (?TrialApplication $r) => trim(($r?->player->position ?? '—').' / '.($r?->player->current_club ?: '—'))),
                Forms\Components\Placeholder::make('parent')->label('Parent / guardian')
                    ->content(fn (?TrialApplication $r) => $r ? new HtmlString(e($r->parent->name).'<br>'.e($r->parent->email).'<br>'.e($r->parent->phone ?? '')) : null),
                Forms\Components\Placeholder::make('note')->label('Note from the parent')->columnSpanFull()
                    ->content(fn (?TrialApplication $r) => $r?->parent_note ?: '—'),
            ]),
            Forms\Components\Section::make('Medical and welfare notes')
                ->description("Confidential. Share only with staff who need it for the player's safety.")
                ->icon('heroicon-o-lock-closed')->collapsed()
                ->schema([
                    Forms\Components\Placeholder::make('medical')->hiddenLabel()
                        ->content(fn (?TrialApplication $r) => new HtmlString(nl2br(e($r?->player->medical_notes ?: 'None provided.')))),
                ]),
            Forms\Components\Section::make('Decision and trial')
                ->description('The parent is emailed when the status, trial details or message change.')
                ->columns(2)->schema([
                    Forms\Components\Select::make('status')->options(TrialApplication::STATUSES)->required()->live(),
                    Forms\Components\DateTimePicker::make('trial_at')->label('Trial date and time')->seconds(false)
                        ->required(fn (Forms\Get $get) => $get('status') === 'trial_scheduled'),
                    Forms\Components\TextInput::make('trial_location')->label('Trial location')->maxLength(255)
                        ->required(fn (Forms\Get $get) => $get('status') === 'trial_scheduled'),
                    Forms\Components\Textarea::make('message_to_parent')->label('Message to the parent')->rows(3)->maxLength(2000)->columnSpanFull()
                        ->helperText("Shown in the parent's portal and included in the email."),
                    Forms\Components\Textarea::make('staff_notes')->label('Internal staff notes')->rows(3)->maxLength(5000)->columnSpanFull()
                        ->helperText('Never shown to the parent.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->copyable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('player.full_name')->label('Player'),
                Tables\Columns\TextColumn::make('player.age')->label('Age'),
                Tables\Columns\TextColumn::make('program_name')->label('Programme'),
                Tables\Columns\TextColumn::make('parent.name')->label('Parent')->description(fn (TrialApplication $r) => $r->parent->phone),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state) => TrialApplication::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => PortalTrialApplicationResource::statusColor($state)),
                Tables\Columns\TextColumn::make('trial_at')->label('Trial')->dateTime('j M, g:ia')->placeholder('—')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Received')->date('j M Y')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(TrialApplication::STATUSES),
                Tables\Filters\SelectFilter::make('program_name')->label('Programme')
                    ->options(fn () => TrialApplication::query()->distinct()->pluck('program_name', 'program_name')->all()),
            ])
            ->actions([Tables\Actions\EditAction::make()->label('Review')]);
    }

    public static function canCreate(): bool
    {
        return false; // applications come from parents in the portal
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrialApplications::route('/'),
            'edit' => Pages\EditTrialApplication::route('/{record}/edit'),
        ];
    }
}
