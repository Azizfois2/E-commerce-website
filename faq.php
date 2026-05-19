<?php
require_once 'config.php';
require_once 'support-page-template.php';

renderSupportPage([
    'title' => 'FAQ',
    'eyebrow' => 'Help Center',
    'summary' => 'Answers to common Maroc PC questions about accounts, orders, payment, PC builder compatibility, returns, warranty, shipping, and after-sales service.',
    'cards' => [
        ['icon' => 'fa-user', 'title' => 'Account', 'text' => 'Login, email verification, password reset, two-factor security, and saved builds.'],
        ['icon' => 'fa-desktop', 'title' => 'Hardware', 'text' => 'Product stock, compatibility, PC builder choices, warranty, and installation services.'],
        ['icon' => 'fa-headset', 'title' => 'Support', 'text' => 'Returns, refunds, damaged parcels, missing items, and repairs.'],
    ],
    'sections' => [
        [
            'title' => 'Ordering',
            'faq' => [
                ['q' => 'How do I track my order?', 'a' => 'Sign in, open My Account, then select My Orders. You can see order status, items, and history there.'],
                ['q' => 'Can I cancel an order?', 'a' => 'You can request cancellation before dispatch. If stock has been reserved, cancellation restores stock automatically when the order is cancelled.'],
                ['q' => 'Why is my order pending?', 'a' => 'Pending can mean payment confirmation, COD confirmation, stock check, or address review is still in progress.'],
                ['q' => 'Do prices include everything?', 'a' => 'Product prices are shown in MAD. Shipping, COD fees, discounts, loyalty points, and service fees are calculated at checkout.'],
            ],
        ],
        [
            'title' => 'Payments and Loyalty',
            'faq' => [
                ['q' => 'Which payment methods are supported?', 'a' => 'The checkout flow supports card-style payment, PayPal-style payment, COD, and other simulated wallet/crypto options depending on configuration.'],
                ['q' => 'How do loyalty points work?', 'a' => 'Points can be earned from purchases and may be redeemed at checkout when the loyalty feature is available for your account.'],
                ['q' => 'What happens if payment fails?', 'a' => 'The order may remain pending or fail depending on the payment method. Contact support if money was charged but the order did not complete.'],
            ],
        ],
        [
            'title' => 'PC Builder and Compatibility',
            'faq' => [
                ['q' => 'Does the PC builder check compatibility?', 'a' => 'Yes. It checks product stock, CPU and motherboard socket, motherboard and RAM memory type, PSU wattage, and cooler headroom from available specs.'],
                ['q' => 'Are PC builder quotes final?', 'a' => 'No. A saved build or AI recommendation is guidance. Prices and stock are confirmed when the order is placed.'],
                ['q' => 'Can Maroc PC assemble my build?', 'a' => 'Yes. The builder includes build services such as professional assembly, BIOS update, stress testing, Windows install, and Bazzite + Proton++ Linux setup.'],
            ],
        ],
        [
            'title' => 'Returns, Refunds, and Warranty',
            'faq' => [
                ['q' => 'How do I start a return or refund?', 'a' => 'Open Returns & Refunds, fill the after-sales request form, and include the order number, product, condition, and issue details.'],
                ['q' => 'How long do refunds take?', 'a' => 'After approval and inspection, refunds usually take 3-10 business days depending on payment method.'],
                ['q' => 'What if an item arrives damaged?', 'a' => 'Report it as soon as possible, ideally within 24 hours. Keep all packaging and send photos to support@marocpc.com.'],
                ['q' => 'Do warranties require serial numbers?', 'a' => 'For many products, yes. Serial numbers or product label photos help validate manufacturer warranty coverage.'],
            ],
        ],
        [
            'title' => 'Account and Security',
            'faq' => [
                ['q' => 'I forgot my password. What should I do?', 'a' => 'Use the Forgot Password link on the login page. Maroc PC sends a secure reset link by email using the configured mail system.'],
                ['q' => 'Can I enable two-factor authentication?', 'a' => 'When available in your account security tab, two-factor login sends a one-time code to your email.'],
                ['q' => 'What happens if I delete my account?', 'a' => 'Account deletion can cancel open orders and restore reserved stock where applicable. A restoration period may be available before permanent cleanup.'],
            ],
        ],
    ],
]);
