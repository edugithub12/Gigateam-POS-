<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class EnableAuditLogging extends Command
{
    protected $signature   = 'gigateam:enable-audit-logging';
    protected $description = 'Add LogsUserActivity trait to all Gigateam models';

    // Map of model file => log name
    private array $models = [
        'Sale.php'         => 'sales',
        'Invoice.php'      => 'invoices',
        'Quotation.php'    => 'quotations',
        'JobCard.php'      => 'job_cards',
        'DeliveryNote.php' => 'delivery_notes',
        'Product.php'      => 'products',
        'Customer.php'     => 'customers',
        'Payment.php'      => 'payments',
        'Supplier.php'     => 'suppliers',
        'User.php'         => 'users',
        'StockMovement.php'=> 'stock',
    ];

    public function handle(): void
    {
        $path = app_path('Models');

        foreach ($this->models as $file => $logName) {
            $filePath = "{$path}/{$file}";

            if (!file_exists($filePath)) {
                $this->warn("Skipping {$file} — not found.");
                continue;
            }

            $content = file_get_contents($filePath);

            // Skip if already patched
            if (str_contains($content, 'LogsUserActivity')) {
                $this->info("Skipping {$file} — already has LogsUserActivity.");
                continue;
            }

            // 1. Add import after namespace line
            $content = preg_replace(
                '/(namespace App\\\\Models;)/',
                "$1\n\nuse App\\Traits\\LogsUserActivity;",
                $content,
                1
            );

            // 2. Add trait to first use statement inside class
            $content = preg_replace(
                '/(\buse\s+HasFactory)/m',
                'use LogsUserActivity, HasFactory',
                $content,
                1
            );

            // 3. Add getActivityLogName() before the first public function
            $method = "\n    protected static function getActivityLogName(): string\n    {\n        return '{$logName}';\n    }\n";

            $content = preg_replace(
                '/(\n    \/\/ ─|(\n    public function))/m',
                $method . '$1',
                $content,
                1
            );

            file_put_contents($filePath, $content);
            $this->info("✅ Patched {$file} → log name: {$logName}");
        }

        $this->info('');
        $this->info('All models patched. Run: php artisan optimize:clear && php artisan optimize');
    }
}