-- phpMyAdmin SQL Dump
-- Database: sewlovely_db
-- --------------------------------------------------------

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+07:00";

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','mitra') NOT NULL DEFAULT 'mitra',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Admin Default Account
-- password is 'admin123' (using password_hash)
INSERT INTO `users` (`id`, `email`, `password_hash`, `role`) VALUES
(1, 'admin@sewlovely.com', '$2y$10$wOqgD5fJz/lMhM6A8A4X5OWg9bY6L1e.gqV8W1.Q3O9P1F2P7W9kS', 'admin');

-- --------------------------------------------------------
-- Table structure for table `partners`
-- --------------------------------------------------------

CREATE TABLE `partners` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `full_name` varchar(150) NOT NULL,
  `whatsapp_number` varchar(20) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `bank_name` varchar(50) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `account_holder` varchar(100) DEFAULT NULL,
  `affiliate_code` varchar(20) NOT NULL,
  `commission_percentage` decimal(5,2) DEFAULT 5.00,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `affiliate_code` (`affiliate_code`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_partner_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `surveys`
-- --------------------------------------------------------

CREATE TABLE `surveys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_address` text NOT NULL,
  `survey_date` date NOT NULL,
  `survey_time` varchar(10) NOT NULL,
  `calculator_type` varchar(50) NOT NULL,
  `status` enum('pending','survey','waiting_payment','confirmed','completed','production','installation','done','cancelled') NOT NULL DEFAULT 'pending',
  `kode_gorden_url` varchar(255) DEFAULT NULL,
  `motif_gorden_url` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `fk_survey_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `invoices`
-- --------------------------------------------------------

CREATE TABLE `invoices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `survey_id` int(11) NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `secure_token` varchar(64) DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `payment_status` enum('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',
  `invoice_notes` text DEFAULT NULL,
  `cart_json` longtext DEFAULT NULL,
  `commission_paid` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  UNIQUE KEY `secure_token` (`secure_token`),
  KEY `survey_id` (`survey_id`),
  CONSTRAINT `fk_invoice_survey` FOREIGN KEY (`survey_id`) REFERENCES `surveys` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Table structure for table `transactions`
-- --------------------------------------------------------

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `partner_id` int(11) NOT NULL,
  `type` enum('commission','withdraw') NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `description` varchar(255) NOT NULL,
  `status` enum('pending','success','rejected') NOT NULL DEFAULT 'success',
  `proof_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `partner_id` (`partner_id`),
  CONSTRAINT `fk_transaction_partner` FOREIGN KEY (`partner_id`) REFERENCES `partners` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

COMMIT;
