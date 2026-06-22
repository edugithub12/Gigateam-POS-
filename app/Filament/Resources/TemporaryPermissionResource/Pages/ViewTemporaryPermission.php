<?php
// ═══════════════════════════════════════════════════════════════════
// FILE: app/Filament/Resources/TemporaryPermissionResource/Pages/ViewTemporaryPermission.php
// ═══════════════════════════════════════════════════════════════════

namespace App\Filament\Resources\TemporaryPermissionResource\Pages;

use App\Filament\Resources\TemporaryPermissionResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewTemporaryPermission extends ViewRecord
{
    protected static string $resource = TemporaryPermissionResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Grant Details')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('user.name')
                        ->label('Staff Member')
                        ->weight('bold'),

                    Infolists\Components\TextEntry::make('permission')
                        ->label('Permission')
                        ->badge()
                        ->color('info'),

                    Infolists\Components\TextEntry::make('grantedBy.name')
                        ->label('Granted By'),

                    Infolists\Components\TextEntry::make('status_label')
                        ->label('Status')
                        ->badge()
                        ->color(fn ($record) => $record->status_badge_color),

                    Infolists\Components\TextEntry::make('granted_at')
                        ->label('Granted At')
                        ->dateTime('d M Y H:i'),

                    Infolists\Components\TextEntry::make('expires_at')
                        ->label('Expires At')
                        ->dateTime('d M Y H:i')
                        ->placeholder('Never — Permanent Grant'),

                    Infolists\Components\TextEntry::make('reason')
                        ->label('Reason')
                        ->columnSpanFull()
                        ->placeholder('No reason provided'),
                ]),

            Infolists\Components\Section::make('Revocation Details')
                ->visible(fn ($record) => $record->status === 'revoked')
                ->columns(2)
                ->schema([
                    Infolists\Components\TextEntry::make('revokedBy.name')
                        ->label('Revoked By'),

                    Infolists\Components\TextEntry::make('revoked_at')
                        ->label('Revoked At')
                        ->dateTime('d M Y H:i'),

                    Infolists\Components\TextEntry::make('revocation_reason')
                        ->label('Revocation Reason')
                        ->columnSpanFull()
                        ->placeholder('No reason provided'),
                ]),
        ]);
    }
}