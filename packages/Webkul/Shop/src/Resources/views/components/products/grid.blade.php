@props(['src', 'title'])

<v-products-grid
    src="{{ $src }}"
    title="{{ $title }}"
>
    <x-shop::shimmer.products.carousel />
</v-products-grid>

@pushOnce('scripts')
    <script
        type="text/x-template"
        id="v-products-grid-template"
    >
        <section
            class="uf-prod-section"
            v-if="! isLoading && products.length"
        >
            <div class="uf-prod-container">
                <div class="uf-prod-head">
                    <h2 class="uf-prod-title" v-text="title"></h2>
                </div>

                <div class="uf-related-grid">
                    <x-shop::products.card
                        v-for="product in products"
                        ::key="product.id"
                        class="uf-related-grid-item"
                    />
                </div>

                <div class="uf-loadmore-wrap" v-if="hasMore">
                    <button
                        type="button"
                        class="uf-loadmore-btn"
                        :disabled="isLoadingMore"
                        @click="loadMore"
                    >
                        <span
                            class="uf-loadmore-spinner"
                            v-if="isLoadingMore"
                            aria-hidden="true"
                        ></span>
                        <span v-if="isLoadingMore">@lang('shop::app.products.view.loading')</span>
                        <span v-else>@lang('shop::app.products.view.load-more')</span>
                    </button>
                </div>
            </div>
        </section>

        <!-- Initial shimmer -->
        <template v-if="isLoading">
            <x-shop::shimmer.products.carousel />
        </template>
    </script>

    <script type="module">
        app.component('v-products-grid', {
            template: '#v-products-grid-template',

            props: [
                'src',
                'title',
            ],

            data() {
                return {
                    isLoading: true,
                    isLoadingMore: false,
                    products: [],
                    currentPage: 0,
                    lastPage: 1,
                };
            },

            computed: {
                hasMore() {
                    return this.currentPage < this.lastPage;
                },
            },

            mounted() {
                this.fetchPage().finally(() => {
                    this.isLoading = false;
                });
            },

            methods: {
                fetchPage() {
                    const nextPage = this.currentPage + 1;

                    return this.$axios.get(this.src, { params: { page: nextPage } })
                        .then(response => {
                            this.products = this.products.concat(response.data.data);

                            const meta = response.data.meta || {};
                            this.currentPage = meta.current_page || nextPage;
                            this.lastPage    = meta.last_page || this.currentPage;
                        })
                        .catch(error => {
                            console.log(error);
                        });
                },

                loadMore() {
                    if (this.isLoadingMore || ! this.hasMore) return;

                    this.isLoadingMore = true;

                    this.fetchPage().finally(() => {
                        this.isLoadingMore = false;
                    });
                },
            },
        });
    </script>
@endPushOnce
