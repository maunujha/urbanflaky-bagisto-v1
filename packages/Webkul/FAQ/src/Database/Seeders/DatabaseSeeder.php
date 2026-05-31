<?php

namespace Webkul\FAQ\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the default FAQ categories and a few sample FAQs.
     *
     * Idempotent: categories are matched by slug, FAQs by (category, question),
     * so re-running will not create duplicates.
     */
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            'Orders & Tracking' => [
                [
                    'How do I track my order?',
                    'Once your order ships, we email you a tracking link. You can also track it any time from <strong>My Orders</strong> in your account.',
                ],
                [
                    'Can I cancel my order?',
                    'You can cancel an order from <strong>My Orders</strong> while it is still in the "Processing" stage. Once it has been dispatched, it can no longer be cancelled but can be returned after delivery.',
                ],
                [
                    'I have not received an order confirmation. What should I do?',
                    'Order confirmations are sent by email and SMS within a few minutes. Please check your spam folder. If you still do not see it, contact our support team with your registered phone number.',
                ],
            ],
            'Shipping & Delivery' => [
                [
                    'How long does delivery take?',
                    'Orders are usually delivered within 3–7 business days, depending on your location. Metro cities are typically faster.',
                ],
                [
                    'Do you ship across India?',
                    'Yes, we ship to all serviceable pin codes across India. You can check serviceability for your pin code at checkout.',
                ],
                [
                    'How much are the shipping charges?',
                    'Shipping is free on most orders. Any applicable shipping charge is shown clearly at checkout before you pay.',
                ],
            ],
            'Returns & Refunds' => [
                [
                    'How do returns work?',
                    'You can request a return from <strong>My Orders</strong> within the return window mentioned on the product page. Keep the item unused with its original tags and packaging.',
                ],
                [
                    'When will I receive my refund?',
                    'Once your return is picked up and quality-checked, refunds are processed within 5–7 business days to your original payment method or as store credit.',
                ],
                [
                    'Can I exchange a product for a different size?',
                    'Yes. Raise an exchange request from <strong>My Orders</strong> and select the size you need, subject to availability.',
                ],
            ],
            'Payments' => [
                [
                    'What payment methods do you accept?',
                    'We accept UPI, credit/debit cards, net banking, popular wallets, and Cash on Delivery (where available).',
                ],
                [
                    'Is it safe to pay online on Urbanflaky?',
                    'Absolutely. All payments are processed through secure, PCI-compliant payment gateways. We never store your card details.',
                ],
                [
                    'My payment failed but money was deducted. What now?',
                    'Failed-payment deductions are automatically reversed by your bank within 5–7 business days. If it is not reversed, contact us with your transaction reference.',
                ],
            ],
            'Account & Profile' => [
                [
                    'How do I create an account?',
                    'Tap the account icon and sign up with your phone number or email. You can verify with an OTP and start shopping right away.',
                ],
                [
                    'How do I update my address or profile details?',
                    'Go to your account, open <strong>Profile</strong> or <strong>Addresses</strong>, and edit your details. Changes are saved instantly.',
                ],
                [
                    'I forgot my password. How do I reset it?',
                    'Use the "Forgot password" link on the login page. We will send a reset link to your registered email.',
                ],
            ],
            'Products & Sizing' => [
                [
                    'How do I choose the right size?',
                    'Each product page has a detailed size chart. Measure yourself and match it to the chart for the best fit.',
                ],
                [
                    'Are the product colours accurate?',
                    'We photograph products to represent colours as accurately as possible. Slight variation may occur due to screen settings and lighting.',
                ],
                [
                    'What material are your t-shirts made of?',
                    'Our polos and casuals use premium, breathable cotton blends designed for everyday comfort. Exact composition is listed on each product page.',
                ],
            ],
        ];

        $categorySort = 1;

        foreach ($data as $categoryName => $faqs) {
            $slug = Str::slug($categoryName);

            $categoryId = DB::table('faq_categories')->where('slug', $slug)->value('id');

            if (! $categoryId) {
                $categoryId = DB::table('faq_categories')->insertGetId([
                    'name'       => $categoryName,
                    'slug'       => $slug,
                    'sort_order' => $categorySort,
                    'status'     => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            $faqSort = 1;

            foreach ($faqs as [$question, $answer]) {
                $exists = DB::table('faqs')
                    ->where('faq_category_id', $categoryId)
                    ->where('question', $question)
                    ->exists();

                if (! $exists) {
                    DB::table('faqs')->insert([
                        'faq_category_id' => $categoryId,
                        'question'        => $question,
                        'answer'          => $answer,
                        'sort_order'      => $faqSort,
                        'status'          => 1,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                }

                $faqSort++;
            }

            $categorySort++;
        }
    }
}
