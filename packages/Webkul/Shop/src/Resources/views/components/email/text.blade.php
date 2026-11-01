{{-- Body paragraph. "muted" for secondary notes, "strong" for the one line that matters. --}}
@props(['muted' => false, 'strong' => false, 'gap' => '16px'])

<p style="margin:0 0 {{ $gap }} 0; font-size:{{ $muted ? '13px' : '16px' }}; line-height:{{ $muted ? '20px' : '26px' }}; color:{{ $muted ? '#a3a3a3' : '#f5f5f5' }}; font-weight:{{ $strong ? '600' : '400' }};">{{ $slot }}</p>
