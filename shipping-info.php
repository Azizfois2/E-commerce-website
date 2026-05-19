<?php
require_once 'config.php';
require_once 'support-page-template.php';

renderSupportPage([
    'title' => 'Shipping Info',
    'eyebrow' => 'Delivery',
    'summary' => 'Shipping methods, delivery timelines, COD notes, order tracking, package inspection, and what to do if your computer hardware parcel arrives damaged or incomplete.',
    'cards' => [
        ['icon' => 'fa-truck', 'title' => 'Standard', 'text' => 'Estimated around 3-5 business days depending on city and item readiness.'],
        ['icon' => 'fa-bolt', 'title' => 'Express', 'text' => 'Faster delivery where available for urgent components and build parts.'],
        ['icon' => 'fa-map-location-dot', 'title' => 'Tracking', 'text' => 'Track status from your account orders page after checkout.'],
    ],
    'sections' => [
        [
            'title' => 'Delivery Coverage',
            'paragraphs' => [
                'Maroc PC ships computer components, accessories, and eligible build orders across supported Moroccan delivery zones. Some bulky or fragile items may need extra handling confirmation before dispatch.',
            ],
        ],
        [
            'title' => 'Shipping Methods',
            'items' => [
                'Standard delivery: best for normal orders and non-urgent components.',
                'Express delivery: faster handling when available for your address and item type.',
                'Cash on delivery: may require confirmation before dispatch and may not be available for every order value or destination.',
                'Store pickup or special handling may be arranged by support for sensitive hardware when available.',
            ],
        ],
        [
            'title' => 'Processing Time',
            'items' => [
                'Orders are reviewed for payment status, stock reservation, and address completeness before dispatch.',
                'PC build services, BIOS updates, Windows/Linux installation, and stress testing can add preparation time.',
                'Orders placed late in the day, during weekends, or during holidays may begin processing the next business day.',
            ],
        ],
        [
            'title' => 'Tracking Your Order',
            'items' => [
                'Sign in and open My Orders from your account page to see order status and history.',
                'Status can include pending, processing, shipped, out for delivery, delivered, or cancelled.',
                'If tracking does not update after dispatch, contact support with your order number.',
            ],
        ],
        [
            'title' => 'Damaged or Missing Items',
            'items' => [
                'Inspect the package before opening when possible.',
                'Keep packaging, labels, foam, seals, and accessories until the issue is resolved.',
                'Report damaged parcels or missing items through Returns & Refunds within 24 hours when possible.',
                'Send photos of the outer parcel, shipping label, product box, and affected item to support@marocpc.com.',
            ],
        ],
        [
            'title' => 'Delivery Problems',
            'items' => [
                'Wrong address, unreachable phone number, failed COD confirmation, or refused delivery can delay or cancel shipment.',
                'If a delivery attempt fails, contact support quickly so the order can be redirected or rescheduled where possible.',
                'Refunds for undelivered orders depend on payment status, courier return, and order cancellation review.',
            ],
        ],
    ],
]);
