<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\TrialApplicationResource\Pages;
use App\Models\Center;
use App\Models\Player;
use App\Models\Program;
use App\Models\TrialApplication;
use App\Support\AgeRange;
use Closure;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** A parent's trial applications: apply, follow the status, withdraw. Own records only. */
class TrialApplicationResource extends Resource
{
    protected static ?string $model = TrialApplication::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Applications';
    protected static ?string $modelLabel = 'trial application';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function statusColor(string $status): string
    {
        return match ($status) {
            'trial_scheduled', 'accepted' => 'success',
            'under_review', 'waitlisted' => 'warning',
            'declined', 'withdrawn' => 'gray',
            default => 'info',
        };
    }

    public static function form(Form $form): Form
    {
        $centres = Center::orderBy('sort_order')->pluck('name', 'name');

        return $form->schema([
            Forms\Components\Section::make('Apply for a trial')
                ->description("Every new player's first trial session is free. The academy reviews each application and updates its status here.")
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('player_id')->label('Player')->required()
                        ->options(fn () => auth()->user()->players()->get()->mapWithKeys(fn (Player $p) => [$p->id => "{$p->full_name} (age {$p->age})"]))
                        ->helperText('Not listed? Add your child under My players first.')
                        // Only the signed-in parent's own players are accepted, whatever is posted.
                        ->rules([fn () => \Illuminate\Validation\Rule::exists('players', 'id')->where('user_id', auth()->id())])
                        ->validationMessages(['exists' => 'Choose one of your own players.'])
                        ->live(),
                    Forms\Components\Select::make('program_id')->label('Programme')->required()
                        ->options(fn () => Program::orderBy('sort_order')->get()->mapWithKeys(fn (Program $p) => [$p->id => "{$p->name} — {$p->ages}"]))
                        ->rules([fn (Get $get) => function (string $attribute, $value, Closure $fail) use ($get) {
                            $player = auth()->user()->players()->find($get('player_id'));
                            $band = AgeRange::parse(Program::find($value)?->ages);
                            if ($player && $band && ($player->age < $band[0] || $player->age > $band[1])) {
                                $fail("This programme is for ages {$band[0]}–{$band[1]}; {$player->first_name} is {$player->age}.");
                            }
                            if ($player && TrialApplication::where('player_id', $player->id)->where('program_id', $value)
                                ->whereNotIn('status', TrialApplication::CLOSED)->exists()) {
                                $fail("{$player->first_name} already has an open application for this programme.");
                            }
                        }]),
                    Forms\Components\Select::make('centre')->label('Training location')
                        ->options($centres)->required($centres->isNotEmpty())->visible($centres->isNotEmpty()),
                    Forms\Components\Textarea::make('parent_note')->label('Anything the coaches should know?')
                        ->helperText('Optional. Medical needs belong in the player\'s profile, where they are stored encrypted.')
                        ->maxLength(2000)->rows(3)->columnSpanFull(),
                    Forms\Components\Checkbox::make('consent')->required()->accepted()->dehydrated(false)->columnSpanFull()
                        ->label('I\'m this player\'s parent or legal guardian and I agree to Ennavizion using these details to handle this application.'),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make()->columns(3)->schema([
                Infolists\Components\TextEntry::make('reference')->copyable()->fontFamily('mono'),
                Infolists\Components\TextEntry::make('status')->badge()
                    ->formatStateUsing(fn ($state) => TrialApplication::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => self::statusColor($state)),
                Infolists\Components\TextEntry::make('created_at')->label('Submitted')->dateTime('j M Y, g:ia'),
                Infolists\Components\TextEntry::make('player.full_name')->label('Player'),
                Infolists\Components\TextEntry::make('program_name')->label('Programme'),
                Infolists\Components\TextEntry::make('centre')->label('Training location')->placeholder('—'),
            ]),
            Infolists\Components\Section::make('Trial')
                ->visible(fn (TrialApplication $r) => filled($r->trial_at))
                ->columns(2)->schema([
                    Infolists\Components\TextEntry::make('trial_at')->label('Date and time')->dateTime('l j F Y, g:ia'),
                    Infolists\Components\TextEntry::make('trial_location')->label('Where')->placeholder('—'),
                ]),
            Infolists\Components\Section::make('Message from the academy')
                ->visible(fn (TrialApplication $r) => filled($r->message_to_parent))
                ->schema([Infolists\Components\TextEntry::make('message_to_parent')->hiddenLabel()]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No applications yet')
            ->emptyStateDescription('Apply for a free trial for one of your players.')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('player.full_name')->label('Player'),
                Tables\Columns\TextColumn::make('program_name')->label('Programme'),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state) => TrialApplication::STATUSES[$state] ?? $state)
                    ->color(fn ($state) => self::statusColor($state)),
                Tables\Columns\TextColumn::make('trial_at')->label('Trial')->dateTime('j M Y, g:ia')->placeholder('Not scheduled yet'),
                Tables\Columns\TextColumn::make('created_at')->label('Submitted')->date('j M Y'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                self::withdrawAction(Tables\Actions\Action::make('withdraw')),
            ]);
    }

    /** Shared by the table row and the view page. */
    public static function withdrawAction($action)
    {
        return $action->label('Withdraw')->icon('heroicon-o-x-circle')->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Withdraw this application?')
            ->modalDescription('The academy will stop reviewing it. You can apply again later.')
            ->visible(fn (TrialApplication $record) => auth()->user()->can('withdraw', $record))
            ->action(function (TrialApplication $record) {
                abort_unless(auth()->user()->can('withdraw', $record), 403);
                $record->update(['status' => 'withdrawn']);
                Notification::make()->title('Application withdrawn')->success()->send();
            });
    }

    public static function canEdit($record): bool
    {
        return false; // parents can't change an application once sent — they withdraw and re-apply
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrialApplications::route('/'),
            'create' => Pages\CreateTrialApplication::route('/create'),
            'view' => Pages\ViewTrialApplication::route('/{record}'),
        ];
    }
}
