@props(['count' => 3])

<section class="uf-cat-section">
    <div class="uf-cat-container">
        <div class="uf-cat-grid">
            @for ($i = 0; $i < $count; $i++)
                <div class="uf-cat-card shimmer" aria-hidden="true"></div>
            @endfor
        </div>
    </div>
</section>
