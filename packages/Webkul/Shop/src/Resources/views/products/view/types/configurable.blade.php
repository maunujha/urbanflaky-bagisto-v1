@if (Webkul\Product\Helpers\ProductType::hasVariants($product->type))
    {!! view_render_event('bagisto.shop.products.view.configurable-options.before', ['product' => $product]) !!}

    <v-product-configurable-options :errors="errors"></v-product-configurable-options>

    {!! view_render_event('bagisto.shop.products.view.configurable-options.after', ['product' => $product]) !!}

    @push('scripts')
        <script
            type="text/x-template"
            id="v-product-configurable-options-template"
        >
            <div class="w-[455px] max-w-full max-sm:w-full">
                <input
                    type="hidden"
                    name="selected_configurable_option"
                    id="selected_configurable_option"
                    :value="selectedOptionVariant"
                    ref="selected_configurable_option"
                >

                <div
                    class="mt-5"
                    v-for='(attribute, index) in childAttributes'
                >
                    <!-- Dropdown Options Container -->
                    <template v-if="! attribute.swatch_type || attribute.swatch_type == '' || attribute.swatch_type == 'dropdown'">
                        <!-- Dropdown Label -->
                        <div class="mb-4 flex items-center gap-3 max-sm:mb-1.5">
                            <h2 class="text-xl max-sm:text-base max-sm:font-medium">
                                @{{ attribute.label }}
                            </h2>
                            <button
                                v-if="attribute.label.toLowerCase().includes('size')"
                                type="button"
                                style="font-size:13px;color:#666;text-decoration:underline;text-underline-offset:2px;background:none;border:none;cursor:pointer;padding:0;line-height:1;"
                                @click="openSizeGuide(attribute)"
                            >Size Guide ↗</button>
                        </div>
                        
                        <!-- Dropdown Options -->
                        <v-field
                            as="select"
                            :name="'super_attribute[' + attribute.id + ']'"
                            class="custom-select mb-3 block w-full cursor-pointer rounded-lg border border-zinc-200 bg-white px-5 py-3 text-base text-zinc-500 focus:border-blue-500 focus:ring-blue-500"
                            :class="[errors['super_attribute[' + attribute.id + ']'] ? 'border border-red-500' : '']"
                            :id="'attribute_' + attribute.id"
                            v-model="attribute.selectedValue"
                            rules="required"
                            :label="attribute.label"
                            :aria-label="attribute.label"
                            :disabled="attribute.disabled"
                            @change="configure(attribute, $event.target.value)"
                        >
                            <option
                                v-for='(option, index) in attribute.options'
                                :value="option.id"
                            >
                                @{{ option.label }}
                            </option>
                        </v-field>
                    </template>

                    <!-- Swatch Options Container -->
                    <template v-else>
                        <!-- Option Label -->
                        <div class="mb-4 flex items-center gap-3 max-sm:mb-2">
                            <h2 class="text-xl max-sm:text-base">
                                @{{ attribute.label }}
                            </h2>
                            <button
                                v-if="attribute.label.toLowerCase().includes('size')"
                                type="button"
                                style="font-size:13px;color:#666;text-decoration:underline;text-underline-offset:2px;background:none;border:none;cursor:pointer;padding:0;line-height:1;"
                                @click="openSizeGuide(attribute)"
                            >Size Guide ↗</button>
                        </div>

                        <!-- Swatch Options -->
                        <div class="flex items-center gap-3">
                            <template v-for="(option, index) in attribute.options">
                                <template v-if="option.id">
                                    <!-- Color Swatch Options -->
                                    <label
                                        class="relative -m-0.5 flex cursor-pointer items-center justify-center rounded-full p-0.5 focus:outline-none"
                                        :class="{'ring-2 ring-gray-900' : option.id == attribute.selectedValue}"
                                        :title="option.label"
                                        v-if="attribute.swatch_type == 'color'"
                                    >
                                        <v-field
                                            type="radio"
                                            :name="'super_attribute[' + attribute.id + ']'"
                                            :value="option.id"
                                            v-slot="{ field }"
                                            rules="required"
                                            :label="attribute.label"
                                            :aria-label="attribute.label"
                                        >
                                            <input
                                                type="radio"
                                                :name="'super_attribute[' + attribute.id + ']'"
                                                :value="option.id"
                                                v-bind="field"
                                                :id="'attribute_' + attribute.id"
                                                :aria-labelledby="'color-choice-' + index + '-label'"
                                                class="peer sr-only"
                                                @click="configure(attribute, $event.target.value)"
                                            />
                                        </v-field>

                                        <span
                                            class="h-8 w-8 rounded-full border border-gray-200 max-sm:h-[25px] max-sm:w-[25px]"
                                            tabindex="0"
                                            :style="{ 'background-color': option.swatch_value }"
                                        ></span>
                                    </label>

                                    <!-- Image Swatch Options -->
                                    <label 
                                        class="group relative flex h-[60px] w-[60px] cursor-pointer items-center justify-center overflow-hidden rounded-md border bg-white font-medium uppercase text-gray-900 hover:bg-gray-50 sm:py-6"
                                        :class="{'border-navyBlue' : option.id == attribute.selectedValue }"
                                        :title="option.label"
                                        v-if="attribute.swatch_type == 'image'"
                                    >
                                        <v-field
                                            type="radio"
                                            :name="'super_attribute[' + attribute.id + ']'"
                                            v-model="attribute.selectedValue"
                                            :value="option.id"
                                            v-slot="{ field }"
                                            rules="required"
                                            :label="attribute.label"
                                            :aria-label="attribute.label"
                                        >
                                            <input
                                                type="radio"
                                                :name="'super_attribute[' + attribute.id + ']'"
                                                :value="option.id"
                                                v-bind="field"
                                                :id="'attribute_' + attribute.id"
                                                :aria-labelledby="'color-choice-' + index + '-label'"
                                                class="peer sr-only"
                                                @click="configure(attribute, $event.target.value)"
                                            />
                                        </v-field>

                                        <img
                                            :src="option.swatch_value"
                                            :title="option.label"
                                        />
                                    </label>

                                    <!-- Text Swatch Options -->
                                    <label 
                                        class="group relative flex h-fit min-w-fit cursor-pointer items-center justify-center rounded-full border border-gray-300 bg-white px-5 py-3 font-medium uppercase text-gray-900 hover:bg-gray-50 max-sm:h-fit max-sm:w-fit max-sm:px-3.5 max-sm:py-2"
                                        :class="{'border-transparent !bg-navyBlue text-white' : option.id == attribute.selectedValue }"
                                        :title="option.label"
                                        v-if="attribute.swatch_type == 'text'"
                                    >
                                        <v-field
                                            type="radio"
                                            :name="'super_attribute[' + attribute.id + ']'"
                                            :value="option.id"
                                            v-model="attribute.selectedValue"
                                            v-slot="{ field }"
                                            rules="required"
                                            :label="attribute.label"
                                            :aria-label="attribute.label"
                                        >
                                            <input
                                                type="radio"
                                                :name="'super_attribute[' + attribute.id + ']'"
                                                :value="option.id"
                                                v-bind="field"
                                                :id="'attribute_' + attribute.id"
                                                class="peer sr-only"
                                                :aria-labelledby="'color-choice-' + index + '-label'"
                                                @click="configure(attribute, $event.target.value)"
                                            />
                                        </v-field>

                                        <span class="text-lg max-sm:text-sm">
                                            @{{ option.label }}
                                        </span>

                                        <span
                                            class="pointer-events-none absolute -inset-px rounded-full"
                                            role="presentation"
                                        >
                                        </span>
                                    </label>
                                </template>
                            </template>

                            <span
                                class="text-sm text-gray-600 max-sm:text-xs"
                                v-if="! attribute.options.length"
                            >
                                @lang('shop::app.products.view.type.configurable.select-above-options')
                            </span>
                        </div>
                    </template>

                    <v-error-message
                        :name="'super_attribute[' + attribute.id + ']'"
                        v-slot="{ message }"
                    >
                        <p class="mt-1 text-xs italic text-red-500">
                            @{{ message }}
                        </p>
                    </v-error-message>
                </div>

                <!-- Size Guide Modal -->
                <teleport to="body">
                    <div
                        v-if="sizeGuideOpen"
                        style="position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;"
                        @click.self="closeSizeGuide"
                    >
                        <div style="background:#fff;border-radius:12px;max-width:480px;width:100%;padding:28px 24px 24px;position:relative;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
                            <!-- Close button -->
                            <button
                                type="button"
                                style="position:absolute;top:14px;right:16px;font-size:20px;line-height:1;background:none;border:none;cursor:pointer;color:#888;padding:4px;"
                                aria-label="Close size guide"
                                @click="closeSizeGuide"
                            >✕</button>

                            <h3 style="font-size:17px;font-weight:700;margin-bottom:4px;color:#111;">Size Guide</h3>
                            <p style="font-size:12px;color:#888;margin-bottom:16px;">All measurements in centimetres (cm)</p>

                            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                                <thead>
                                    <tr style="background:#f5f5f5;">
                                        <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e0e0e0;font-weight:600;color:#333;">Size</th>
                                        <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e0e0e0;font-weight:600;color:#333;">Chest</th>
                                        <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e0e0e0;font-weight:600;color:#333;">Shoulder</th>
                                        <th style="padding:10px 12px;text-align:left;border-bottom:2px solid #e0e0e0;font-weight:600;color:#333;">Length</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="row in sizeChartRows"
                                        :key="row.size"
                                        :style="isSelectedSize(row.size) ? 'background:#c7eb31;font-weight:600;' : 'background:#fff;'"
                                    >
                                        <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;">@{{ row.size }}</td>
                                        <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;">@{{ row.chest }}</td>
                                        <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;">@{{ row.shoulder }}</td>
                                        <td style="padding:10px 12px;border-bottom:1px solid #f0f0f0;">@{{ row.length }}</td>
                                    </tr>
                                </tbody>
                            </table>

                            <p style="font-size:11px;color:#aaa;margin-top:14px;line-height:1.5;">Measurements are approximate and may vary slightly by style.</p>
                        </div>
                    </div>
                </teleport>
            </div>
        </script>

        <script type="module">
            let galleryImages = @json(product_image()->getGalleryImages($product));

            const defaultProductImages = Object.freeze(@json(product_image()->getGalleryImages($product)));

            app.component('v-product-configurable-options', {
                template: '#v-product-configurable-options-template',

                props: ['errors'],

                data() {
                    return {
                        config: @json(app('Webkul\Product\Helpers\ConfigurableOption')->getConfigurationConfig($product)),

                        childAttributes: [],

                        possibleOptionVariant: null,

                        selectedOptionVariant: '',

                        galleryImages: [],

                        sizeGuideOpen: false,

                        sizeGuideAttribute: null,

                        sizeChartRows: [
                            { size: 'XS',  chest: '84–88',   shoulder: '38', length: '67' },
                            { size: 'S',   chest: '88–92',   shoulder: '40', length: '69' },
                            { size: 'M',   chest: '92–96',   shoulder: '42', length: '71' },
                            { size: 'L',   chest: '96–100',  shoulder: '44', length: '73' },
                            { size: 'XL',  chest: '100–106', shoulder: '46', length: '75' },
                            { size: 'XXL', chest: '106–112', shoulder: '48', length: '77' },
                        ],
                    }
                },

                mounted() {
                    document.addEventListener('keydown', (e) => {
                        if (e.key === 'Escape' && this.sizeGuideOpen) {
                            this.sizeGuideOpen = false;
                        }
                    });

                    let attributes = JSON.parse(JSON.stringify(this.config)).attributes.slice();

                    let index = attributes.length;

                    while (index--) {
                        let attribute = attributes[index];

                        attribute.options = [];

                        if (index) {
                            attribute.disabled = true;
                        } else {
                            this.fillAttributeOptions(attribute);
                        }

                        attribute = Object.assign(attribute, {
                            childAttributes: this.childAttributes.slice(),
                            prevAttribute: attributes[index - 1],
                            nextAttribute: attributes[index + 1]
                        });

                        this.childAttributes.unshift(attribute);
                    }

                    this.preloadVariantImages();
                },

                methods: {
                    configure(attribute, optionId) {
                        this.possibleOptionVariant = this.getPossibleOptionVariant(attribute, optionId);

                        if (optionId) {
                            attribute.selectedValue = optionId;
                            
                            if (attribute.nextAttribute) {
                                attribute.nextAttribute.disabled = false;

                                this.clearAttributeSelection(attribute.nextAttribute);

                                this.fillAttributeOptions(attribute.nextAttribute);

                                this.resetChildAttributes(attribute.nextAttribute);
                            } else {
                                this.selectedOptionVariant = this.possibleOptionVariant;
                            }
                        } else {
                            this.clearAttributeSelection(attribute);

                            this.clearAttributeSelection(attribute.nextAttribute);

                            this.resetChildAttributes(attribute);
                        }

                        this.reloadPrice();
                        
                        this.reloadImages();
                    },

                    getPossibleOptionVariant(attribute, optionId) {
                        let matchedOptions = attribute.options.filter(option => option.id == optionId);

                        if (matchedOptions[0]?.allowedProducts) {
                            return matchedOptions[0].allowedProducts[0];
                        }

                        return undefined;
                    },

                    fillAttributeOptions(attribute) {
                        let options = this.config.attributes.find(tempAttribute => tempAttribute.id === attribute.id)?.options;

                        attribute.options = [{
                            'id': '',
                            'label': "@lang('shop::app.products.view.type.configurable.select-options')",
                            'products': []
                        }];

                        if (! options) {
                            return;
                        }

                        let prevAttributeSelectedOption = attribute.prevAttribute?.options.find(option => option.id == attribute.prevAttribute.selectedValue);

                        let index = 1;

                        for (let i = 0; i < options.length; i++) {
                            let allowedProducts = [];

                            if (prevAttributeSelectedOption) {
                                for (let j = 0; j < options[i].products.length; j++) {
                                    if (prevAttributeSelectedOption.allowedProducts && prevAttributeSelectedOption.allowedProducts.includes(options[i].products[j])) {
                                        allowedProducts.push(options[i].products[j]);
                                    }
                                }
                            } else {
                                allowedProducts = options[i].products.slice(0);
                            }

                            if (allowedProducts.length > 0) {
                                options[i].allowedProducts = allowedProducts;

                                attribute.options[index++] = options[i];
                            }
                        }
                    },

                    resetChildAttributes(attribute) {
                        if (! attribute.childAttributes) {
                            return;
                        }

                        attribute.childAttributes.forEach(function (set) {
                            set.selectedValue = null;

                            set.disabled = true;
                        });
                    },

                    clearAttributeSelection (attribute) {
                        if (! attribute) {
                            return;
                        }

                        attribute.selectedValue = null;

                        this.selectedOptionVariant = null;
                    },

                    reloadPrice () {
                        let selectedOptionCount = this.childAttributes.filter(attribute => attribute.selectedValue).length;

                        let finalPrice   = document.querySelector('.final-price');
                        let regularPrice = document.querySelector('.regular-price');
                        let priceLabel   = document.querySelector('.price-label');

                        if (! finalPrice) return;

                        let configVariant = this.config.variant_prices[this.possibleOptionVariant];
                        let allSelected   = this.childAttributes.length === selectedOptionCount;

                        if (allSelected && configVariant) {
                            if (priceLabel) priceLabel.style.display = 'none';

                            const displayPrice = parseFloat(configVariant.regular.price) > parseFloat(configVariant.final.price)
                                ? configVariant.final.formatted_price
                                : configVariant.regular.formatted_price;

                            finalPrice.innerHTML = displayPrice;

                            if (regularPrice) {
                                if (parseFloat(configVariant.regular.price) > parseFloat(configVariant.final.price)) {
                                    regularPrice.style.display = 'block';
                                    regularPrice.innerHTML     = configVariant.regular.formatted_price;
                                } else {
                                    regularPrice.style.display = 'none';
                                }
                            }

                            this.$emitter.emit('configurable-variant-selected-event', this.possibleOptionVariant);

                            this.$emitter.emit('configurable-variant-price-updated', {
                                price:     displayPrice,
                                variantId: this.possibleOptionVariant,
                            });
                        } else {
                            if (priceLabel) priceLabel.style.display = 'inline-block';

                            finalPrice.innerHTML = this.config.regular.formatted_price;

                            if (regularPrice) regularPrice.style.display = 'none';

                            this.$emitter.emit('configurable-variant-selected-event', 0);

                            this.$emitter.emit('configurable-variant-price-updated', {
                                price:     null,
                                variantId: null,
                            });
                        }
                    },

                    preloadVariantImages () {
                        // Silently preload all variant images after mount so
                        // switching variants feels instant (images served from cache)
                        this.$nextTick(() => {
                            Object.values(this.config.variant_images).forEach(images => {
                                images.forEach(image => {
                                    if (image.large_image_url) {
                                        new Image().src = image.large_image_url;
                                    }

                                    if (image.medium_image_url) {
                                        new Image().src = image.medium_image_url;
                                    }
                                });
                            });
                        });
                    },

                    reloadImages () {
                        galleryImages.splice(0, galleryImages.length);

                        if (this.possibleOptionVariant) {
                            (this.config.variant_images[this.possibleOptionVariant] || []).forEach(function(image) {
                                galleryImages.push(image);
                            });

                            (this.config.variant_videos[this.possibleOptionVariant] || []).forEach(function(video) {
                                galleryImages.push(video);
                            });
                        }

                        // Fall back to parent product images so gallery never goes blank
                        if (! galleryImages.length) {
                            defaultProductImages.forEach(function(image) {
                                galleryImages.push(image);
                            });
                        }

                        this.$emitter.emit('configurable-variant-update-images-event', galleryImages);

                        // Direct ref update kept as compatibility fallback
                        if (this.$parent?.$parent?.$refs?.gallery) {
                            this.$parent.$parent.$refs.gallery.media.images = [...galleryImages];
                        }
                    },

                    openSizeGuide(attribute) {
                        this.sizeGuideAttribute = attribute;
                        this.sizeGuideOpen = true;
                    },

                    closeSizeGuide() {
                        this.sizeGuideOpen = false;
                    },

                    isSelectedSize(size) {
                        if (!this.sizeGuideAttribute) return false;
                        const selected = this.sizeGuideAttribute.options.find(
                            o => o.id === this.sizeGuideAttribute.selectedValue
                        );
                        return selected && selected.label.trim().toUpperCase() === size.toUpperCase();
                    },
                }
            });

        </script>
    @endpush

@endif