<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ParbatoBackupCommand extends Command
{
    protected $signature = 'parbato:backup {--keep=14 : Number of compressed backups to retain}';

    protected $description = 'Create a mysqldump backup of the ParbaTo database into storage/app/backups';

    public function handle(): int
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? '') !== 'mysql') {
            $this->error('parbato:backup requires a MySQL connection (mysqldump). Current driver: '.($config['driver'] ?? 'unknown'));
            Log::error('Backup failed: unsupported driver', ['driver' => $config['driver'] ?? null]);

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $filename = 'parbato_'.now()->format('Y-m-d_H-i-s').'.sql.gz';
        $path = $dir.DIRECTORY_SEPARATOR.$filename;

        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 3306;
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        $mysqldump = env('MYSQLDUMP_PATH', 'mysqldump');
        $command = sprintf(
            '%s --host=%s --port=%s --user=%s %s %s | gzip > %s',
            escapeshellcmd($mysqldump),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            $password !== '' ? '--password='.escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($path)
        );

        $exit = null;
        system($command, $exit);

        if ($exit !== 0 || ! File::exists($path) || File::size($path) < 20) {
            $this->error('Backup failed. Ensure mysqldump is installed and credentials are valid.');
            Log::error('Backup failed', ['exit' => $exit, 'path' => $path]);
            if (File::exists($path)) {
                File::delete($path);
            }

            return self::FAILURE;
        }

        $keep = max(1, (int) $this->option('keep'));
        $files = collect(File::files($dir))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.sql.gz'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        foreach ($files->slice($keep) as $old) {
            File::delete($old->getPathname());
        }

        $this->info("Backup created: {$filename}");
        Log::info('Backup succeeded', ['file' => $filename, 'bytes' => File::size($path)]);

        return self::SUCCESS;
    }
}
