<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ContributionResource\Pages;
use App\Models\Submission;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Finance view of donations (business plan sections 7, 8 and 16). Read-only: a donation's
 * status only changes when Paystack confirms it. Test-mode payments are listed but never
 * counted in the totals.
 */
class ContributionResource extends Resource
{
    protected static ?string $model = Submission::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Contributions';
    protected static ?string $modelLabel = 'contribution';
    protected static ?string $slug = 'contributions';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('type', 'donation');
    }

    public static function table(Table $table): Table
    {
        $allocations = config('contributions.allocations');

        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->copyable()->fontFamily('mono'),
                Tables\Columns\TextColumn::make('created_at')->label('Started')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('data.donor_name')->label('Donor')->searchable(query: fn (Builder $query, string $search) => $query->where('data->donor_name', 'like', "%{$search}%")),
                Tables\Columns\TextColumn::make('data.amount')->label('Amount')->formatStateUsing(fn ($state) => '₦'.number_format((float) $state)),
                Tables\Columns\TextColumn::make('data.allocation')->label('Use')
                    ->formatStateUsing(fn ($state) => $allocations[$state ?? 'greatest_need'] ?? $state)
                    ->description(fn (Submission $r) => ($r->data['restricted'] ?? false) ? 'Restricted' : null),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn ($state) => SubmissionResource::STATUSES[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) { 'paid' => 'success', 'failed' => 'danger', 'review' => 'warning', default => 'gray' }),
                Tables\Columns\TextColumn::make('data.mode')->label('Mode')->badge()
                    ->formatStateUsing(fn ($state) => $state === 'test' ? 'Test — not real money' : 'Live')
                    ->color(fn ($state) => $state === 'test' ? 'warning' : 'success'),
                Tables\Columns\TextColumn::make('data.paid_at')->label('Paid at')->dateTime()->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_intersect_key(SubmissionResource::STATUSES, array_flip(['pending', 'paid', 'failed', 'review']))),
                Tables\Filters\SelectFilter::make('mode')->options(['live' => 'Live', 'test' => 'Test'])
                    ->query(fn (Builder $query, array $data) => $data["value"] ? $query->where('data->mode', $data["value"]) : $query),
                Tables\Filters\SelectFilter::make('allocation')->options($allocations)
                    ->query(fn (Builder $query, array $data) => $data["value"] ? $query->where('data->allocation', $data["value"]) : $query),
            ])
            ->actions([
                Tables\Actions\Action::make('open')->label('Open')->icon('heroicon-o-eye')
                    ->url(fn (Submission $r) => SubmissionResource::getUrl('edit', ['record' => $r])),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /** Verified live contributions only: paid, not test mode. */
    public static function totals(): array
    {
        $paid = Submission::where('type', 'donation')->where('status', 'paid')->get()
            ->filter(fn (Submission $s) => ($s->data['mode'] ?? 'live') !== 'test');

        return [
            'count' => $paid->count(),
            'total' => $paid->sum(fn ($s) => (float) ($s->data['amount'] ?? 0)),
            'restricted' => $paid->filter(fn ($s) => $s->data['restricted'] ?? false)->sum(fn ($s) => (float) ($s->data['amount'] ?? 0)),
        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListContributions::route('/')];
    }
}
