-- phpMyAdmin SQL Dump
-- version 5.2.1deb3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 21, 2026 at 07:16 AM
-- Server version: 10.11.13-MariaDB-0ubuntu0.24.04.1
-- PHP Version: 8.3.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `body_repair`
--
CREATE DATABASE IF NOT EXISTS `body_repair` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `body_repair`;

-- --------------------------------------------------------

--
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) NOT NULL COMMENT 'Model class name',
  `model_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Record ID',
  `action` varchar(20) NOT NULL COMMENT 'created, updated, deleted',
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `old_values` text DEFAULT NULL COMMENT 'JSON of old values (for update/delete)',
  `new_values` text DEFAULT NULL COMMENT 'JSON of new values (for create/update)',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bon_outs`
--

DROP TABLE IF EXISTS `bon_outs`;
CREATE TABLE `bon_outs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `work_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `bon_out_number` varchar(255) NOT NULL,
  `bon_out_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `issued_date` date NOT NULL,
  `issued_to` varchar(255) DEFAULT NULL COMMENT 'Person or department receiving items',
  `purpose` text DEFAULT NULL COMMENT 'Reason for stock issuance',
  `notes` text DEFAULT NULL,
  `status` enum('on_progress','completed','cancelled') NOT NULL DEFAULT 'on_progress',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `completed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `total_cogs` decimal(15,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `bon_out_items`
--

DROP TABLE IF EXISTS `bon_out_items`;
CREATE TABLE `bon_out_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `bon_out_id` bigint(20) UNSIGNED NOT NULL,
  `work_order_item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `demand_quantity` decimal(15,2) NOT NULL,
  `actual_quantity` decimal(15,2) DEFAULT NULL COMMENT 'Actual quantity consumed (filled during Bon Out completion)',
  `unit_cost` decimal(14,2) NOT NULL DEFAULT 0.00 COMMENT 'Avg cost per unit at time of Bon Out completion (for COGS)',
  `unit_price` decimal(15,2) DEFAULT NULL,
  `remark` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `credit_notes`
--

DROP TABLE IF EXISTS `credit_notes`;
CREATE TABLE `credit_notes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `credit_note_number` varchar(255) NOT NULL,
  `invoice_id` bigint(20) UNSIGNED NOT NULL,
  `work_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `customer_id` bigint(20) UNSIGNED DEFAULT NULL,
  `qq` varchar(255) DEFAULT NULL,
  `credit_note_date` date NOT NULL,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(8,4) NOT NULL DEFAULT 0.0000,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `cancellation_reason` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `npwp` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `invoices`
--

DROP TABLE IF EXISTS `invoices`;
CREATE TABLE `invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `work_order_id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `qq` varchar(255) DEFAULT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `tax_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL,
  `cogm_material` decimal(15,2) DEFAULT NULL COMMENT 'Cost of materials (actual qty × avg cost)',
  `cogm_labor` decimal(15,2) DEFAULT NULL COMMENT 'Fixed labor cost included in COGM',
  `cogm` decimal(15,2) DEFAULT NULL COMMENT 'Total Cost of Goods Manufactured',
  `status` enum('on_progress','sent','paid','partial','overdue','cancelled') DEFAULT 'on_progress',
  `notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `items`
--

DROP TABLE IF EXISTS `items`;
CREATE TABLE `items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_type` enum('A','B','C','E','T','TE') NOT NULL COMMENT 'A=Coating, B=Chemical, C=Consumable, E=Equipment, T=Tools, TE=Tools&Equipment',
  `code` varchar(50) NOT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL COMMENT 'e.g., Paint, Parts, Chemicals',
  `smallest_uom_id` bigint(20) UNSIGNED NOT NULL,
  `reorder_level` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Alert when stock below this level',
  `selling_price` decimal(15,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_complete` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'False for placeholder items from PPB that need completion before Bon In',
  `is_manual_entry` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'True when item is created manually from Item Create form',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `item_uoms`
--

DROP TABLE IF EXISTS `item_uoms`;
CREATE TABLE `item_uoms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `conversion_to_smallest` decimal(15,6) NOT NULL COMMENT 'How many smallest UOM in this UOM. E.g., 1 Box = 100 Pieces',
  `price` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Selling price for this UOM',
  `is_default` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Default UOM for purchase/display',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `labors`
--

DROP TABLE IF EXISTS `labors`;
CREATE TABLE `labors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `labor_code` varchar(20) NOT NULL,
  `description` varchar(255) NOT NULL,
  `price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `multiplier` decimal(6,2) DEFAULT NULL,
  `price_0_300` decimal(15,2) DEFAULT NULL,
  `price_300_500` decimal(15,2) DEFAULT NULL,
  `price_500_800` decimal(15,2) DEFAULT NULL,
  `price_800_2000` decimal(15,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int(10) UNSIGNED NOT NULL,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

DROP TABLE IF EXISTS `packages`;
CREATE TABLE `packages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `category` varchar(100) NOT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_bom_items`
--

DROP TABLE IF EXISTS `package_bom_items`;
CREATE TABLE `package_bom_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(15,2) NOT NULL COMMENT 'Base quantity estimate per job',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `package_sizes`
--

DROP TABLE IF EXISTS `package_sizes`;
CREATE TABLE `package_sizes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `package_id` bigint(20) UNSIGNED NOT NULL,
  `size_name` varchar(50) NOT NULL,
  `price` decimal(15,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proforma_discount_lines`
--

DROP TABLE IF EXISTS `proforma_discount_lines`;
CREATE TABLE `proforma_discount_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `proforma_invoice_id` bigint(20) UNSIGNED NOT NULL,
  `target_type` enum('package','extra_item','extra_labor') NOT NULL,
  `target_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(300) NOT NULL,
  `original_price` decimal(15,2) NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `final_price` decimal(15,2) NOT NULL,
  `status` enum('pending_approval','approved','rejected') NOT NULL DEFAULT 'pending_approval',
  `approvals_required` tinyint(4) NOT NULL,
  `approver1_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approver1_approved_at` timestamp NULL DEFAULT NULL,
  `approver1_rejected_at` timestamp NULL DEFAULT NULL,
  `approver2_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approver2_approved_at` timestamp NULL DEFAULT NULL,
  `approver2_rejected_at` timestamp NULL DEFAULT NULL,
  `approver3_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approver3_approved_at` timestamp NULL DEFAULT NULL,
  `approver3_rejected_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `proforma_invoices`
--

DROP TABLE IF EXISTS `proforma_invoices`;
CREATE TABLE `proforma_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `proforma_number` varchar(50) NOT NULL,
  `work_order_id` bigint(20) UNSIGNED NOT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `subtotal` decimal(15,2) NOT NULL,
  `discount_type` enum('percentage','amount') DEFAULT NULL,
  `discount_value` decimal(15,2) NOT NULL DEFAULT 0.00,
  `discount_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `discount_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `total` decimal(15,2) NOT NULL,
  `status` enum('pending_approval','approved','rejected','no_discount') NOT NULL DEFAULT 'pending_approval',
  `approvals_required` tinyint(4) NOT NULL DEFAULT 0,
  `approver1_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approver1_approved_at` timestamp NULL DEFAULT NULL,
  `approver1_rejected_at` timestamp NULL DEFAULT NULL,
  `approver2_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approver2_approved_at` timestamp NULL DEFAULT NULL,
  `approver2_rejected_at` timestamp NULL DEFAULT NULL,
  `approver3_id` bigint(20) UNSIGNED DEFAULT NULL,
  `approver3_approved_at` timestamp NULL DEFAULT NULL,
  `approver3_rejected_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `voucher_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `voucher_code` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_orders`
--

DROP TABLE IF EXISTS `purchase_orders`;
CREATE TABLE `purchase_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `po_number` varchar(50) NOT NULL,
  `ppb_number` varchar(255) DEFAULT NULL,
  `po_type` enum('purchase_order','service_order') NOT NULL DEFAULT 'purchase_order' COMMENT 'Type: Purchase Order (PPB) or Service Order (PPJ)',
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `purchase_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `order_date` date NOT NULL,
  `expected_delivery_date` date DEFAULT NULL,
  `supplier_name` varchar(200) NOT NULL,
  `supplier_address` text DEFAULT NULL,
  `supplier_phone` varchar(50) DEFAULT NULL,
  `supplier_contact_person` varchar(255) DEFAULT NULL COMMENT 'UP (Contact Person)',
  `lokasi_pengerjaan` varchar(255) DEFAULT NULL COMMENT 'Work location (for PPJ)',
  `lokasi_pengiriman` varchar(255) DEFAULT NULL COMMENT 'Delivery location (for PPB)',
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `misc_cost` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Lain-lain (shipping, duty stamp, etc)',
  `misc_cost_description` varchar(255) DEFAULT NULL COMMENT 'Description for misc cost',
  `status` enum('on_progress','approved','sent','confirmed','partial','received','completed','cancelled','closed_shortage','printed') NOT NULL DEFAULT 'on_progress',
  `notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `include_ppn` tinyint(1) NOT NULL DEFAULT 1 COMMENT 'Include PPN 11%',
  `pph_type` enum('none','pph_21','pph_23') NOT NULL DEFAULT 'none' COMMENT 'PPH Type',
  `waktu_pengerjaan` varchar(255) DEFAULT NULL COMMENT 'Work duration (e.g., 30 Hari)',
  `pembayaran` enum('cash','credit','tunai','non_tunai','cicilan') DEFAULT NULL COMMENT 'Tunai or Non-Tunai',
  `bank_account` varchar(255) DEFAULT NULL COMMENT 'Bank account info',
  `jatuh_tempo` varchar(255) DEFAULT NULL COMMENT 'Due date / Jatuh Tempo',
  `payment_method` enum('credit','cbd','dp') DEFAULT NULL COMMENT 'Payment method type: credit, cbd (Cash Before Delivery), dp (Down Payment)',
  `payment_terms` text DEFAULT NULL,
  `invoice_number` varchar(255) DEFAULT NULL,
  `invoice_date` date DEFAULT NULL,
  `invoice_due_date` date DEFAULT NULL,
  `invoice_notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `printed_at` timestamp NULL DEFAULT NULL,
  `printed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_recorded_by` bigint(20) UNSIGNED DEFAULT NULL,
  `invoice_recorded_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_details`
--

DROP TABLE IF EXISTS `purchase_order_details`;
CREATE TABLE `purchase_order_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_request_detail_id` bigint(20) UNSIGNED DEFAULT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `service_description` text DEFAULT NULL,
  `uom_id` bigint(20) UNSIGNED DEFAULT NULL,
  `conversion_to_smallest` decimal(15,6) DEFAULT NULL COMMENT 'How many smallest UOM in this UOM for this specific PO line. Overrides item master if set.',
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `received_quantity` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Quantity actually received',
  `closed_shortage_quantity` decimal(15,2) NOT NULL DEFAULT 0.00,
  `shortage_close_reason` text DEFAULT NULL,
  `shortage_closed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `shortage_closed_at` timestamp NULL DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL COMMENT 'Remarks column',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_invoices`
--

DROP TABLE IF EXISTS `purchase_order_invoices`;
CREATE TABLE `purchase_order_invoices` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `invoice_number` varchar(100) NOT NULL,
  `invoice_date` date NOT NULL,
  `due_date` date DEFAULT NULL,
  `total_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('on_progress','paid','cancelled') NOT NULL DEFAULT 'on_progress',
  `notes` text DEFAULT NULL,
  `recorded_by` bigint(20) UNSIGNED NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_invoice_lines`
--

DROP TABLE IF EXISTS `purchase_order_invoice_lines`;
CREATE TABLE `purchase_order_invoice_lines` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_invoice_id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_detail_id` bigint(20) UNSIGNED NOT NULL,
  `qty_billed` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `line_total` decimal(15,2) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_order_misc_costs`
--

DROP TABLE IF EXISTS `purchase_order_misc_costs`;
CREATE TABLE `purchase_order_misc_costs` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_order_id` bigint(20) UNSIGNED NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_requests`
--

DROP TABLE IF EXISTS `purchase_requests`;
CREATE TABLE `purchase_requests` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `pr_number` varchar(50) NOT NULL,
  `request_date` date NOT NULL,
  `requested_by` bigint(20) UNSIGNED NOT NULL,
  `notes` text DEFAULT NULL,
  `cancellation_reason` text DEFAULT NULL,
  `status` enum('on_progress','dept_head_approved','gm_approved','rejected','cancelled','completed','printed','closed') NOT NULL DEFAULT 'on_progress',
  `type` enum('Jasa','Barang') NOT NULL DEFAULT 'Barang',
  `require_acknowledgement` tinyint(1) NOT NULL DEFAULT 1,
  `dept_head_by` bigint(20) UNSIGNED DEFAULT NULL,
  `dept_head_at` timestamp NULL DEFAULT NULL,
  `gm_by` bigint(20) UNSIGNED DEFAULT NULL,
  `gm_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `acknowledged_by` bigint(20) UNSIGNED DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `purchasing_received_by` bigint(20) UNSIGNED DEFAULT NULL,
  `purchasing_received_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_request_details`
--

DROP TABLE IF EXISTS `purchase_request_details`;
CREATE TABLE `purchase_request_details` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `purchase_request_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED DEFAULT NULL,
  `service_description` text DEFAULT NULL,
  `is_custom_item` tinyint(1) NOT NULL DEFAULT 0,
  `custom_item_name` varchar(255) DEFAULT NULL,
  `uom_id` bigint(20) UNSIGNED DEFAULT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `ordered_quantity` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Quantity ordered across all POs',
  `unit_price` decimal(15,2) DEFAULT NULL,
  `total_price` decimal(15,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receivables`
--

DROP TABLE IF EXISTS `receivables`;
CREATE TABLE `receivables` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `receive_number` varchar(255) NOT NULL,
  `bon_in_type` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `purchase_order_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_id` bigint(20) UNSIGNED DEFAULT NULL,
  `supplier_name` varchar(255) DEFAULT NULL,
  `received_date` date NOT NULL,
  `status` enum('on_progress','partial_received','completed','cancelled','printed') NOT NULL DEFAULT 'on_progress',
  `printed_at` timestamp NULL DEFAULT NULL,
  `printed_by` bigint(20) UNSIGNED DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `receivable_items`
--

DROP TABLE IF EXISTS `receivable_items`;
CREATE TABLE `receivable_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `receivable_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED NOT NULL,
  `quantity_ordered` decimal(10,2) NOT NULL,
  `quantity_received` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_orders`
--

DROP TABLE IF EXISTS `sales_orders`;
CREATE TABLE `sales_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `so_number` varchar(50) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `order_date` date NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `material_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status` enum('draft','confirmed','cancelled') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales_order_items`
--

DROP TABLE IF EXISTS `sales_order_items`;
CREATE TABLE `sales_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sales_order_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(15,2) NOT NULL,
  `unit_price` decimal(15,2) NOT NULL,
  `total_price` decimal(15,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) UNSIGNED DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stocks`
--

DROP TABLE IF EXISTS `stocks`;
CREATE TABLE `stocks` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `quantity` decimal(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Always stored in smallest UOM',
  `avg_cost` decimal(15,2) NOT NULL DEFAULT 1.00 COMMENT 'Average cost per smallest UOM',
  `location` varchar(100) DEFAULT NULL COMMENT 'Storage location/warehouse',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_cost_adjustments`
--

DROP TABLE IF EXISTS `stock_cost_adjustments`;
CREATE TABLE `stock_cost_adjustments` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `stock_id` bigint(20) UNSIGNED NOT NULL,
  `old_avg_cost` decimal(15,2) NOT NULL DEFAULT 0.00,
  `new_avg_cost` decimal(15,2) NOT NULL,
  `reason` text NOT NULL,
  `adjusted_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stock_transactions`
--

DROP TABLE IF EXISTS `stock_transactions`;
CREATE TABLE `stock_transactions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `transaction_type` enum('in','out','adjustment') NOT NULL COMMENT 'in=receive, out=release, adjustment=manual',
  `quantity` decimal(15,2) NOT NULL COMMENT 'Always in smallest UOM. Positive for IN, negative for OUT',
  `balance_after` decimal(15,2) NOT NULL COMMENT 'Stock balance after this transaction',
  `location` varchar(100) DEFAULT NULL,
  `reference_type` varchar(50) DEFAULT NULL COMMENT 'E.g., PO, WorkOrder, Adjustment',
  `reference_id` bigint(20) UNSIGNED DEFAULT NULL COMMENT 'ID of the reference document',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
CREATE TABLE `suppliers` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `supplier_code` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `contact_person` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `postal_code` varchar(10) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `bank_name` varchar(100) DEFAULT NULL,
  `bank_account_no` varchar(50) DEFAULT NULL,
  `bank_account_name` varchar(100) DEFAULT NULL,
  `npwp` varchar(30) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `uoms`
--

DROP TABLE IF EXISTS `uoms`;
CREATE TABLE `uoms` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(20) NOT NULL COMMENT 'UOM code like PCS, BOX, KG',
  `name` varchar(100) NOT NULL COMMENT 'UOM name like Piece, Box, Kilogram',
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `uom_conversions`
--

DROP TABLE IF EXISTS `uom_conversions`;
CREATE TABLE `uom_conversions` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `from_uom_id` bigint(20) UNSIGNED NOT NULL,
  `to_uom_id` bigint(20) UNSIGNED NOT NULL,
  `conversion_factor` decimal(15,6) NOT NULL COMMENT 'How many to_uom in one from_uom. Example: 1 Box = 10 Pieces, factor = 10',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `username` varchar(255) DEFAULT NULL,
  `role` varchar(255) NOT NULL DEFAULT 'staff',
  `signature_path` varchar(255) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user_notifications`
--

DROP TABLE IF EXISTS `user_notifications`;
CREATE TABLE `user_notifications` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vehicles`
--

DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `plate_number` varchar(20) NOT NULL,
  `brand` varchar(100) DEFAULT NULL,
  `model` varchar(100) DEFAULT NULL,
  `year` varchar(10) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `chasis_no` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_comparisons`
--

DROP TABLE IF EXISTS `vendor_comparisons`;
CREATE TABLE `vendor_comparisons` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `comparison_number` varchar(255) NOT NULL,
  `purchase_request_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nomor_permintaan` varchar(255) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `detail_barang_jasa` text NOT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('draft','submitted','approved') NOT NULL DEFAULT 'draft',
  `selected_vendor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `approved_by` bigint(20) UNSIGNED DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `vendor_comparison_vendors`
--

DROP TABLE IF EXISTS `vendor_comparison_vendors`;
CREATE TABLE `vendor_comparison_vendors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `vendor_comparison_id` bigint(20) UNSIGNED NOT NULL,
  `vendor_order` tinyint(3) UNSIGNED NOT NULL,
  `nama_calon_vendor` varchar(255) NOT NULL,
  `alamat` text DEFAULT NULL,
  `telepon_fax` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `pic_contact_person` varchar(255) DEFAULT NULL,
  `metode_pembayaran` varchar(255) DEFAULT NULL,
  `rekening_bank` varchar(255) DEFAULT NULL,
  `term_of_payment` varchar(255) DEFAULT NULL,
  `harga_barang_jasa` decimal(18,2) DEFAULT NULL,
  `ketentuan_lain` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_orders`
--

DROP TABLE IF EXISTS `work_orders`;
CREATE TABLE `work_orders` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `wo_number` varchar(50) NOT NULL,
  `customer_id` bigint(20) UNSIGNED NOT NULL,
  `vehicle_id` bigint(20) UNSIGNED DEFAULT NULL,
  `package_id` bigint(20) UNSIGNED DEFAULT NULL,
  `package_size_id` bigint(20) UNSIGNED DEFAULT NULL,
  `account_code` enum('C','INT_WS','INT_W3') NOT NULL DEFAULT 'C',
  `reference_wo_id` bigint(20) UNSIGNED DEFAULT NULL,
  `work_date` date NOT NULL,
  `deadline` date DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `vehicle_info` varchar(200) DEFAULT NULL COMMENT 'Vehicle make, model, plate number',
  `vehicle_merk` varchar(100) DEFAULT NULL,
  `vehicle_type_year` varchar(100) DEFAULT NULL,
  `chasis_no` varchar(100) DEFAULT NULL,
  `vehicle_plate` varchar(50) DEFAULT NULL,
  `vehicle_km` int(11) DEFAULT NULL,
  `paket_code` varchar(200) DEFAULT NULL,
  `paket_name` varchar(500) DEFAULT NULL,
  `paket_size` varchar(100) DEFAULT NULL,
  `paket_grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL COMMENT 'What repair work is needed',
  `status` enum('on_progress','in_progress','completed','invoiced','cancelled') DEFAULT 'on_progress',
  `labor_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `material_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `grand_total` decimal(15,2) NOT NULL DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `sa_sales` varchar(100) DEFAULT NULL,
  `created_by` bigint(20) UNSIGNED NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_order_items`
--

DROP TABLE IF EXISTS `work_order_items`;
CREATE TABLE `work_order_items` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `work_order_id` bigint(20) UNSIGNED NOT NULL,
  `item_id` bigint(20) UNSIGNED NOT NULL,
  `uom_id` bigint(20) UNSIGNED DEFAULT NULL,
  `demand_quantity` decimal(15,2) NOT NULL COMMENT 'Quantity used in smallest UOM',
  `actual_quantity` decimal(15,2) DEFAULT NULL COMMENT 'Actual quantity used (recorded on Bon Out)',
  `remark` varchar(255) DEFAULT NULL,
  `unit_price` decimal(15,2) DEFAULT NULL,
  `total_price` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `work_order_labors`
--

DROP TABLE IF EXISTS `work_order_labors`;
CREATE TABLE `work_order_labors` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `work_order_id` bigint(20) UNSIGNED NOT NULL,
  `labor_id` bigint(20) UNSIGNED DEFAULT NULL,
  `description` varchar(200) NOT NULL COMMENT 'What work was done, e.g., Panel painting, Dent removal',
  `qty` decimal(8,2) NOT NULL DEFAULT 1.00,
  `remarks` text DEFAULT NULL,
  `hours` decimal(8,2) DEFAULT NULL,
  `rate` decimal(15,2) DEFAULT NULL,
  `total_price` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  ADD KEY `audit_logs_user_id_index` (`user_id`),
  ADD KEY `audit_logs_action_index` (`action`),
  ADD KEY `audit_logs_created_at_index` (`created_at`);

--
-- Indexes for table `bon_outs`
--
ALTER TABLE `bon_outs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bon_outs_bon_out_number_unique` (`bon_out_number`),
  ADD KEY `bon_outs_created_by_foreign` (`created_by`),
  ADD KEY `bon_outs_completed_by_foreign` (`completed_by`),
  ADD KEY `bon_outs_work_order_id_foreign` (`work_order_id`);

--
-- Indexes for table `bon_out_items`
--
ALTER TABLE `bon_out_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bon_out_items_work_order_item_id_foreign` (`work_order_item_id`);

--
-- Indexes for table `credit_notes`
--
ALTER TABLE `credit_notes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `credit_notes_credit_note_number_unique` (`credit_note_number`),
  ADD KEY `credit_notes_invoice_id_foreign` (`invoice_id`),
  ADD KEY `credit_notes_work_order_id_foreign` (`work_order_id`),
  ADD KEY `credit_notes_customer_id_foreign` (`customer_id`),
  ADD KEY `credit_notes_created_by_foreign` (`created_by`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `customers_code_unique` (`code`);

--
-- Indexes for table `invoices`
--
ALTER TABLE `invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  ADD KEY `invoices_work_order_id_foreign` (`work_order_id`),
  ADD KEY `invoices_customer_id_foreign` (`customer_id`),
  ADD KEY `invoices_created_by_foreign` (`created_by`);

--
-- Indexes for table `items`
--
ALTER TABLE `items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `items_smallest_uom_id_foreign` (`smallest_uom_id`);

--
-- Indexes for table `item_uoms`
--
ALTER TABLE `item_uoms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_uoms_item_id_uom_id_unique` (`item_id`,`uom_id`),
  ADD KEY `item_uoms_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `labors`
--
ALTER TABLE `labors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `labors_labor_code_unique` (`labor_code`);

--
-- Indexes for table `migrations`
--
ALTER TABLE `migrations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `packages_code_unique` (`code`),
  ADD KEY `packages_category_index` (`category`),
  ADD KEY `packages_is_active_index` (`is_active`);

--
-- Indexes for table `package_bom_items`
--
ALTER TABLE `package_bom_items`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `pkg_bom_unique` (`package_id`,`item_id`),
  ADD KEY `package_bom_items_item_id_foreign` (`item_id`),
  ADD KEY `package_bom_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `package_sizes`
--
ALTER TABLE `package_sizes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `package_sizes_package_id_index` (`package_id`),
  ADD KEY `package_sizes_is_active_index` (`is_active`);

--
-- Indexes for table `proforma_discount_lines`
--
ALTER TABLE `proforma_discount_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `proforma_discount_lines_proforma_invoice_id_foreign` (`proforma_invoice_id`),
  ADD KEY `proforma_discount_lines_approver1_id_foreign` (`approver1_id`),
  ADD KEY `proforma_discount_lines_approver2_id_foreign` (`approver2_id`),
  ADD KEY `proforma_discount_lines_approver3_id_foreign` (`approver3_id`);

--
-- Indexes for table `proforma_invoices`
--
ALTER TABLE `proforma_invoices`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `proforma_invoices_proforma_number_unique` (`proforma_number`),
  ADD KEY `proforma_invoices_work_order_id_foreign` (`work_order_id`),
  ADD KEY `proforma_invoices_created_by_foreign` (`created_by`),
  ADD KEY `proforma_invoices_approver1_id_foreign` (`approver1_id`),
  ADD KEY `proforma_invoices_approver2_id_foreign` (`approver2_id`),
  ADD KEY `proforma_invoices_approver3_id_foreign` (`approver3_id`);

--
-- Indexes for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_orders_po_number_unique` (`po_number`),
  ADD KEY `purchase_orders_purchase_request_id_foreign` (`purchase_request_id`),
  ADD KEY `purchase_orders_created_by_foreign` (`created_by`),
  ADD KEY `purchase_orders_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_orders_approved_by_foreign` (`approved_by`),
  ADD KEY `purchase_orders_invoice_recorded_by_foreign` (`invoice_recorded_by`);

--
-- Indexes for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_details_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `purchase_order_details_item_id_foreign` (`item_id`),
  ADD KEY `purchase_order_details_uom_id_foreign` (`uom_id`),
  ADD KEY `purchase_order_details_purchase_request_detail_id_foreign` (`purchase_request_detail_id`),
  ADD KEY `purchase_order_details_shortage_closed_by_foreign` (`shortage_closed_by`);

--
-- Indexes for table `purchase_order_invoices`
--
ALTER TABLE `purchase_order_invoices`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_invoices_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `purchase_order_invoices_supplier_id_foreign` (`supplier_id`),
  ADD KEY `purchase_order_invoices_recorded_by_foreign` (`recorded_by`);

--
-- Indexes for table `purchase_order_invoice_lines`
--
ALTER TABLE `purchase_order_invoice_lines`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_invoice_lines_purchase_order_invoice_id_foreign` (`purchase_order_invoice_id`),
  ADD KEY `purchase_order_invoice_lines_purchase_order_detail_id_foreign` (`purchase_order_detail_id`);

--
-- Indexes for table `purchase_order_misc_costs`
--
ALTER TABLE `purchase_order_misc_costs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_order_misc_costs_purchase_order_id_foreign` (`purchase_order_id`);

--
-- Indexes for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `purchase_requests_pr_number_unique` (`pr_number`),
  ADD KEY `purchase_requests_requested_by_foreign` (`requested_by`),
  ADD KEY `purchase_requests_approved_by_foreign` (`approved_by`),
  ADD KEY `purchase_requests_acknowledged_by_foreign` (`acknowledged_by`),
  ADD KEY `purchase_requests_purchasing_received_by_foreign` (`purchasing_received_by`),
  ADD KEY `purchase_requests_dept_head_by_foreign` (`dept_head_by`),
  ADD KEY `purchase_requests_gm_by_foreign` (`gm_by`);

--
-- Indexes for table `purchase_request_details`
--
ALTER TABLE `purchase_request_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `purchase_request_details_purchase_request_id_foreign` (`purchase_request_id`),
  ADD KEY `purchase_request_details_item_id_foreign` (`item_id`),
  ADD KEY `purchase_request_details_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `receivables`
--
ALTER TABLE `receivables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `receivables_receive_number_unique` (`receive_number`),
  ADD KEY `receivables_purchase_order_id_foreign` (`purchase_order_id`),
  ADD KEY `receivables_supplier_id_foreign` (`supplier_id`),
  ADD KEY `receivables_printed_by_foreign` (`printed_by`);

--
-- Indexes for table `receivable_items`
--
ALTER TABLE `receivable_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receivable_items_receivable_id_foreign` (`receivable_id`),
  ADD KEY `receivable_items_item_id_foreign` (`item_id`),
  ADD KEY `receivable_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `so_number` (`so_number`),
  ADD KEY `fk_so_customer` (`customer_id`),
  ADD KEY `fk_so_created_by` (`created_by`);

--
-- Indexes for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sales_order_items_sales_order_id_foreign` (`sales_order_id`),
  ADD KEY `sales_order_items_item_id_foreign` (`item_id`);

--
-- Indexes for table `sessions`
--
ALTER TABLE `sessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sessions_user_id_index` (`user_id`),
  ADD KEY `sessions_last_activity_index` (`last_activity`);

--
-- Indexes for table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `stocks_item_id_location_unique` (`item_id`,`location`);

--
-- Indexes for table `stock_cost_adjustments`
--
ALTER TABLE `stock_cost_adjustments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_cost_adjustments_stock_id_foreign` (`stock_id`),
  ADD KEY `stock_cost_adjustments_adjusted_by_foreign` (`adjusted_by`),
  ADD KEY `stock_cost_adjustments_item_id_index` (`item_id`);

--
-- Indexes for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_transactions_item_id_foreign` (`item_id`),
  ADD KEY `stock_transactions_created_by_foreign` (`created_by`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `uoms`
--
ALTER TABLE `uoms`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uoms_code_unique` (`code`);

--
-- Indexes for table `uom_conversions`
--
ALTER TABLE `uom_conversions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uom_conversions_from_uom_id_to_uom_id_unique` (`from_uom_id`,`to_uom_id`),
  ADD KEY `uom_conversions_to_uom_id_foreign` (`to_uom_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `users_email_unique` (`email`),
  ADD UNIQUE KEY `users_username_unique` (`username`);

--
-- Indexes for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_notifications_user_id_read_at_index` (`user_id`,`read_at`);

--
-- Indexes for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vehicles_plate_number_unique` (`plate_number`),
  ADD KEY `vehicles_customer_id_foreign` (`customer_id`);

--
-- Indexes for table `vendor_comparisons`
--
ALTER TABLE `vendor_comparisons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `vendor_comparisons_comparison_number_unique` (`comparison_number`),
  ADD KEY `vendor_comparisons_purchase_request_id_foreign` (`purchase_request_id`),
  ADD KEY `vendor_comparisons_created_by_foreign` (`created_by`),
  ADD KEY `vendor_comparisons_approved_by_foreign` (`approved_by`),
  ADD KEY `vendor_comparisons_selected_vendor_id_foreign` (`selected_vendor_id`);

--
-- Indexes for table `vendor_comparison_vendors`
--
ALTER TABLE `vendor_comparison_vendors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `vendor_comparison_vendors_vendor_comparison_id_foreign` (`vendor_comparison_id`);

--
-- Indexes for table `work_orders`
--
ALTER TABLE `work_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `work_orders_wo_number_unique` (`wo_number`),
  ADD KEY `work_orders_customer_id_foreign` (`customer_id`),
  ADD KEY `work_orders_created_by_foreign` (`created_by`),
  ADD KEY `work_orders_package_id_foreign` (`package_id`),
  ADD KEY `work_orders_package_size_id_foreign` (`package_size_id`),
  ADD KEY `work_orders_vehicle_id_foreign` (`vehicle_id`),
  ADD KEY `work_orders_reference_wo_id_foreign` (`reference_wo_id`);

--
-- Indexes for table `work_order_items`
--
ALTER TABLE `work_order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `work_order_items_work_order_id_foreign` (`work_order_id`),
  ADD KEY `work_order_items_item_id_foreign` (`item_id`),
  ADD KEY `work_order_items_uom_id_foreign` (`uom_id`);

--
-- Indexes for table `work_order_labors`
--
ALTER TABLE `work_order_labors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `work_order_labors_work_order_id_foreign` (`work_order_id`),
  ADD KEY `work_order_labors_labor_id_foreign` (`labor_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `audit_logs`
--
ALTER TABLE `audit_logs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bon_outs`
--
ALTER TABLE `bon_outs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `bon_out_items`
--
ALTER TABLE `bon_out_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `credit_notes`
--
ALTER TABLE `credit_notes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `invoices`
--
ALTER TABLE `invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `items`
--
ALTER TABLE `items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `item_uoms`
--
ALTER TABLE `item_uoms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `labors`
--
ALTER TABLE `labors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `migrations`
--
ALTER TABLE `migrations`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_bom_items`
--
ALTER TABLE `package_bom_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `package_sizes`
--
ALTER TABLE `package_sizes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proforma_discount_lines`
--
ALTER TABLE `proforma_discount_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `proforma_invoices`
--
ALTER TABLE `proforma_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_invoices`
--
ALTER TABLE `purchase_order_invoices`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_invoice_lines`
--
ALTER TABLE `purchase_order_invoice_lines`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_order_misc_costs`
--
ALTER TABLE `purchase_order_misc_costs`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchase_request_details`
--
ALTER TABLE `purchase_request_details`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receivables`
--
ALTER TABLE `receivables`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `receivable_items`
--
ALTER TABLE `receivable_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_orders`
--
ALTER TABLE `sales_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_cost_adjustments`
--
ALTER TABLE `stock_cost_adjustments`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uoms`
--
ALTER TABLE `uoms`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `uom_conversions`
--
ALTER TABLE `uom_conversions`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user_notifications`
--
ALTER TABLE `user_notifications`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vehicles`
--
ALTER TABLE `vehicles`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_comparisons`
--
ALTER TABLE `vendor_comparisons`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `vendor_comparison_vendors`
--
ALTER TABLE `vendor_comparison_vendors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_orders`
--
ALTER TABLE `work_orders`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_order_items`
--
ALTER TABLE `work_order_items`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `work_order_labors`
--
ALTER TABLE `work_order_labors`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `audit_logs`
--
ALTER TABLE `audit_logs`
  ADD CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bon_outs`
--
ALTER TABLE `bon_outs`
  ADD CONSTRAINT `bon_outs_completed_by_foreign` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bon_outs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `bon_outs_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `bon_out_items`
--
ALTER TABLE `bon_out_items`
  ADD CONSTRAINT `bon_out_items_work_order_item_id_foreign` FOREIGN KEY (`work_order_item_id`) REFERENCES `work_order_items` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `credit_notes`
--
ALTER TABLE `credit_notes`
  ADD CONSTRAINT `credit_notes_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `credit_notes_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `credit_notes_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `credit_notes_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `invoices`
--
ALTER TABLE `invoices`
  ADD CONSTRAINT `invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `invoices_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `invoices_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`);

--
-- Constraints for table `items`
--
ALTER TABLE `items`
  ADD CONSTRAINT `items_smallest_uom_id_foreign` FOREIGN KEY (`smallest_uom_id`) REFERENCES `uoms` (`id`);

--
-- Constraints for table `item_uoms`
--
ALTER TABLE `item_uoms`
  ADD CONSTRAINT `item_uoms_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `item_uoms_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `package_bom_items`
--
ALTER TABLE `package_bom_items`
  ADD CONSTRAINT `package_bom_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `package_bom_items_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `package_bom_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`);

--
-- Constraints for table `package_sizes`
--
ALTER TABLE `package_sizes`
  ADD CONSTRAINT `package_sizes_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proforma_discount_lines`
--
ALTER TABLE `proforma_discount_lines`
  ADD CONSTRAINT `proforma_discount_lines_approver1_id_foreign` FOREIGN KEY (`approver1_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proforma_discount_lines_approver2_id_foreign` FOREIGN KEY (`approver2_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proforma_discount_lines_approver3_id_foreign` FOREIGN KEY (`approver3_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proforma_discount_lines_proforma_invoice_id_foreign` FOREIGN KEY (`proforma_invoice_id`) REFERENCES `proforma_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `proforma_invoices`
--
ALTER TABLE `proforma_invoices`
  ADD CONSTRAINT `proforma_invoices_approver1_id_foreign` FOREIGN KEY (`approver1_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `proforma_invoices_approver2_id_foreign` FOREIGN KEY (`approver2_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `proforma_invoices_approver3_id_foreign` FOREIGN KEY (`approver3_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `proforma_invoices_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `proforma_invoices_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_orders`
--
ALTER TABLE `purchase_orders`
  ADD CONSTRAINT `purchase_orders_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_orders_invoice_recorded_by_foreign` FOREIGN KEY (`invoice_recorded_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_orders_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`),
  ADD CONSTRAINT `purchase_orders_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_order_details`
--
ALTER TABLE `purchase_order_details`
  ADD CONSTRAINT `purchase_order_details_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_details_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_order_details_purchase_request_detail_id_foreign` FOREIGN KEY (`purchase_request_detail_id`) REFERENCES `purchase_request_details` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_order_details_shortage_closed_by_foreign` FOREIGN KEY (`shortage_closed_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_order_details_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `purchase_order_invoices`
--
ALTER TABLE `purchase_order_invoices`
  ADD CONSTRAINT `purchase_order_invoices_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`),
  ADD CONSTRAINT `purchase_order_invoices_recorded_by_foreign` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_order_invoices_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);

--
-- Constraints for table `purchase_order_invoice_lines`
--
ALTER TABLE `purchase_order_invoice_lines`
  ADD CONSTRAINT `purchase_order_invoice_lines_purchase_order_detail_id_foreign` FOREIGN KEY (`purchase_order_detail_id`) REFERENCES `purchase_order_details` (`id`),
  ADD CONSTRAINT `purchase_order_invoice_lines_purchase_order_invoice_id_foreign` FOREIGN KEY (`purchase_order_invoice_id`) REFERENCES `purchase_order_invoices` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_order_misc_costs`
--
ALTER TABLE `purchase_order_misc_costs`
  ADD CONSTRAINT `purchase_order_misc_costs_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `purchase_requests`
--
ALTER TABLE `purchase_requests`
  ADD CONSTRAINT `purchase_requests_acknowledged_by_foreign` FOREIGN KEY (`acknowledged_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_requests_dept_head_by_foreign` FOREIGN KEY (`dept_head_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_requests_gm_by_foreign` FOREIGN KEY (`gm_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchase_requests_purchasing_received_by_foreign` FOREIGN KEY (`purchasing_received_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `purchase_requests_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `purchase_request_details`
--
ALTER TABLE `purchase_request_details`
  ADD CONSTRAINT `purchase_request_details_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_request_details_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchase_request_details_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`);

--
-- Constraints for table `receivables`
--
ALTER TABLE `receivables`
  ADD CONSTRAINT `receivables_printed_by_foreign` FOREIGN KEY (`printed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `receivables_purchase_order_id_foreign` FOREIGN KEY (`purchase_order_id`) REFERENCES `purchase_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `receivables_supplier_id_foreign` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `receivable_items`
--
ALTER TABLE `receivable_items`
  ADD CONSTRAINT `receivable_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receivable_items_receivable_id_foreign` FOREIGN KEY (`receivable_id`) REFERENCES `receivables` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `receivable_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales_orders`
--
ALTER TABLE `sales_orders`
  ADD CONSTRAINT `fk_so_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `fk_so_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`);

--
-- Constraints for table `sales_order_items`
--
ALTER TABLE `sales_order_items`
  ADD CONSTRAINT `sales_order_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`),
  ADD CONSTRAINT `sales_order_items_sales_order_id_foreign` FOREIGN KEY (`sales_order_id`) REFERENCES `sales_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stocks`
--
ALTER TABLE `stocks`
  ADD CONSTRAINT `stocks_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_cost_adjustments`
--
ALTER TABLE `stock_cost_adjustments`
  ADD CONSTRAINT `stock_cost_adjustments_adjusted_by_foreign` FOREIGN KEY (`adjusted_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `stock_cost_adjustments_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_cost_adjustments_stock_id_foreign` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_transactions`
--
ALTER TABLE `stock_transactions`
  ADD CONSTRAINT `stock_transactions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `stock_transactions_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `uom_conversions`
--
ALTER TABLE `uom_conversions`
  ADD CONSTRAINT `uom_conversions_from_uom_id_foreign` FOREIGN KEY (`from_uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `uom_conversions_to_uom_id_foreign` FOREIGN KEY (`to_uom_id`) REFERENCES `uoms` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_notifications`
--
ALTER TABLE `user_notifications`
  ADD CONSTRAINT `user_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vehicles`
--
ALTER TABLE `vehicles`
  ADD CONSTRAINT `vehicles_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `vendor_comparisons`
--
ALTER TABLE `vendor_comparisons`
  ADD CONSTRAINT `vendor_comparisons_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vendor_comparisons_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `vendor_comparisons_purchase_request_id_foreign` FOREIGN KEY (`purchase_request_id`) REFERENCES `purchase_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `vendor_comparisons_selected_vendor_id_foreign` FOREIGN KEY (`selected_vendor_id`) REFERENCES `vendor_comparison_vendors` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `vendor_comparison_vendors`
--
ALTER TABLE `vendor_comparison_vendors`
  ADD CONSTRAINT `vendor_comparison_vendors_vendor_comparison_id_foreign` FOREIGN KEY (`vendor_comparison_id`) REFERENCES `vendor_comparisons` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_orders`
--
ALTER TABLE `work_orders`
  ADD CONSTRAINT `work_orders_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `work_orders_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `work_orders_package_id_foreign` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `work_orders_package_size_id_foreign` FOREIGN KEY (`package_size_id`) REFERENCES `package_sizes` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `work_orders_reference_wo_id_foreign` FOREIGN KEY (`reference_wo_id`) REFERENCES `work_orders` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `work_orders_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `work_order_items`
--
ALTER TABLE `work_order_items`
  ADD CONSTRAINT `work_order_items_item_id_foreign` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `work_order_items_uom_id_foreign` FOREIGN KEY (`uom_id`) REFERENCES `uoms` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `work_order_items_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `work_order_labors`
--
ALTER TABLE `work_order_labors`
  ADD CONSTRAINT `work_order_labors_labor_id_foreign` FOREIGN KEY (`labor_id`) REFERENCES `labors` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `work_order_labors_work_order_id_foreign` FOREIGN KEY (`work_order_id`) REFERENCES `work_orders` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
