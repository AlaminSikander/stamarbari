<?php

namespace App\Jobs;

use App\Models\DatabaseBackup;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\File;

class RunDatabaseBackupJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $disk = Setting::getValue('backup_disk', 'local');
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);
        $filename = 'backup-'.now('Asia/Dhaka')->format('Y-m-d-His').'.sql';
        $path = $dir.'/'.$filename;

        $record = DatabaseBackup::query()->create([
            'filename' => $filename,
            'disk' => $disk,
            'path' => $path,
            'status' => 'running',
        ]);

        try {
            $connection = config('database.default');
            if ($connection === 'sqlite') {
                $source = config('database.connections.sqlite.database');
                copy($source, $path);
            } else {
                $db = config('database.connections.'.$connection);
                $cmd = sprintf(
                    'mysqldump --host=%s --port=%s --user=%s --password=%s %s > %s',
                    escapeshellarg($db['host']),
                    escapeshellarg((string) $db['port']),
                    escapeshellarg($db['username']),
                    escapeshellarg($db['password']),
                    escapeshellarg($db['database']),
                    escapeshellarg($path),
                );
                exec($cmd, $output, $code);
                if ($code !== 0) {
                    throw new \RuntimeException('Database dump failed.');
                }
            }

            $retention = (int) Setting::getValue('backup_retention_days', 14);
            DatabaseBackup::query()
                ->where('created_at', '<', now()->subDays($retention))
                ->each(function (DatabaseBackup $old) {
                    if (is_file($old->path)) {
                        @unlink($old->path);
                    }
                    $old->delete();
                });

            $record->update([
                'status' => 'completed',
                'size_bytes' => is_file($path) ? filesize($path) : null,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $record->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}
