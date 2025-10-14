@php
    $headers  = $headers ?? [];
    $rows     = $rows ?? [];
    $striped  = $striped ?? true;
    $bordered = $bordered ?? true;
@endphp

{{-- Email Table Component --}}
<table style="
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
    font-size: 14px;
    @if( $bordered )
        border: 1px solid #DEE2E6;
    @endif
" cellspacing="0" cellpadding="0">
    @if( $headers )
        <thead>
            <tr style="background-color: #F8F9FA;">
                @foreach( $headers as $header )
                    <th style="
                                padding: 12px;
                                text-align: left;
                                font-weight: bold;
                                color: #495057;
                                @if( $bordered )
                                    border-bottom: 1px solid #DEE2E6;
                                    border-right: 1px solid #DEE2E6;
                                @endif
                                @if( !$loop->last )
                                    border-right: 1px solid #DEE2E6;
                                @endif
                            ">
                        {{ $header }}
                    </th>
                @endforeach
            </tr>
        </thead>
    @endif

    <tbody>
        @foreach( $rows as $index => $row )
            <tr style="
                    @if( $striped && $index % 2 == 1 )
                        background-color: #F8F9FA;
                    @endif
                ">
                @foreach( $row as $cell )
                    <td style="
                                padding: 12px;
                                color: #495057;
                                @if( $bordered )
                                    border-bottom: 1px solid #DEE2E6;
                                    border-right: 1px solid #DEE2E6;
                                @endif
                                @if( !$loop->last )
                                    border-right: 1px solid #DEE2E6;
                                @endif
                            ">
                        {!! $cell !!}
                    </td>
                @endforeach
            </tr>
        @endforeach
    </tbody>
</table>
