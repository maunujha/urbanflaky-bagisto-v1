<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const URL_KEY = 'shipping-returns-tab';

    public function up(): void
    {
        if (DB::table('cms_page_translations')->where('url_key', self::URL_KEY)->exists()) {
            return;
        }

        $pageId = DB::table('cms_pages')->insertGetId([
            'layout'     => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cms_page_translations')->insert([
            'cms_page_id'      => $pageId,
            'locale'           => 'en',
            'page_title'       => 'Shipping & Returns',
            'url_key'          => self::URL_KEY,
            'meta_title'       => null,
            'meta_description' => null,
            'meta_keywords'    => null,
            'html_content'     => $this->defaultContent(),
        ]);

        // Associate with the default channel (id=1)
        if (DB::table('channels')->where('id', 1)->exists()) {
            DB::table('cms_page_channels')->insert([
                'cms_page_id' => $pageId,
                'channel_id'  => 1,
            ]);
        }
    }

    public function down(): void
    {
        $translation = DB::table('cms_page_translations')
            ->where('url_key', self::URL_KEY)
            ->first();

        if ($translation) {
            DB::table('cms_pages')->where('id', $translation->cms_page_id)->delete();
        }
    }

    private function defaultContent(): string
    {
        return <<<'HTML'
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:2.5rem;">

  <div>
    <h3 style="font-size:1.1rem;font-weight:600;color:#111;margin:0 0 0.75rem;">Shipping</h3>
    <ul style="list-style:none;padding:0;margin:0;color:#71717a;font-size:1rem;line-height:1.8;">
      <li>Free shipping on all orders across India</li>
      <li>Estimated delivery: 3&ndash;5 business days</li>
      <li>Express delivery available at checkout</li>
      <li>Order tracking shared via SMS &amp; email</li>
    </ul>
  </div>

  <div>
    <h3 style="font-size:1.1rem;font-weight:600;color:#111;margin:0 0 0.75rem;">Returns</h3>
    <p style="color:#71717a;font-size:1rem;margin:0 0 0.5rem;">7-day hassle-free return policy from the date of delivery.</p>
    <ol style="padding-left:1.25rem;margin:0;color:#71717a;font-size:1rem;line-height:2;">
      <li>Contact us within 7 days of delivery</li>
      <li>Pack the item in its original packaging</li>
      <li>Schedule a free pickup from your address</li>
      <li>Refund processed within 5&ndash;7 business days</li>
    </ol>
  </div>

  <div>
    <h3 style="font-size:1.1rem;font-weight:600;color:#111;margin:0 0 0.75rem;">Exchange</h3>
    <p style="color:#71717a;font-size:1rem;margin:0 0 0.5rem;">Not satisfied with the size or colour? We make exchanges simple.</p>
    <ul style="list-style:none;padding:0;margin:0;color:#71717a;font-size:1rem;line-height:1.8;">
      <li>Size &amp; colour exchanges within 7 days</li>
      <li>Subject to stock availability</li>
      <li>Raise a request via email or WhatsApp</li>
      <li>Replacement dispatched within 2 business days</li>
    </ul>
  </div>

</div>
HTML;
    }
};
