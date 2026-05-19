<?php
require_once 'config.php';
require_once 'support-page-template.php';

renderSupportPage([
    'title' => 'Terms of Service',
    'eyebrow' => 'Terms',
    'summary' => 'The rules for using Maroc PC, placing orders, using accounts, paying for products, reviews, PC builder quotes, after-sales service, and store content.',
    'cards' => [
        ['icon' => 'fa-cart-shopping', 'title' => 'Orders', 'text' => 'Orders are confirmed after checkout validation, stock checks, and payment or COD acceptance.'],
        ['icon' => 'fa-shield-halved', 'title' => 'Warranty', 'text' => 'Warranty and repair handling depends on product category, brand policy, serial verification, and inspection result.'],
        ['icon' => 'fa-scale-balanced', 'title' => 'Fair use', 'text' => 'Accounts, reviews, saved builds, and support tools must be used honestly and lawfully.'],
    ],
    'sections' => [
        [
            'title' => 'Using the Store',
            'paragraphs' => ['By using Maroc PC, creating an account, placing an order, saving a build, posting a review, or opening a support ticket, you agree to these terms.'],
            'items' => [
                'You must provide accurate account, delivery, billing, and contact information.',
                'You are responsible for keeping your account credentials secure.',
                'You may not abuse checkout, loyalty, reviews, wishlist, builder, or after-sales systems.',
            ],
        ],
        [
            'title' => 'Products, Pricing, and Stock',
            'items' => [
                'Product images, specifications, ratings, stock, discounts, and compatibility notes are provided for shopping guidance.',
                'Prices are shown in MAD and may change before an order is placed.',
                'Stock is reserved when an order is placed and may be restored if an order is cancelled or deleted according to store rules.',
                'If a product is incorrectly priced or unavailable because of a technical error, Maroc PC may correct the order before fulfillment.',
            ],
        ],
        [
            'title' => 'Orders and Payments',
            'items' => [
                'A checkout order may be pending, processing, shipped, delivered, or cancelled.',
                'Payment status may be pending, paid, failed, or refunded depending on payment method and support action.',
                'Cash on delivery may require confirmation before dispatch.',
                'Orders may be cancelled for failed payment, unavailable stock, suspected fraud, incorrect details, or customer request before dispatch.',
            ],
        ],
        [
            'title' => 'PC Builder and Recommendations',
            'paragraphs' => ['The PC builder, AI assistant, FPS estimator, and compatibility messages help with selection but do not replace final technical inspection.'],
            'items' => [
                'Compatibility is checked from available product data such as socket, memory type, wattage, and stock.',
                'Final assembly, BIOS updates, thermal checks, and case clearance may still require technician review.',
                'Quotes and saved builds are not guaranteed reservations until products are placed in an order and stock is confirmed.',
            ],
        ],
        [
            'title' => 'Reviews and User Content',
            'items' => [
                'Reviews should be truthful, relevant, and based on real product experience.',
                'Maroc PC may moderate reviews for spam, abuse, personal data, fraud, or irrelevant content.',
                'By submitting content, you allow Maroc PC to display it on the store unless removed or moderated.',
            ],
        ],
        [
            'title' => 'Returns, Refunds, and Warranty',
            'items' => [
                'Eligible returns and exchanges should be requested through the Returns & Refunds page.',
                'Refunds depend on eligibility, inspection, payment status, and product completeness.',
                'Warranty cases may require serial numbers, photos, diagnostics, and manufacturer approval.',
                'Damaged parcels and missing items should be reported as soon as possible, ideally within 24 hours of delivery.',
            ],
        ],
        [
            'title' => 'Liability and Changes',
            'paragraphs' => [
                'Maroc PC works to keep the store accurate and available, but temporary errors, downtime, stock changes, or content mistakes can happen. These terms may be updated as the store evolves.',
            ],
        ],
    ],
]);
