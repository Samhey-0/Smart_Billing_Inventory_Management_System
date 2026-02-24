-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 19, 2026 at 07:06 AM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `inspire_shoes`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `full_name`, `email`, `phone`, `address`, `city`, `created_at`, `updated_at`) VALUES
(1, 'Ali', 'Ali@email.com', '03334567890', '123 Main Street, Apt 4B', 'karachi', '2026-02-18 06:48:20', '2026-02-18 07:09:54'),
(2, 'sarah', 'sarah.j@email.com', '03387654321', '456 Oak Avenue', 'Lahore', '2026-02-18 06:48:20', '2026-02-18 07:10:15'),
(3, 'Ahsan', 'Ahsan.b@email.com', '03545123456', '789 Pine Road, Suite 100', 'sargodha', '2026-02-18 06:48:20', '2026-02-18 07:10:39'),
(4, 'Noman', 'imran@email.com', '03334567890', '', 'Khushab', '2026-02-18 07:11:08', '2026-02-18 07:11:08'),
(5, 'Akram', 'akram@email.com', '0333432490', '', 'Rawalpindi', '2026-02-18 12:43:40', '2026-02-18 12:43:40'),
(6, 'Hassan', 'hassan@gmail.com', '03215488994', 'street 4', 'Rawat', '2026-02-19 03:21:26', '2026-02-19 03:21:26');

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_rate` decimal(5,2) NOT NULL DEFAULT 16.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `discount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `status` enum('Paid','Unpaid','Cancelled') DEFAULT 'Unpaid',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoices`
--

INSERT INTO `invoices` (`id`, `customer_id`, `user_id`, `subtotal`, `tax_rate`, `tax_amount`, `discount`, `grand_total`, `status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 219.98, 16.00, 35.20, 0.00, 255.18, 'Paid', 'First purchase - loyal customer', '2026-02-18 06:48:21', '2026-02-19 05:16:17'),
(2, 2, 1, 249.98, 16.00, 40.00, 10.00, 279.98, 'Paid', '', '2026-02-18 06:48:21', '2026-02-19 03:18:55'),
(6, 5, 1, 400.00, 16.00, 64.00, 0.00, 464.00, 'Cancelled', 'Good customer', '2026-02-18 12:51:23', '2026-02-19 05:17:13'),
(7, 6, 1, 10000.00, 16.00, 1600.00, 0.00, 11600.00, 'Paid', 'Paid slip, best customer', '2026-02-19 03:22:35', '2026-02-19 03:22:56'),
(8, 4, 1, 30000.00, 16.00, 4800.00, 0.00, 34800.00, 'Paid', '', '2026-02-19 05:38:16', '2026-02-19 05:57:05'),
(9, 1, 1, 2000.00, 16.00, 320.00, 0.00, 2320.00, 'Paid', '', '2026-02-19 05:51:10', '2026-02-19 05:51:22');

-- --------------------------------------------------------

--
-- Table structure for table `invoice_items`
--

CREATE TABLE `invoice_items` (
  `id` int(11) NOT NULL,
  `invoice_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `size` varchar(50) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `line_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `invoice_items`
--

INSERT INTO `invoice_items` (`id`, `invoice_id`, `product_id`, `product_name`, `size`, `quantity`, `unit_price`, `line_total`) VALUES
(1, 1, 1, 'Air Runner Pro', '42', 1, 129.99, 129.99),
(2, 1, 3, 'Street Style Sneakers', '40', 1, 89.99, 89.99),
(3, 2, 2, 'Classic Leather Oxford', '44', 1, 189.99, 189.99),
(4, 2, 4, 'Trail Blazer X', '43', 1, 159.99, 159.99),
(5, 6, 5, 'Premium Sandals', '42', 10, 40.00, 400.00),
(6, 7, 4, 'Trail Blazer X', '43', 20, 150.00, 3000.00),
(7, 7, 1, 'Air Runner Pro', '42', 30, 100.00, 3000.00),
(8, 7, 2, 'Classic Leather Oxford', '44', 20, 200.00, 4000.00),
(9, 8, 5, 'Premium Sandals', '42', 10, 3000.00, 30000.00),
(10, 9, 1, 'Air Runner Pro', '42', 1, 2000.00, 2000.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `brand` varchar(100) NOT NULL,
  `size` varchar(50) NOT NULL,
  `color` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock_qty` int(11) NOT NULL DEFAULT 0,
  `image_path` varchar(255) DEFAULT NULL,
  `supplier_name` varchar(100) DEFAULT NULL,
  `supplier_phone` varchar(20) DEFAULT NULL,
  `purchase_price` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `name`, `description`, `brand`, `size`, `color`, `price`, `stock_qty`, `image_path`, `supplier_name`, `supplier_phone`, `purchase_price`, `created_at`, `updated_at`) VALUES
(1, 'Air Runner Pro', 'High-performance running shoes with advanced cushioning technology', 'Nike', '42', 'Black/Red', 2000.00, 18, 'e25230f27598d56c603c58055ccd8b85.jpg', NULL, NULL, 0.00, '2026-02-18 06:48:20', '2026-02-19 05:51:10'),
(2, 'Classic Leather Oxford', 'Elegant leather oxford shoes for formal occasions', 'Clarks', '44', 'Brown', 2500.00, 14, NULL, NULL, NULL, 0.00, '2026-02-18 06:48:20', '2026-02-19 05:32:04'),
(3, 'Street Style Sneakers', 'Trendy sneakers perfect for casual everyday wear', 'Adidas', '40', 'White/Grey', 1000.00, 74, NULL, NULL, NULL, 0.00, '2026-02-18 06:48:20', '2026-02-19 05:32:49'),
(4, 'Trail Blazer X', 'Rugged hiking boots with superior grip and durability', 'Timberland', '43', 'Tan', 1500.00, 4, NULL, NULL, NULL, 0.00, '2026-02-18 06:48:20', '2026-02-19 05:31:42'),
(5, 'Premium Sandals', 'Comfortable sandals with memory foam insole', 'Skechers', '42', 'Navy', 3000.00, 40, NULL, NULL, NULL, 0.00, '2026-02-18 06:48:20', '2026-02-19 05:38:16');

-- --------------------------------------------------------

--
-- Table structure for table `stock_log`
--

CREATE TABLE `stock_log` (
  `id` int(10) UNSIGNED NOT NULL,
  `product_id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `adjustment_type` enum('add','subtract') NOT NULL,
  `quantity` int(10) UNSIGNED NOT NULL,
  `reason` varchar(255) NOT NULL,
  `stock_before` int(11) NOT NULL,
  `stock_after` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `stock_log`
--

INSERT INTO `stock_log` (`id`, `product_id`, `user_id`, `adjustment_type`, `quantity`, `reason`, `stock_before`, `stock_after`, `created_at`) VALUES
(1, 2, 1, 'add', 5, 'Returned by customer', 9, 14, '2026-02-19 03:51:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@inspireshoes.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active', '2026-02-18 06:48:20', '2026-02-18 12:33:12'),
(4, 'staff', 'staff@gmail.com', '$2y$10$sEXUh0oY4br4FM.422jjruaNRP.xlqt44H9bvt0bVu9qhxkvUxrn.', 'staff', 'active', '2026-02-19 05:14:04', '2026-02-19 05:18:46');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_full_name` (`full_name`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_city` (`city`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_customer_id` (`customer_id`),
  ADD KEY `idx_user_id` (`user_id`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_created_at` (`created_at`);

--
-- Indexes for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_invoice_id` (`invoice_id`),
  ADD KEY `idx_product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_name` (`name`),
  ADD KEY `idx_brand` (`brand`),
  ADD KEY `idx_price` (`price`);

--
-- Indexes for table `stock_log`
--
ALTER TABLE `stock_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_stock_log_product` (`product_id`),
  ADD KEY `fk_stock_log_user` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `invoice_items`
--
ALTER TABLE `invoice_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `stock_log`
--
ALTER TABLE `stock_log`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `invoices_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `invoice_items`
--
ALTER TABLE `invoice_items`
  ADD CONSTRAINT `invoice_items_ibfk_1` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `invoice_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
