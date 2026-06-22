<div style="font-family: sans-serif; font-size: 14px;">
    @php
        $properties = $activity->properties;
        $old = $properties->get('old', []);
        $new = $properties->get('attributes', []);
        $allKeys = array_unique(array_merge(array_keys($old ?? []), array_keys($new ?? [])));
    @endphp

    @if(empty($allKeys))
        <p style="color:#888; padding:16px 0;">No field-level changes recorded for this action.</p>
    @else
        <table style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background:#f8f9fa;">
                    <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #e9ecef; color:#666; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Field</th>
                    <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #e9ecef; color:#c0392b; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">Before</th>
                    <th style="padding:10px 12px; text-align:left; border-bottom:2px solid #e9ecef; color:#27ae60; font-size:12px; text-transform:uppercase; letter-spacing:0.5px;">After</th>
                </tr>
            </thead>
            <tbody>
                @foreach($allKeys as $key)
                    @php
                        $before = $old[$key] ?? null;
                        $after  = $new[$key] ?? null;
                        $changed = $before !== $after;
                    @endphp
                    <tr style="background: {{ $changed ? '#fffbf0' : 'white' }}; border-bottom:1px solid #f0f0f0;">
                        <td style="padding:8px 12px; font-weight:600; color:#1a1a1a;">
                            {{ ucwords(str_replace('_', ' ', $key)) }}
                        </td>
                        <td style="padding:8px 12px; color:#c0392b;">
                            {{ is_array($before) ? json_encode($before) : ($before ?? '—') }}
                        </td>
                        <td style="padding:8px 12px; color:#27ae60;">
                            {{ is_array($after) ? json_encode($after) : ($after ?? '—') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div style="margin-top:16px; padding:12px; background:#f8f9fa; border-radius:6px; font-size:12px; color:#888;">
        <strong>Logged at:</strong> {{ $activity->created_at->format('d M Y H:i:s') }}
        &nbsp;|&nbsp;
        <strong>By:</strong> {{ $activity->causer->name ?? 'System' }}
        &nbsp;|&nbsp;
        <strong>IP:</strong> {{ $activity->properties->get('ip', '—') }}
    </div>
</div>