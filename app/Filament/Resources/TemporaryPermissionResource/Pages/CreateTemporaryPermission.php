<?php

namespace App\Filament\Resources\TemporaryPermissionResource\Pages;

use App\Filament\Resources\TemporaryPermissionResource;
use App\Models\TemporaryPermission;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;

class CreateTemporaryPermission extends CreateRecord
{
    protected static string $resource = TemporaryPermissionResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    /**
     * Override to create one record per selected permission
     * instead of the default single record creation.
     */
    protected function handleRecordCreation(array $data): TemporaryPermission
    {
        $permissions = $data['permissions'] ?? [];
        $expiresAt   = ($data['is_permanent'] ?? false) ? null : ($data['expires_at'] ?? null);
        $userId      = $data['user_id'];
        $grantedBy   = auth()->id();
        $reason      = $data['reason'] ?? '';

        $lastRecord = null;

        foreach ($permissions as $permission) {
            $lastRecord = TemporaryPermission::create([
                'user_id'    => $userId,
                'permission' => $permission,
                'granted_by' => $grantedBy,
                'granted_at' => now(),
                'expires_at' => $expiresAt,
                'reason'     => $reason,
                'status'     => 'active',
            ]);
        }

        // Store for afterCreate()
        $this->grantedPermissions = $permissions;
        $this->grantedUserId      = $userId;
        $this->grantedExpiresAt   = $expiresAt;

        // Return the last created record (Filament requires a model returned)
        return $lastRecord ?? new TemporaryPermission();
    }

    // Store granted info for afterCreate
    public array $grantedPermissions = [];
    public ?int $grantedUserId = null;
    public mixed $grantedExpiresAt = null;

    protected function afterCreate(): void
    {
        $user  = User::find($this->grantedUserId);
        $count = count($this->grantedPermissions);

        if (!$user) return;

        // Admin notification
        Notification::make()
            ->title('Permissions Granted')
            ->body("✅ {$user->name} has been granted {$count} permission(s)" .
                   ($this->grantedExpiresAt
                       ? ' until ' . \Carbon\Carbon::parse($this->grantedExpiresAt)->format('d M Y H:i')
                       : ' (permanent)') . '.')
            ->success()
            ->send();

        // Email the staff member
        if ($user->email && $count > 0) {
            try {
                // Pass the last created record — email lists all permissions
                Mail::to($user->email)->send(
                    new \App\Mail\PermissionGrantedNotification(
                        $this->record,
                        $this->grantedPermissions,
                        $this->grantedExpiresAt,
                        $user
                    )
                );
            } catch (\Exception $e) {
                // Fail silently
            }
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return null;
    }
}