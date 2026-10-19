<?php

namespace App\Filament\Resources\GroupInquiries\Pages;

use App\Filament\Resources\GroupInquiries\GroupInquiryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGroupInquiries extends ListRecords
{
    protected static string $resource = GroupInquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
