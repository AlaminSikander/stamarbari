<?php

namespace App\Filament\Support;

use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class VisibilityBulkActions
{
    /**
     * @return array<int, BulkAction>
     */
    public static function make(string $column = 'is_active'): array
    {
        return [
            self::hide($column),
            self::show($column),
        ];
    }

    public static function hide(string $column = 'is_active'): BulkAction
    {
        return BulkAction::make('hide')
            ->label('Hide')
            ->icon(Heroicon::OutlinedEyeSlash)
            ->color('warning')
            ->requiresConfirmation()
            ->modalHeading('Hide selected records')
            ->modalDescription('They will stay in admin, but will not appear on the public website.')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($column): void {
                $records->each(fn (Model $record) => $record->update([$column => false]));
                Notification::make()->title('Hidden from the website')->success()->send();
            });
    }

    public static function show(string $column = 'is_active'): BulkAction
    {
        return BulkAction::make('show')
            ->label('Show')
            ->icon(Heroicon::OutlinedEye)
            ->color('success')
            ->deselectRecordsAfterCompletion()
            ->action(function (Collection $records) use ($column): void {
                $first = $records->first();
                $hasPublishedAt = $first
                    && $column === 'is_published'
                    && Schema::hasColumn($first->getTable(), 'published_at');

                $records->each(function (Model $record) use ($column, $hasPublishedAt): void {
                    $data = [$column => true];
                    if ($hasPublishedAt && blank($record->getAttribute('published_at'))) {
                        $data['published_at'] = now();
                    }
                    $record->update($data);
                });
                Notification::make()->title('Visible on the website')->success()->send();
            });
    }
}
