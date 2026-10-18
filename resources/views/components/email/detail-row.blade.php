@props(['label', 'emphasis' => false])
<tr>
    <th scope="row" align="left" valign="top" style="width:38%;padding:12px 10px 12px 0;border-bottom:1px solid #e2e8f0;color:#64748b;font-size:12px;line-height:1.6;font-weight:400;">{{ $label }}</th>
    <td valign="top" style="padding:12px 0;border-bottom:1px solid #e2e8f0;color:#0f172a;font-size:{{ $emphasis ? '18px' : '13px' }};line-height:1.6;font-weight:{{ $emphasis ? '700' : '600' }};word-break:break-word;">{{ $slot }}</td>
</tr>
