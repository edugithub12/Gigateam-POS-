@extends('emails.layout', ['badgeText' => $success ? 'Backup OK' : 'BACKUP FAILED'])

@section('content')

@if($success)
<div class="alert-box green">
    <div class="alert-title">✅ Database Backup Successful</div>
    <div class="alert-sub">{{ now()->format('l, d M Y \a\t H:i') }} — Your data is safe.</div>
</div>

<div class="section-heading">Backup Details</div>
<table class="data-table">
    <tbody>
        <tr>
            <td class="label-col">Filename</td>
            <td class="value-col">{{ $filename }}</td>
        </tr>
        <tr>
            <td class="label-col">File Size</td>
            <td class="value-col">{{ $filesize }}</td>
        </tr>
        <tr>
            <td class="label-col">Local Backup</td>
            <td class="value-col"><span class="badge badge-green">✓ Saved</span></td>
        </tr>
        <tr>
            <td class="label-col">USB Drive</td>
            <td class="value-col">
                @if($usbCopied)
                    <span class="badge badge-green">✓ Copied</span>
                @else
                    <span class="badge badge-grey">USB not connected</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label-col">Google Drive</td>
            <td class="value-col">
                @if($driveSynced)
                    <span class="badge badge-green">✓ Synced</span>
                @else
                    <span class="badge badge-orange">⚠ Not synced</span>
                @endif
            </td>
        </tr>
        <tr>
            <td class="label-col">Backup Time</td>
            <td class="value-col">{{ now()->format('H:i:s') }}</td>
        </tr>
    </tbody>
</table>

@if(!$usbCopied)
<div class="warning-row">
    ⚠️ USB drive not found. Make sure a USB drive with a <strong>GigateamBackups</strong> folder is plugged into the server.
</div>
@endif

@if(!$driveSynced)
<div class="warning-row">
    ⚠️ Google Drive sync failed. Run <strong>rclone config reconnect gdrive:</strong> on the server to refresh the connection.
</div>
@endif

@else

<div class="alert-box">
    <div class="alert-title">🚨 DATABASE BACKUP FAILED</div>
    <div class="alert-sub">{{ now()->format('l, d M Y \a\t H:i') }} — Immediate attention required.</div>
</div>

<div class="error-row">
    <strong>Error:</strong> {{ $errorMessage ?: 'Unknown error. Check storage/logs/backup.log on the server.' }}
</div>

<div class="section-heading">What to Do Now</div>
<table class="data-table">
    <tbody>
        <tr>
            <td style="padding:8px 10px;">1.</td>
            <td style="padding:8px 10px;">Log into the server desktop</td>
        </tr>
        <tr>
            <td style="padding:8px 10px;">2.</td>
            <td style="padding:8px 10px;">Open PowerShell and run: <code style="background:#f0f0f0; padding:2px 6px; border-radius:3px;">php artisan backup:database</code></td>
        </tr>
        <tr>
            <td style="padding:8px 10px;">3.</td>
            <td style="padding:8px 10px;">Check the error output</td>
        </tr>
        <tr>
            <td style="padding:8px 10px;">4.</td>
            <td style="padding:8px 10px;">Check <code style="background:#f0f0f0; padding:2px 6px; border-radius:3px;">storage\logs\backup.log</code> for details</td>
        </tr>
        <tr>
            <td style="padding:8px 10px;">5.</td>
            <td style="padding:8px 10px;">Ensure MySQL is running in Laragon (toggle should be green)</td>
        </tr>
    </tbody>
</table>

<div class="alert-box">
    <div class="alert-title">Your last successful backup</div>
    <div class="alert-sub">Check storage\app\backups\ on the server for the most recent .sql file.</div>
</div>

@endif

@endsection