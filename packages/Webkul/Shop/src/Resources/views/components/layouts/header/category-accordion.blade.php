{{--
    Shared recursive category accordion node.

    Used by BOTH the desktop "All" drawer and the mobile drawer. Registered once
    (pushOnce is keyed) no matter how many times this partial is included, so the
    same markup + logic drives both surfaces.

    Usage from any drawer template:
        <v-category-node :category="category" :level="0"></v-category-node>
--}}
@pushOnce('scripts', 'uf-category-accordion')
    <style>
        /* Smooth height expand/collapse without max-height guesswork. */
        .uf-acc         { display: grid; grid-template-rows: 0fr; transition: grid-template-rows .32s ease; }
        .uf-acc.is-open { grid-template-rows: 1fr; }
        .uf-acc > div   { overflow: hidden; }
    </style>

    <script type="text/x-template" id="v-category-node-template">
        <div class="border-b border-white/[0.06] last:border-b-0">
            <!-- Row -->
            <div
                class="flex items-center justify-between gap-3 transition-colors duration-200 hover:bg-white/[0.03]"
                :style="{ paddingInlineStart: indent + 'px' }"
            >
                <!-- Leaf → direct link -->
                <a
                    v-if="! hasChildren"
                    :href="category.url"
                    class="block flex-1 py-3.5 pr-6 transition-colors"
                    :class="nameClass"
                >@{{ category.name }}</a>

                <!-- Branch → toggle children -->
                <button
                    v-else
                    type="button"
                    @click="expanded = ! expanded"
                    class="flex flex-1 items-center justify-between py-3.5 pr-6 text-left transition-colors"
                    :class="expanded ? 'text-uf-accent' : nameClass"
                    :aria-expanded="expanded ? 'true' : 'false'"
                >
                    <span>@{{ category.name }}</span>

                    <span
                        class="icon-arrow-down shrink-0 text-base transition-transform duration-300"
                        :class="expanded ? 'rotate-180 text-uf-accent' : 'text-white/40'"
                    ></span>
                </button>
            </div>

            <!-- Expandable children -->
            <div
                v-if="hasChildren"
                class="uf-acc"
                :class="{ 'is-open': expanded }"
            >
                <div>
                    <v-category-node
                        v-for="child in category.children"
                        :key="child.id"
                        :category="child"
                        :level="level + 1"
                    ></v-category-node>

                    <!-- "View all" CTA — sits AFTER the child categories -->
                    <a
                        :href="category.url"
                        class="flex items-center gap-1.5 py-3 font-poppins text-[11px] font-semibold uppercase tracking-[2px] text-uf-accent transition-opacity hover:opacity-80"
                        :style="{ paddingInlineStart: (indent + 18) + 'px' }"
                    >
                        View All @{{ category.name }}
                        <span class="icon-arrow-right rtl:icon-arrow-left text-sm"></span>
                    </a>
                </div>
            </div>
        </div>
    </script>

    <script type="module">
        /* Recursive accordion node — renders itself for each level of children.
           Shared by the desktop and mobile category drawers. */
        app.component('v-category-node', {
            template: '#v-category-node-template',

            props: {
                category: { type: Object, required: true },
                level:    { type: Number, default: 0 },
            },

            data() {
                return { expanded: false };
            },

            computed: {
                hasChildren() {
                    return !! (this.category.children && this.category.children.length);
                },

                /* Indent deeper levels so the hierarchy reads clearly. */
                indent() {
                    return 24 + this.level * 18;
                },

                /* Font weight / colour steps down as the tree gets deeper. */
                nameClass() {
                    if (this.level === 0) {
                        return 'font-poppins text-[15px] font-semibold text-white hover:text-uf-accent';
                    }

                    if (this.level === 1) {
                        return 'text-[14px] font-medium text-zinc-300 hover:text-uf-accent';
                    }

                    return 'text-[13px] font-normal text-zinc-400 hover:text-white';
                },
            },
        });
    </script>
@endPushOnce
