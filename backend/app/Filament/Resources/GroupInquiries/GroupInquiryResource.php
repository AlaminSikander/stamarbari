<?php

namespace App\Filament\Resources\GroupInquiries;

use App\Filament\Resources\GroupInquiries\Pages\CreateGroupInquiry;
use App\Filament\Resources\GroupInquiries\Pages\EditGroupInquiry;
use App\Filament\Resources\GroupInquiries\Pages\ListGroupInquiries;
use App\Filament\Resources\GroupInquiries\Schemas\GroupInquiryForm;
use App\Filament\Resources\GroupInquiries\Tables\GroupInquiriesTable;
use App\Models\GroupInquiry;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GroupInquiryResource extends Resource
{
    protected static ?string $model = GroupInquiry::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    public static function form(Schema $schema): Schema
    {
        return GroupInquiryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GroupInquiriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroupInquiries::route('/'),
            'create' => CreateGroupInquiry::route('/create'),
            'edit' => EditGroupInquiry::route('/{record}/edit'),
        ];
    }
}
