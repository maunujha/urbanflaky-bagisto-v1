{{--
    Two side-by-side blocks that stack on phones (addresses, payment and
    delivery). $blocks: [['title' => ..., 'lines' => [...]], ...]; empty lines
    are dropped, lines are escaped.
--}}
@props(['blocks' => []])

@php
    $blocks = array_values(array_filter($blocks, fn ($block) => array_filter($block['lines'] ?? [])));
@endphp

@if ($blocks)
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:0 0 26px 0;">
        @foreach (array_chunk($blocks, 2) as $row)
            <tr>
                @foreach ($row as $index => $block)
                    <td class="uf-stack {{ $index ? 'uf-stack-gap' : '' }}" width="50%" valign="top" style="padding:{{ $loop->parent->first ? '0' : '20px' }} 0 0 {{ $index ? '20px' : '0' }}; font-size:14px; line-height:22px; color:#d4d4d4;">
                        <div style="margin:0 0 6px 0; font-size:12px; line-height:16px; font-weight:600; letter-spacing:1.5px; text-transform:uppercase; color:#a3a3a3;">{{ $block['title'] }}</div>
                        @foreach (array_filter($block['lines']) as $line)
                            {{ $line }}<br />
                        @endforeach
                    </td>
                @endforeach

                @if (count($row) === 1)
                    <td class="uf-stack uf-hide-sm" width="50%">&nbsp;</td>
                @endif
            </tr>
        @endforeach
    </table>
@endif
