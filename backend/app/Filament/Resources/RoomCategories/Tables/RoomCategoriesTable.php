<?php

namespace App\Filament\Resources\RoomCategories\Tables;

use App\Enums\RoomType;
use App\Filament\Support\VisibilityBulkActions;
use App\Models\RoomCategory;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class RoomCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('basePrice')->withCount(['rooms' => fn ($rooms) => $rooms->where('is_active', true)]))
            ->defaultSort('sort_order')
            ->paginated([25, 50, 'all'])
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('name_en')
                    ->label('Room Name')
                    ->searchable(['name_en', 'name_bn'])
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('room_type')
                    ->label('Room Type')
                    ->badge()
                    ->placeholder('Not set')
                    ->sortable(),
                TextColumn::make('max_adults')
                    ->label('Capacity')
                    ->formatStateUsing(fn (RoomCategory $record): string => $record->capacityLabel())
                    ->sortable(),
                TextColumn::make('basePrice.amount')
                    ->label('Price')
                    ->formatStateUsing(fn ($state): string => self::bdt($state))
                    ->description('per night')
                    ->placeholder('No price')
                    ->alignEnd(),
                TextColumn::make('rooms_count')
                    ->label('Bookable units')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('On website')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('room_type')
                    ->label('Room type')
                    ->options(RoomType::class),
                TrashedFilter::make()->label('Archived'),
            ])
            ->recordActions([
                EditAction::make(),
                self::guardDelete(DeleteAction::make()),
                self::guardDelete(ForceDeleteAction::make(), permanently: true),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    ...VisibilityBulkActions::make('is_active'),
                    self::guardBulkDelete(DeleteBulkAction::make()),
                    self::guardBulkDelete(ForceDeleteBulkAction::make(), permanently: true),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function bdt(mixed $amount): string
    {
        return '৳'.number_format((float) $amount);
    }

    /**
     * @template T of Action
     *
     * @param  T  $action
     * @return T
     */
    public static function guardDelete(Action $action, bool $permanently = false): Action
    {
        return $action->before(function (Action $action, RoomCategory $record) use ($permanently): void {
            $reason = $record->deletionBlockedReason($permanently);
            if ($reason === null) {
                return;
            }
            Notification::make()->danger()->title('Room category not deleted')->body($reason)->persistent()->send();
            $action->cancel();
        });
    }

    /**
     * @template T of BulkAction
     *
     * @param  T  $action
     * @return T
     */
    private static function guardBulkDelete(BulkAction $action, bool $permanently = false): BulkAction
    {
        return $action->before(function (BulkAction $action, Collection $records) use ($permanently): void {
            $reasons = $records
                ->map(fn (RoomCategory $record) => $record->deletionBlockedReason($permanently))
                ->filter();
            if ($reasons->isEmpty()) {
                return;
            }
            Notification::make()->danger()->title('Nothing was deleted')->body($reasons->implode(' '))->persistent()->send();
            $action->cancel();
        });
    }
}
