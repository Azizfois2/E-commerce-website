<?php
require_once 'config.php';
require_once 'support-page-template.php';

renderSupportPage([
    'title' => 'Privacy Policy',
    'eyebrow' => 'Privacy',
    'summary' => 'How Maroc PC collects, uses, protects, and shares customer data for accounts, orders, support, payments, reviews, wishlists, and saved PC builds.',
    'cards' => [
        ['icon' => 'fa-user-shield', 'title' => 'Account security', 'text' => 'We use session cookies, CSRF tokens, password hashing, and optional two-factor login protections.'],
        ['icon' => 'fa-receipt', 'title' => 'Order operations', 'text' => 'Your contact, delivery, payment status, and order item data are used to process and support purchases.'],
        ['icon' => 'fa-envelope', 'title' => 'Support contact', 'text' => 'Email support@marocpc.com to ask privacy questions or request account help.'],
    ],
    'sections' => [
        [
            'title' => 'Information We Collect',
            'paragraphs' => ['We collect the information needed to run an online computer hardware store and provide after-sales support.'],
            'items' => [
                'Account information: name, email, hashed password, email verification status, loyalty tier, and security settings.',
                'Order information: items purchased, shipping and billing addresses, payment method, transaction reference, order status, and delivery estimate.',
                'Support information: after-sales tickets, return/refund details, warranty details, serial numbers when provided, and messages sent to support.',
                'Store activity: wishlist items, saved PC builds, product reviews, review votes, restock notifications, and newsletter subscriptions.',
                'Technical data: session cookies, CSRF tokens, browser/device basics, and logs needed to secure and debug the site.',
            ],
        ],
        [
            'title' => 'How We Use Information',
            'items' => [
                'Create and secure your Maroc PC account.',
                'Process orders, reserve stock, calculate loyalty points, and send order emails.',
                'Handle returns, refunds, warranty claims, damaged parcels, and missing-item reports.',
                'Show saved builds, wishlists, reviews, and account history.',
                'Send verification, password reset, two-factor, order, newsletter, and support emails.',
                'Prevent fraud, abuse, duplicate payment issues, unauthorized access, and account misuse.',
            ],
        ],
        [
            'title' => 'Payments',
            'paragraphs' => [
                'Payment information is used to complete checkout and track payment status. The current checkout supports simulated card/payment flows and order references. If a real payment processor is connected later, sensitive card data should be handled by that payment provider rather than stored directly by Maroc PC.',
            ],
        ],
        [
            'title' => 'Sharing',
            'paragraphs' => ['We share information only when needed to operate the store, deliver orders, provide support, or comply with legal obligations.'],
            'items' => [
                'Delivery partners may receive shipping contact and address information.',
                'Email providers may process transactional emails such as verification, password reset, and order updates.',
                'Payment providers may process payment data when a live payment method is used.',
                'Manufacturers or distributors may receive warranty details, serial numbers, and fault descriptions for service cases.',
            ],
        ],
        [
            'title' => 'Data Retention',
            'items' => [
                'Order records are kept for business, tax, support, and warranty tracking.',
                'Password reset and email verification tokens expire and are marked used or deleted after use.',
                'After-sales tickets are kept to document service history and warranty decisions.',
                'Deleted accounts may enter a short restoration period before permanent cleanup, as shown in the account deletion flow.',
            ],
        ],
        [
            'title' => 'Your Choices',
            'items' => [
                'You can update profile details from your account page.',
                'You can delete or restore your account from the security/account area when available.',
                'You can request support for privacy, order, or account questions at support@marocpc.com.',
                'You can control cookies and local storage through your browser settings, but some site features may stop working.',
            ],
        ],
    ],
]);
