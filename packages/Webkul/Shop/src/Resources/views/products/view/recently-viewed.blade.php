{{-- Recently Viewed Products — section injected via JS into #main after fetch resolves.
     Styling lives in urbanflaky.css under the `.rv-*` classes (dark theme + responsive). --}}
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

        var wrap = document.createElement('div');
        wrap.className = 'rv-card-media';

        var imgEl = document.createElement('img');
        imgEl.src       = img;
        imgEl.alt       = name;
        imgEl.loading   = 'lazy';
        imgEl.width     = 170;
        imgEl.height    = 226;
        imgEl.className = 'rv-card-img';
        wrap.appendChild(imgEl);

        var nameEl = document.createElement('p');
        nameEl.textContent = name;
        nameEl.className   = 'rv-card-name';

        var priceEl = document.createElement('p');
        priceEl.textContent = price;
        priceEl.className   = 'rv-card-price';

        a.appendChild(wrap);
        a.appendChild(nameEl);
        a.appendChild(priceEl);
        return a;
    }

    function inject(products) {
        var main = document.getElementById('main');
        if (!main) return;

        var section = document.createElement('section');
        section.className = 'rv-section';

        var heading = document.createElement('h2');
        heading.textContent = 'Recently Viewed';
        heading.className   = 'rv-heading';

        var scroll = document.createElement('div');
        scroll.className = 'rv-scroll';

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
