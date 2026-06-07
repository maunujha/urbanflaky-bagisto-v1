<?php

namespace Gabha\Blog\Database\Seeders;

use Gabha\Blog\Models\Blog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class BlogSeeder extends Seeder
{
    /**
     * Seed a handful of starter posts so the storefront grid and listing have
     * content out of the box. Idempotent — keyed on slug.
     */
    public function run(): void
    {
        $posts = [
            [
                'title'             => '5 Ways to Style a Polo T-Shirt for Every Occasion',
                'author'            => 'Urbanflaky Team',
                'short_description' => 'From weekend brunches to smart-casual Fridays — five effortless ways to wear your favourite polo and look put-together every time.',
                'content'           => '<p>The polo t-shirt is the most versatile piece in any wardrobe. Here are five ways to make it work harder for you.</p><h2>1. Smart-Casual at Work</h2><p>Pair a slim-fit polo with chinos and clean sneakers for a relaxed yet professional look.</p><h2>2. Weekend Layering</h2><p>Throw an open overshirt on top and roll the sleeves for an easy weekend vibe.</p><h2>3. Tucked &amp; Tailored</h2><p>Tuck the polo into tailored trousers with a leather belt for dinners and events.</p><h2>4. Sporty Minimal</h2><p>Keep it clean with shorts and white trainers when the weather warms up.</p><h2>5. Monochrome Confidence</h2><p>Match your polo and bottoms in the same tone for a sharp, modern silhouette.</p>',
                'meta_title'        => '5 Ways to Style a Polo T-Shirt | Urbanflaky Style Guide',
                'meta_description'  => 'Learn five easy ways to style a polo t-shirt for work, weekends and evenings. Practical menswear styling tips from Urbanflaky.',
                'meta_keywords'     => 'polo t-shirt styling, how to wear polo, mens style guide, urbanflaky',
                'days_ago'          => 2,
            ],
            [
                'title'             => 'Slim Fit vs Regular Fit: Which T-Shirt Is Right for You?',
                'author'            => 'Urbanflaky Team',
                'short_description' => 'Confused between slim and regular fits? Here is a simple guide to choosing the cut that flatters your body type.',
                'content'           => '<p>Fit changes everything. The same t-shirt can look sharp or sloppy depending on the cut you choose.</p><h2>Slim Fit</h2><p>Tapered through the body and arms, slim fit suits leaner builds and gives a contemporary look.</p><h2>Regular Fit</h2><p>A relaxed, straight cut that offers comfort and works for most body types.</p><h2>How to Choose</h2><p>If you want a modern, structured look go slim. For all-day comfort and an easy drape, choose regular.</p>',
                'meta_title'        => 'Slim Fit vs Regular Fit T-Shirts: A Buyer Guide | Urbanflaky',
                'meta_description'  => 'Slim fit or regular fit? Understand the difference and pick the t-shirt cut that suits your body type with this Urbanflaky guide.',
                'meta_keywords'     => 'slim fit tshirt, regular fit tshirt, tshirt fit guide, mens fashion',
                'days_ago'          => 6,
            ],
            [
                'title'             => 'How to Care for Your Cotton T-Shirts So They Last Longer',
                'author'            => 'Urbanflaky Team',
                'short_description' => 'Simple washing and drying habits that keep your cotton tees soft, bright and shrink-free for years.',
                'content'           => '<p>Good clothes deserve good care. Follow these steps to extend the life of your cotton t-shirts.</p><h2>Wash Cold, Inside Out</h2><p>Cold water and inside-out washing protect colours and prints.</p><h2>Skip the Dryer When You Can</h2><p>Air-drying prevents shrinkage and keeps the fabric soft.</p><h2>Fold, Don\'t Hang</h2><p>Hanging stretches the collar over time. Fold tees to keep their shape.</p>',
                'meta_title'        => 'How to Care for Cotton T-Shirts | Urbanflaky Tips',
                'meta_description'  => 'Make your cotton t-shirts last longer with these easy washing, drying and storage tips from Urbanflaky.',
                'meta_keywords'     => 'tshirt care, how to wash tshirts, cotton tshirt care, garment care tips',
                'days_ago'          => 11,
            ],
            [
                'title'             => 'Building a Capsule Wardrobe Under ₹3000',
                'author'            => 'Urbanflaky Team',
                'short_description' => 'You do not need a big budget to dress well. Here is how to build a mix-and-match capsule wardrobe affordably.',
                'content'           => '<p>A capsule wardrobe is a small collection of versatile pieces that work together. Here is a starter set under ₹3000.</p><h2>The Essentials</h2><ul><li>2 plain polo t-shirts</li><li>2 slim-fit casual tees</li><li>1 neutral overshirt</li></ul><h2>Why It Works</h2><p>Neutral colours mix easily, so a few pieces create many outfits — perfect for everyday wear.</p>',
                'meta_title'        => 'Capsule Wardrobe Under ₹3000 | Affordable Menswear | Urbanflaky',
                'meta_description'  => 'Build a versatile capsule wardrobe under ₹3000 with affordable polos and casual tees from Urbanflaky. Mix-and-match style on a budget.',
                'meta_keywords'     => 'capsule wardrobe, affordable fashion india, budget menswear, urbanflaky',
                'days_ago'          => 16,
            ],
            [
                'title'             => 'The Colours That Never Go Out of Style',
                'author'            => 'Urbanflaky Team',
                'short_description' => 'Trends come and go, but these timeless t-shirt colours belong in every wardrobe season after season.',
                'content'           => '<p>When in doubt, reach for a classic colour. These shades pair with almost anything.</p><h2>White</h2><p>Crisp, clean and endlessly versatile.</p><h2>Black</h2><p>Sleek and slimming — the ultimate go-to.</p><h2>Navy</h2><p>Softer than black but just as easy to style.</p><h2>Olive &amp; Grey</h2><p>Earthy neutrals that add variety without clashing.</p>',
                'meta_title'        => 'Timeless T-Shirt Colours for Every Wardrobe | Urbanflaky',
                'meta_description'  => 'Discover the timeless t-shirt colours that never go out of style. Build a versatile wardrobe with these classic shades from Urbanflaky.',
                'meta_keywords'     => 'classic tshirt colours, timeless fashion, wardrobe basics, urbanflaky',
                'days_ago'          => 21,
            ],
        ];

        foreach ($posts as $post) {
            $daysAgo = $post['days_ago'];
            unset($post['days_ago']);

            $post['status'] = 1;
            $post['published_at'] = Carbon::now()->subDays($daysAgo);
            $post['slug'] = \Illuminate\Support\Str::slug($post['title']);

            Blog::updateOrCreate(['slug' => $post['slug']], $post);
        }
    }
}
