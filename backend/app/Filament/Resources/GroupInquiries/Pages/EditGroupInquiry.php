<?php

namespace App\Filament\Resources\GroupInquiries\Pages;

use App\Filament\Resources\GroupInquiries\GroupInquiryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGroupInquiry extends EditRecord
{
    protected static string $resource = GroupInquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
