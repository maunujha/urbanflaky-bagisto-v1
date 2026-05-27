{{-- Urbanflaky: Card Background selector for "Shop by Gender" home carousel --}}
@php
    $cardBackgrounds = [
        ''                  => ['label' => 'None (Default)', 'style' => 'background: repeating-linear-gradient(45deg, #f3f4f6, #f3f4f6 6px, #fff 6px, #fff 12px);'],
        'gradient-dark'     => ['label' => 'Dark',           'style' => 'background: linear-gradient(135deg, #0a0a0a 0%, #1f1f1f 55%, #3a3a3a 100%);'],
        'gradient-blue'     => ['label' => 'Blue',           'style' => 'background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 55%, #93c5fd 100%);'],
        'gradient-pink'     => ['label' => 'Pink',           'style' => 'background: linear-gradient(135deg, #be123c 0%, #ef4444 55%, #fb923c 100%);'],
        'gradient-emerald'  => ['label' => 'Emerald',        'style' => 'background: linear-gradient(135deg, #064e3b 0%, #10b981 55%, #6ee7b7 100%);'],
        'gradient-purple'   => ['label' => 'Purple',         'style' => 'background: linear-gradient(135deg, #3b0764 0%, #7c3aed 55%, #c4b5fd 100%);'],
    ];

    $current = $currentCardBg ?? '';
@endphp

<style>
    .uf-bg-picker {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(108px, 1fr));
        gap: 14px;
        margin-top: 10px;
    }

    .uf-bg-option {
        position: relative;
        display: block;
        cursor: pointer;
        user-select: none;
        outline: none;
    }

    /* Hide the native radio but keep it accessible & in normal flow */
    .uf-bg-option input[type="radio"] {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        margin: 0;
        opacity: 0;
        cursor: pointer;
        z-index: 2;
    }

    .uf-bg-swatch {
        display: block;
        width: 100%;
        height: 92px;
        border-radius: 12px;
        border: 2px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.06);
        transition: border-color .18s ease, box-shadow .18s ease, transform .18s ease;
        position: relative;
        overflow: hidden;
    }

    .uf-bg-option:hover .uf-bg-swatch {
        transform: translateY(-2px);
        border-color: #93c5fd;
        box-shadow: 0 6px 14px rgba(0, 0, 0, 0.1);
    }

    .uf-bg-option input[type="radio"]:focus-visible + .uf-bg-swatch {
        outline: 2px solid #2563eb;
        outline-offset: 2px;
    }

    .uf-bg-option input[type="radio"]:checked + .uf-bg-swatch {
        border-color: #2563eb;
        box-shadow:
            0 0 0 3px rgba(37, 99, 235, 0.35),
            0 4px 12px rgba(37, 99, 235, 0.18);
        transform: translateY(-1px);
    }

    /* Checkmark badge when selected */
    .uf-bg-swatch::after {
        content: '';
        position: absolute;
        top: 6px;
        right: 6px;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        background: #2563eb url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='white'><path fill-rule='evenodd' d='M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 011.42-1.42L8.5 12.08l6.79-6.79a1 1 0 011.414 0z' clip-rule='evenodd'/></svg>") center/14px no-repeat;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
        opacity: 0;
        transform: scale(0.6);
        transition: opacity .15s ease, transform .15s ease;
    }
    .uf-bg-option input[type="radio"]:checked + .uf-bg-swatch::after {
        opacity: 1;
        transform: scale(1);
    }

    .uf-bg-label {
        display: block;
        text-align: center;
        margin-top: 8px;
        font-size: 12px;
        font-weight: 500;
        color: #4b5563;
    }
    .uf-bg-option input[type="radio"]:checked ~ .uf-bg-label {
        color: #2563eb;
        font-weight: 600;
    }

    .dark .uf-bg-label { color: #d1d5db; }
    .dark .uf-bg-swatch { border-color: #374151; }
    .dark .uf-bg-option input[type="radio"]:checked ~ .uf-bg-label { color: #93c5fd; }
</style>

<div class="mt-6 border-t border-gray-200 pt-5 dark:border-gray-800">
    <p class="font-medium text-gray-800 dark:text-white">
        Card Background
    </p>

    <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
        Choose the gradient background for this category's card on the homepage "Shop by" carousel. Select <strong>None</strong> to use the default dark surface.
    </p>

    <div class="uf-bg-picker">
        @foreach ($cardBackgrounds as $value => $bg)
            <label
                class="uf-bg-option"
                title="{{ $bg['label'] }}"
            >
                <input
                    type="radio"
                    name="card_background"
                    value="{{ $value }}"
                    @checked($current === $value)
                />
                <span class="uf-bg-swatch" style="{{ $bg['style'] }}"></span>
                <span class="uf-bg-label">{{ $bg['label'] }}</span>
            </label>
        @endforeach
    </div>
</div>
