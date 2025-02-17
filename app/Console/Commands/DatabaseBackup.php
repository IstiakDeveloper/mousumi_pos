<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class DatabaseBackup extends Command
{
    protected $signature = 'backup:database';
    protected $description = 'Backup database to Google Drive';

    public function handle()
    {
        try {
            // Create backup directory if it doesn't exist
            $backup_dir = storage_path('app/backup');
            if (!File::exists($backup_dir)) {
                File::makeDirectory($backup_dir, 0755, true);
            }

            // Database configuration
            $database = config('database.connections.mysql.database');
            $username = config('database.connections.mysql.username');
            $password = config('database.connections.mysql.password');
            $host = config('database.connections.mysql.host');

            // Backup filename
            $filename = "backup-" . date("Y-m-d-H-i-s") . ".sql";
            $backup_path = storage_path('app/backup/' . $filename);

            // For shared hosting, mysqldump is usually in the system path
            $mysqldump = 'mysqldump';

            // Create backup command
            $command = "{$mysqldump} --opt -h {$host} -u {$username} -p'{$password}' {$database} > \"{$backup_path}\" 2>&1";

            // Execute backup
            $this->info('Creating database backup...');
            exec($command, $output, $return_var);

            if ($return_var !== 0) {
                $this->error('Command output: ' . implode("\n", $output));
                throw new \Exception('Database backup failed with code: ' . $return_var);
            }

            if (!File::exists($backup_path)) {
                throw new \Exception('Backup file was not created at: ' . $backup_path);
            }

            // Check file size
            if (filesize($backup_path) === 0) {
                throw new \Exception('Backup file is empty');
            }

            // Compress the backup
            $this->info('Compressing backup file...');
            $zip_filename = $filename . '.zip';
            $zip_path = storage_path('app/backup/' . $zip_filename);

            $zip = new \ZipArchive();
            if ($zip->open($zip_path, \ZipArchive::CREATE) === TRUE) {
                if (!$zip->addFile($backup_path, $filename)) {
                    throw new \Exception('Failed to add SQL file to ZIP');
                }
                $zip->close();
            } else {
                throw new \Exception('Failed to create zip file');
            }

            // Verify zip was created
            if (!File::exists($zip_path)) {
                throw new \Exception('ZIP file was not created');
            }

            // Upload to Google Drive
            $this->info('Uploading to Google Drive...');
            $this->uploadToDrive($zip_path, $zip_filename);

            // Clean up
            File::delete($backup_path);
            File::delete($zip_path);

            $this->info('Backup completed successfully!');

        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());

            // Clean up any files if they exist
            if (isset($backup_path) && File::exists($backup_path)) {
                File::delete($backup_path);
            }
            if (isset($zip_path) && File::exists($zip_path)) {
                File::delete($zip_path);
            }
        }
    }

    private function uploadToDrive($file_path, $file_name)
    {
        try {
            $credentials_path = storage_path('app/google-credentials.json');
            if (!File::exists($credentials_path)) {
                throw new \Exception('Google credentials file not found at: ' . $credentials_path);
            }

            $client = new Google_Client();
            $client->setAuthConfig($credentials_path);
            $client->addScope(Google_Service_Drive::DRIVE_FILE);

            $service = new Google_Service_Drive($client);

            // File metadata
            $file_metadata = new Google_Service_Drive_DriveFile([
                'name' => $file_name,
                'parents' => ['1In4GyWcEAoKM-bmXyGmABFZ9w2rjuu1-'] // Google Drive folder ID
            ]);

            // Upload file
            $service->files->create(
                $file_metadata,
                [
                    'data' => file_get_contents($file_path),
                    'mimeType' => 'application/zip',
                    'uploadType' => 'multipart'
                ]
            );
        } catch (\Exception $e) {
            throw new \Exception('Google Drive upload failed: ' . $e->getMessage());
        }
    }
}
