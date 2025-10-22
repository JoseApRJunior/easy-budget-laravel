{{-- Email Table Component --}}
<table class="email-table" style="width: 100%; border-collapse: collapse; margin: 20px 0;">
    <thead>
        <tr>
            @foreach( $headers ?? [] as $header )
                <th
                    style="padding: 12px; text-align: left; border-bottom: 2px solid #e5e7eb; font-weight: 600; color: #374151;">
                    {{ $header }}
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach( $rows ?? [] as $row )
            <tr>
                @foreach( $row as $cell )
                    <td style="padding: 12px; border-bottom: 1px solid #e5e7eb; color: #6b7280;">
                        {{ $cell }}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
