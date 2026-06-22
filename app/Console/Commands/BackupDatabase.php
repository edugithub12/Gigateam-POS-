<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Mail\BackupNotification;
use Carbon\Carbon;

class BackupDatabase extends Command
{
    protected $signature   = 'backup:database';
    protected $description = 'Backup MySQL database to local, USB, and Google Drive — then email notification';

    public function handle(): void
    {
        $db        = config('database.connections.mysql.database');
        $user      = config('database.connections.mysql.username');
        $pass      = config('database.connections.mysql.password');
        $host      = config('database.connections.mysql.host', '127.0.0.1');
        $port      = config('database.connections.mysql.port', '3306');
        $date      = Carbon::now()->format('Y-m-d_His');
        $filename  = "gigateam-backup-{$date}.sql";
        $dir       = storage_path('app/backups');
        $filepath  = "{$dir}/{$filename}";
        $adminEmail = config('mail.admin_email', 'admin@gigateam.co.ke');

        $usbCopied   = false;
        $driveSynced = false;

        $this->info("=== Gigateam Database Backup ===");
        $this->info("Started: " . Carbon::now()->format('Y-m-d H:i:s'));

        // ── 1. Create backup directory ─────────────────────────────────
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // ── 2. Find mysqldump ──────────────────────────────────────────
        $mysqldumpPaths = [
            'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.1.0-winx64\\bin\\mysqldump.exe',
        ];
        $mysqldump = null;
        foreach ($mysqldumpPaths as $path) {
            if (file_exists($path)) { $mysqldump = $path; break; }
        }

        if (!$mysqldump) {
            $msg = 'mysqldump.exe not found. Check Laragon MySQL bin path.';
            $this->error($msg);
            Mail::to($adminEmail)->send(new BackupNotification(false, '', '', $msg));
            return;
        }

        // ── 3. Dump the database ───────────────────────────────────────
        $passFlag = $pass ? "-p\"{$pass}\"" : '';
        $cmd = "\"{$mysqldump}\" -h{$host} -P{$port} -u{$user} {$passFlag} --single-transaction --routines --triggers {$db} > \"{$filepath}\"";
        exec($cmd, $output, $result);

        if ($result !== 0 || !file_exists($filepath) || filesize($filepath) < 100) {
            $msg = 'mysqldump failed. Check MySQL credentials and that MySQL is running.';
            $this->error($msg);
            Mail::to($adminEmail)->send(new BackupNotification(false, $filename, '', $msg));
            return;
        }

        $size = round(filesize($filepath) / 1024, 2) . ' KB';
        $this->info("Backup saved locally: {$filename} ({$size})");

        // ── 4. Copy to USB drive ───────────────────────────────────────
        foreach (range('D', 'Z') as $drive) {
            $usbPath = "{$drive}:\\GigateamBackups";
            if (is_dir($usbPath)) {
                if (copy($filepath, "{$usbPath}/{$filename}")) {
                    $this->info("Copied to USB drive {$drive}:");
                    $usbCopied = true;
                }
                break;
            }
        }
        if (!$usbCopied) $this->warn("No USB drive found. Skipping USB backup.");

        // ── 5. Sync to Google Drive via rclone ─────────────────────────
        $rclone = null;
        foreach (['C:\\rclone\\rclone.exe', 'C:\\Program Files\\rclone\\rclone.exe'] as $p) {
            if (file_exists($p)) { $rclone = $p; break; }
        }
        if ($rclone) {
            exec("\"{$rclone}\" copy \"{$filepath}\" gdrive:GigateamPOS/Backups/ --log-level ERROR", $o, $r);
            $driveSynced = $r === 0;
            $driveSynced
                ? $this->info("Synced to Google Drive.")
                : $this->warn("Google Drive sync failed.");
        } else {
            $this->warn("rclone not found. Skipping Google Drive backup.");
        }

        // ── 6. Delete backups older than 30 days ──────────────────────
        $files = glob("{$dir}/gigateam-backup-*.sql");
        $deleted = 0;
        foreach ($files as $file) {
            if (filemtime($file) < strtotime('-30 days')) { unlink($file); $deleted++; }
        }
        if ($deleted > 0) $this->info("Cleaned up {$deleted} old backup(s).");

        // ── 7. Send success email ──────────────────────────────────────
        Mail::to($adminEmail)->send(new BackupNotification(
            success:     true,
            filename:    $filename,
            filesize:    $size,
            usbCopied:   $usbCopied,
            driveSynced: $driveSynced,
        ));

        $this->info("Email notification sent to {$adminEmail}");
        $this->info("Backup completed: " . Carbon::now()->format('Y-m-d H:i:s'));
        $this->info("================================");
    }
}