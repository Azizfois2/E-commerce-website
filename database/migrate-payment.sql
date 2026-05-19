-- Migration: Add payment transaction tracking to orders table
-- Run this in phpMyAdmin or MySQL CLI

ALTER TABLE `orders`
  ADD COLUMN `transaction_id` VARCHAR(64) DEFAULT NULL AFTER `payment_status`,
  ADD COLUMN `paypal_order_id` VARCHAR(64) DEFAULT NULL AFTER `transaction_id`;

-- Update the payment_method column to include 'cod' and 'paypal'
-- (Already varchar(50) so no change needed, just documenting valid values)
-- Valid values: credit-card, paypal, bitcoin, apple-pay, google-pay, cod
