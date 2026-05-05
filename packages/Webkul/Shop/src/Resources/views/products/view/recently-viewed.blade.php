{{-- Recently Viewed Products — section injected via JS into #main after fetch resolves --}}
@push('scripts')
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
        var url   = PRODUCT_URL.replace(':slug', p.url_key);
        var img   = (p.base_image && p.base_image.medium_image_url) ? p.base_image.medium_image_url : '';
        var name  = p.name || '';
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
        imgEl.style.cssText = 'width:100%;height:100%;object-fit:cover;transition:opacity .2s;';
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

    function inject(products) {
        var main = document.getElementById('main');
        if (!main) return;

        var css = document.createElement('style');
        css.textContent = '#rv-scroll::-webkit-scrollbar{display:none}.rv-card img{transition:opacity .2s}.rv-card:hover img{opacity:.85}';
        document.head.appendChild(css);

        var section = document.createElement('section');
        section.style.cssText = 'max-width:1180px;margin:56px auto 0;padding:0 20px 40px;';

        var heading = document.createElement('h2');
        heading.textContent  = 'Recently Viewed';
        heading.style.cssText = 'font-size:1.25rem;font-weight:600;color:#111;margin-bottom:24px;';

        var scroll = document.createElement('div');
        scroll.id = 'rv-scroll';
        scroll.style.cssText = 'display:flex;gap:16px;overflow-x:auto;padding-bottom:12px;-webkit-overflow-scrolling:touch;scrollbar-width:none;-ms-overflow-style:none;';

        products.forEach(function (p) { scroll.appendChild(buildCard(p)); });

        section.appendChild(heading);
        section.appendChild(scroll);
        main.appendChild(section);
    }

    function init() {
        var list   = push(CURRENT_ID);
        var toShow = list.filter(function (id) { return id !== CURRENT_ID; });

        if (toShow.length < 1) return;

        fetch(API_ENDPOINT + '?ids=' + toShow.join(','), { headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status);
                return r.json();
            })
            .then(function (json) {
                var data = Array.isArray(json.data) ? json.data : [];
                if (data.length < 1) return;
                inject(data);
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
@endpush
