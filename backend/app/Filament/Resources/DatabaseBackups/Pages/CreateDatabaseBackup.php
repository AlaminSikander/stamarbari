<?php

namespace App\Filament\Resources\DatabaseBackups\Pages;

use App\Filament\Resources\DatabaseBackups\DatabaseBackupResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDatabaseBackup extends CreateRecord
{
    protected static string $resource = DatabaseBackupResource::class;
}
