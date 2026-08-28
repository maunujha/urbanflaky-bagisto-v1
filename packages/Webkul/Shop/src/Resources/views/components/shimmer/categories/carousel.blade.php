@props(['count' => 3, 'navigationLink' => false])

<section class="uf-cat-section" {{ $attributes }}>
    <div class="uf-cat-container">
        <div class="uf-cat-grid">
            @for ($i = 0; $i < $count; $i++)
                <div class="uf-cat-card shimmer" aria-hidden="true"></div>
            @endfor
        </div>
    </div>
</section>
