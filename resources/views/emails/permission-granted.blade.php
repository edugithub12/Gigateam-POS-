@extends('emails.layout', ['badgeText' => 'Access Granted'])

@section('content')

<p style="margin:0 0 6px;font-size:18px;font-weight:700;color:#1a1a1a;">&#128273; New Access Granted</p>
<p style="margin:0 0 20px;font-size:13px;color:#555;">
    Hi <strong>{{ $staffMember->name ?? 'there' }}</strong>, you have been granted the following access on the Gigateam Solutions POS system.
</p>

{{-- Permissions list --}}
<p style="margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:#DC2626;border-bottom:2px solid #DC2626;padding-bottom:5px;">Permissions Granted</p>

<table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:20px;border:1px solid #e9ecef;border-radius:6px;overflow:hidden;">
    @foreach($allPermissions as $perm)
    <tr style="border-bottom:1px solid #f0f0f0;">
        <td style="padding:10px 16px;font-size:13px;color:#1a1a1a;">
            <span style="color:#DC2626;font-weight:700;margin-right:8px;">&#10003;</span>{{ $perm }}
        </td>
    </tr>
    @endforeach
    <tr style="background:#f8f9fa;border-top:1px solid #e9ecef;">
        <td style="padding:10px 16px;">
            <span style="font-size:10px;color:#888;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Granted By</span>
            <strong style="font-size:13px;color:#1a1a1a;">{{ $permission->grantedBy->name ?? 'Administrator' }}</strong>
        </td>
    </tr>
    <tr style="background:#f8f9fa;">
        <td style="padding:10px 16px;">
            <span style="font-size:10px;color:#888;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Expires</span>
            <strong style="font-size:13px;color:#1a1a1a;">
                @if($expiresAt)
                    {{ \Carbon\Carbon::parse($expiresAt)->format('d M Y, H:i') }}
                @else
                    No expiry (Permanent)
                @endif
            </strong>
        </td>
    </tr>
    @if($permission->reason ?? false)
    <tr style="background:#f8f9fa;border-top:1px solid #e9ecef;">
        <td style="padding:10px 16px;">
            <span style="font-size:10px;color:#888;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:2px;">Reason</span>
            <span style="font-size:13px;color:#1a1a1a;">{{ $permission->reason }}</span>
        </td>
    </tr>
    @endif
</table>

<p style="margin:0 0 14px;font-size:13px;color:#555;">
    This access is now active. Log in to the POS system and these permissions will be available immediately.
</p>

@if($expiresAt)
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;">
<tr>
    <td style="background:#fff3cd;border-left:4px solid #ffc107;padding:10px 14px;border-radius:0 4px 4px 0;font-size:12px;color:#856404;">
        &#9888; This access will automatically expire on <strong>{{ \Carbon\Carbon::parse($expiresAt)->format('d M Y at H:i') }}</strong>.
    </td>
</tr>
</table>
@endif

<p style="margin:0;font-size:11px;color:#aaa;">
    If you believe this was granted in error, please contact your administrator immediately.
</p>

@endsection