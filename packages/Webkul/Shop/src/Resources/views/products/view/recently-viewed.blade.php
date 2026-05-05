{{-- Recently Viewed Products
     - Stores up to 8 product IDs in localStorage (key: uf_rv_products).
     - Pushes current product on load; displays remaining IDs (excluding current).
     - Hidden when fewer than 2 other products exist in history.
--}}
<section
    id="rv-section"
    class="container mt-14 !p-0 max-1180:px-5"
    style="display:none"
>
    <h2 class="mb-6 text-xl font-semibold text-black max-sm:text-lg">Recently Viewed</h2>

    <div
        id="rv-scroll"
        style="display:flex;gap:16px;overflow-x:auto;padding-bottom:12px;-webkit-overflow-scrolling:touch;scrollbar-width:none;-ms-overflow-style:none;"
    >
        {{-- Cards injected by JS --}}
    </div>
</section>

<style>
#rv-scroll::-webkit-scrollbar { display: none; }
.rv-card img { transition: opacity 0.2s; }
.rv-card:hover img { opacity: 0.85; }
</style>

@pushOnce('scripts')
<script>
(function () {
    var STORAGE_KEY  = 'uf_rv_products';
    var MAX_ITEMS    = 8;
    var CURRENT_ID   = {{ $product->id }};
    var API_ENDPOINT = '{{ url('/api/products/by-ids') }}';
    var PRODUCT_URL  = '{{ route('shop.product_or_category.index', ':slug') }}';

    function readList() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
        catch (e) { return []; }
    }

    function writeList(list) {
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(list)); }
        catch (e) {}
    }

    function push(id) {
        var list = readList().filter(function (x) { return x !== id; });
        list.unshift(id);
        if (list.length > MAX_ITEMS) list = list.slice(0, MAX_ITEMS);
        writeList(list);
        return list;
    }

    function buildCard(p) {
        var url  = PRODUCT_URL.replace(':slug', p.url_key);
        var img  = (p.base_image && p.base_image.medium_image_url) ? p.base_image.medium_image_url : '';
        var name = p.name || '';
        var price = p.min_price || '';

        var a = document.createElement('a');
        a.href      = url;
        a.className = 'rv-card';
        a.setAttribute('aria-label', name);
        a.style.cssText = 'flex-shrink:0;width:160px;text-decoration:none;color:inherit;display:flex;flex-direction:column;gap:8px;';

        var wrap = document.createElement('div');
        wrap.style.cssText = 'width:160px;height:213px;border-radius:12px;overflow:hidden;background:#f5f5f5;';

        var imgEl = document.createElement('img');
        imgEl.src           = img;
        imgEl.alt           = name;
        imgEl.loading       = 'lazy';
        imgEl.width         = 160;
        imgEl.height        = 213;
        imgEl.style.cssText = 'width:100%;height:100%;object-fit:cover;';
        wrap.appendChild(imgEl);

        var nameEl = document.createElement('p');
        nameEl.textContent   = name;
        nameEl.style.cssText = 'font-size:13px;font-weight:500;color:#111;line-height:1.35;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;';

        var priceEl = document.createElement('p');
        priceEl.textContent   = price;
        priceEl.style.cssText = 'font-size:14px;font-weight:700;color:#111;';

        a.appendChild(wrap);
        a.appendChild(nameEl);
        a.appendChild(priceEl);
        return a;
    }

    function init() {
        var list   = push(CURRENT_ID);
        var toShow = list.filter(function (id) { return id !== CURRENT_ID; });

        if (toShow.length < 2) return;

        fetch(API_ENDPOINT + '?ids=' + toShow.join(','), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (json) {
                var data = Array.isArray(json.data) ? json.data : [];
                if (data.length < 2) return;

                var section = document.getElementById('rv-section');
                var scroll  = document.getElementById('rv-scroll');
                if (!section || !scroll) return;

                data.forEach(function (p) { scroll.appendChild(buildCard(p)); });
                section.style.display = '';
            })
            .catch(function () {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
@endPushOnce
