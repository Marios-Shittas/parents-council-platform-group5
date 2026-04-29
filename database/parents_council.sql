-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 27, 2026 at 10:10 PM
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
-- Database: `parents_council`
--
-- ========================================================
-- Schema dump
-- To arxeio auto periexei ti domi tis vasis.
-- Edo tha vreis tables, indexes, auto increment kai foreign keys.
-- Den exei seed data, mono ton orismo tis vasis.
-- ========================================================
--

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `AnnouncementAttachments`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `AnnouncementAttachments` (
  `attachment_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Announcements`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Announcements` (
  `announcement_id` int(11) NOT NULL,
  `announcement_title` varchar(255) NOT NULL,
  `announcement_date` date DEFAULT NULL,
  `publish_date` date NOT NULL,
  `announcement_description` text DEFAULT NULL,
  `gdpr_notice` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `AnnouncementsImages`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `AnnouncementsImages` (
  `an_image_id` int(11) NOT NULL,
  `announcement_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `ApplicationAttachments`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `ApplicationAttachments` (
  `attachment_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `upload_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Applications`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Applications` (
  `application_id` int(11) NOT NULL,
  `template_id` int(11) DEFAULT NULL,
  `application_title` varchar(255) NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `application_description` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `submission_type` enum('file','text') NOT NULL DEFAULT 'file',
  `academic_year` varchar(20) DEFAULT NULL,
  `open_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('draft','published','closed') NOT NULL DEFAULT 'draft',
  `allow_online_submission` tinyint(1) NOT NULL DEFAULT 1,
  `allow_file_submission` tinyint(1) NOT NULL DEFAULT 1,
  `require_signature` tinyint(1) NOT NULL DEFAULT 0,
  `form_schema` longtext DEFAULT NULL,
  `target_audience` longtext DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `ApplicationsDocuments`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `ApplicationsDocuments` (
  `ap_document_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `ApplicationsFormFields`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `ApplicationsFormFields` (
  `field_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_type` varchar(50) NOT NULL DEFAULT 'text',
  `field_order` int(11) NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `ApplicationSubmissions`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `ApplicationSubmissions` (
  `submission_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submission_type` varchar(50) NOT NULL DEFAULT 'manual',
  `status` varchar(50) NOT NULL DEFAULT 'submitted',
  `form_data` longtext DEFAULT NULL,
  `uploaded_files` longtext DEFAULT NULL,
  `signature_data` longtext DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `ApplicationTemplates`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `ApplicationTemplates` (
  `template_id` int(11) NOT NULL,
  `template_key` varchar(150) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'standard',
  `form_schema` longtext DEFAULT NULL,
  `is_system_template` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Children`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Children` (
  `child_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `school_class` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `contact_messages`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `EpikoinoniaPageSections`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `EpikoinoniaPageSections` (
  `section_id` int(11) NOT NULL,
  `section_key` varchar(100) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_subtitle` text DEFAULT NULL,
  `content_json` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `EshopSettings`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `EshopSettings` (
  `setting_id` tinyint(3) UNSIGNED NOT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Events`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Events` (
  `event_id` int(11) NOT NULL,
  `event_title` varchar(255) NOT NULL,
  `event_description` text DEFAULT NULL,
  `gdpr_notice` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `publish_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `EventsImages`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `EventsImages` (
  `ev_image_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `HomeBannerSlides`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `HomeBannerSlides` (
  `slide_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `HomePageSections`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `HomePageSections` (
  `section_id` int(11) NOT NULL,
  `section_key` varchar(100) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_subtitle` text DEFAULT NULL,
  `content_json` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `InsurancePayments`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `InsurancePayments` (
  `payment_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Logs`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Logs` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `OrderItems`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `OrderItems` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `size` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Orders`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Orders` (
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `order_status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  `admin_seen_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `ParentsPageGalleryImages`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `ParentsPageGalleryImages` (
  `image_id` int(11) NOT NULL,
  `full_image_path` varchar(255) NOT NULL,
  `thumb_image_path` varchar(255) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `ParentsPageSections`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `ParentsPageSections` (
  `section_id` int(11) NOT NULL,
  `section_key` varchar(100) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_subtitle` text DEFAULT NULL,
  `content_json` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Payments`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Payments` (
  `payment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_type` enum('membership','insurance','product') NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `PaymentsDetails`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `PaymentsDetails` (
  `payment_item_id` int(11) NOT NULL,
  `payment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_at_purchase` decimal(10,2) NOT NULL,
  `size` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Posts`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Posts` (
  `post_id` int(11) NOT NULL,
  `event_id` int(11) NOT NULL,
  `post_title` varchar(255) NOT NULL,
  `post_content` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `PostsImages`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `PostsImages` (
  `post_image_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `PricingSettings`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `PricingSettings` (
  `id` int(11) NOT NULL,
  `subscription_price` decimal(10,2) NOT NULL,
  `insurance_price` decimal(10,2) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Products`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Products` (
  `product_id` int(11) NOT NULL,
  `product_name` varchar(255) NOT NULL,
  `product_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `ProductsImages`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `ProductsImages` (
  `pro_image_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `ProductSizeOptions`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `ProductSizeOptions` (
  `size_option_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `size_value` varchar(100) NOT NULL,
  `size_label` varchar(100) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Submissions`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Submissions` (
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `text_content` text DEFAULT NULL,
  `submission_data` longtext DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `admin_seen_at` datetime DEFAULT NULL,
  `sub_status` enum('waiting','approved','rejected') DEFAULT 'waiting'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `SystemSchedule`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `SystemSchedule` (
  `ss_id` int(11) NOT NULL,
  `feature` enum('registration','delete_users','cleanup_submissions') NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `ss_status` enum('active','inactive') DEFAULT 'inactive'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `UsefulInformationSections`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `UsefulInformationSections` (
  `section_id` int(11) NOT NULL,
  `section_key` varchar(100) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_subtitle` text DEFAULT NULL,
  `content_json` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- ========================================================
-- Domi pinaka: `Users`
-- Edw orizontai ta columns kai ta default values tou pinaka
-- ========================================================

CREATE TABLE `Users` (
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `number_of_children` int(11) DEFAULT 0,
  `role` enum('parent','admin') NOT NULL DEFAULT 'parent',
  `account_status` enum('pending','approved','rejected','waiting_payment','active') NOT NULL DEFAULT 'pending',
  `token` varchar(255) DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- ========================================================
-- Indexes
-- Edo dilonoume primary keys, unique keys kai voithitika indexes
-- ========================================================

--
-- Indexes gia ton pinaka: `AnnouncementAttachments`
--
ALTER TABLE `AnnouncementAttachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `fk_announcement_attachment` (`announcement_id`);

--
-- Indexes gia ton pinaka: `Announcements`
--
ALTER TABLE `Announcements`
  ADD PRIMARY KEY (`announcement_id`);

--
-- Indexes gia ton pinaka: `AnnouncementsImages`
--
ALTER TABLE `AnnouncementsImages`
  ADD PRIMARY KEY (`an_image_id`),
  ADD KEY `fk_an_img` (`announcement_id`);

--
-- Indexes gia ton pinaka: `ApplicationAttachments`
--
ALTER TABLE `ApplicationAttachments`
  ADD PRIMARY KEY (`attachment_id`),
  ADD KEY `idx_app_attachment_order` (`application_id`,`upload_order`);

--
-- Indexes gia ton pinaka: `Applications`
--
ALTER TABLE `Applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `idx_applications_template_id` (`template_id`),
  ADD KEY `idx_applications_status_dates` (`status`,`open_date`,`due_date`),
  ADD KEY `idx_applications_created_by` (`created_by`);

--
-- Indexes gia ton pinaka: `ApplicationsDocuments`
--
ALTER TABLE `ApplicationsDocuments`
  ADD PRIMARY KEY (`ap_document_id`),
  ADD KEY `fk_app_doc` (`application_id`);

--
-- Indexes gia ton pinaka: `ApplicationsFormFields`
--
ALTER TABLE `ApplicationsFormFields`
  ADD PRIMARY KEY (`field_id`),
  ADD KEY `idx_application_order` (`application_id`,`field_order`);

--
-- Indexes gia ton pinaka: `ApplicationSubmissions`
--
ALTER TABLE `ApplicationSubmissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `idx_app_submissions_application` (`application_id`,`submitted_at`),
  ADD KEY `idx_app_submissions_user` (`user_id`),
  ADD KEY `idx_app_submissions_reviewed_by` (`reviewed_by`);

--
-- Indexes gia ton pinaka: `ApplicationTemplates`
--
ALTER TABLE `ApplicationTemplates`
  ADD PRIMARY KEY (`template_id`),
  ADD UNIQUE KEY `uq_application_templates_template_key` (`template_key`),
  ADD KEY `idx_application_templates_category` (`category`);

--
-- Indexes gia ton pinaka: `Children`
--
ALTER TABLE `Children`
  ADD PRIMARY KEY (`child_id`),
  ADD KEY `fk_child_user` (`user_id`);

--
-- Indexes gia ton pinaka: `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`message_id`),
  ADD KEY `idx_created_at` (`created_at`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes gia ton pinaka: `EpikoinoniaPageSections`
--
ALTER TABLE `EpikoinoniaPageSections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `uq_epikoinonia_page_section_key` (`section_key`);

--
-- Indexes gia ton pinaka: `EshopSettings`
--
ALTER TABLE `EshopSettings`
  ADD PRIMARY KEY (`setting_id`);

--
-- Indexes gia ton pinaka: `Events`
--
ALTER TABLE `Events`
  ADD PRIMARY KEY (`event_id`);

--
-- Indexes gia ton pinaka: `EventsImages`
--
ALTER TABLE `EventsImages`
  ADD PRIMARY KEY (`ev_image_id`),
  ADD KEY `fk_ev_img` (`event_id`);

--
-- Indexes gia ton pinaka: `HomeBannerSlides`
--
ALTER TABLE `HomeBannerSlides`
  ADD PRIMARY KEY (`slide_id`);

--
-- Indexes gia ton pinaka: `HomePageSections`
--
ALTER TABLE `HomePageSections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `uq_home_page_section_key` (`section_key`);

--
-- Indexes gia ton pinaka: `InsurancePayments`
--
ALTER TABLE `InsurancePayments`
  ADD PRIMARY KEY (`payment_id`,`child_id`),
  ADD KEY `fk_ins_child` (`child_id`);

--
-- Indexes gia ton pinaka: `Logs`
--
ALTER TABLE `Logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `idx_logs_user_id` (`user_id`);

--
-- Indexes gia ton pinaka: `OrderItems`
--
ALTER TABLE `OrderItems`
  ADD PRIMARY KEY (`order_id`,`product_id`,`size`),
  ADD KEY `fk_oi_product` (`product_id`);

--
-- Indexes gia ton pinaka: `Orders`
--
ALTER TABLE `Orders`
  ADD PRIMARY KEY (`order_id`),
  ADD KEY `fk_order_user` (`user_id`);

--
-- Indexes gia ton pinaka: `ParentsPageGalleryImages`
--
ALTER TABLE `ParentsPageGalleryImages`
  ADD PRIMARY KEY (`image_id`);

--
-- Indexes gia ton pinaka: `ParentsPageSections`
--
ALTER TABLE `ParentsPageSections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `uq_parents_page_section_key` (`section_key`);

--
-- Indexes gia ton pinaka: `Payments`
--
ALTER TABLE `Payments`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `idx_payments_transaction_id` (`transaction_id`),
  ADD KEY `fk_pay_user` (`user_id`);

--
-- Indexes gia ton pinaka: `PaymentsDetails`
--
ALTER TABLE `PaymentsDetails`
  ADD PRIMARY KEY (`payment_item_id`),
  ADD KEY `fk_pd_payment` (`payment_id`),
  ADD KEY `fk_pd_product` (`product_id`);

--
-- Indexes gia ton pinaka: `Posts`
--
ALTER TABLE `Posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `fk_post_event` (`event_id`);

--
-- Indexes gia ton pinaka: `PostsImages`
--
ALTER TABLE `PostsImages`
  ADD PRIMARY KEY (`post_image_id`),
  ADD KEY `fk_post_img` (`post_id`);

--
-- Indexes gia ton pinaka: `PricingSettings`
--
ALTER TABLE `PricingSettings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes gia ton pinaka: `Products`
--
ALTER TABLE `Products`
  ADD PRIMARY KEY (`product_id`);

--
-- Indexes gia ton pinaka: `ProductsImages`
--
ALTER TABLE `ProductsImages`
  ADD PRIMARY KEY (`pro_image_id`),
  ADD KEY `fk_prod_img` (`product_id`);

--
-- Indexes gia ton pinaka: `ProductSizeOptions`
--
ALTER TABLE `ProductSizeOptions`
  ADD PRIMARY KEY (`size_option_id`),
  ADD UNIQUE KEY `uq_product_size_value` (`product_id`,`size_value`),
  ADD KEY `idx_product_size_product` (`product_id`);

--
-- Indexes gia ton pinaka: `Submissions`
--
ALTER TABLE `Submissions`
  ADD PRIMARY KEY (`application_id`,`user_id`),
  ADD KEY `fk_sub_user` (`user_id`);

--
-- Indexes gia ton pinaka: `SystemSchedule`
--
ALTER TABLE `SystemSchedule`
  ADD PRIMARY KEY (`ss_id`);

--
-- Indexes gia ton pinaka: `UsefulInformationSections`
--
ALTER TABLE `UsefulInformationSections`
  ADD PRIMARY KEY (`section_id`),
  ADD UNIQUE KEY `uq_useful_information_section_key` (`section_key`);

--
-- Indexes gia ton pinaka: `Users`
--
ALTER TABLE `Users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- ========================================================
-- Auto increment
-- Edo dilonetai poia pedia pairnoun automatiche sunexeia sto id
-- ========================================================

--
-- Auto increment gia ton pinaka: `AnnouncementAttachments`
--
ALTER TABLE `AnnouncementAttachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `Announcements`
--
ALTER TABLE `Announcements`
  MODIFY `announcement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `AnnouncementsImages`
--
ALTER TABLE `AnnouncementsImages`
  MODIFY `an_image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `ApplicationAttachments`
--
ALTER TABLE `ApplicationAttachments`
  MODIFY `attachment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `Applications`
--
ALTER TABLE `Applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `ApplicationsDocuments`
--
ALTER TABLE `ApplicationsDocuments`
  MODIFY `ap_document_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `ApplicationsFormFields`
--
ALTER TABLE `ApplicationsFormFields`
  MODIFY `field_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `ApplicationSubmissions`
--
ALTER TABLE `ApplicationSubmissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `ApplicationTemplates`
--
ALTER TABLE `ApplicationTemplates`
  MODIFY `template_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `Children`
--
ALTER TABLE `Children`
  MODIFY `child_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `message_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `EpikoinoniaPageSections`
--
ALTER TABLE `EpikoinoniaPageSections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `Events`
--
ALTER TABLE `Events`
  MODIFY `event_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `EventsImages`
--
ALTER TABLE `EventsImages`
  MODIFY `ev_image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `HomeBannerSlides`
--
ALTER TABLE `HomeBannerSlides`
  MODIFY `slide_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `HomePageSections`
--
ALTER TABLE `HomePageSections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `Logs`
--
ALTER TABLE `Logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `Orders`
--
ALTER TABLE `Orders`
  MODIFY `order_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `ParentsPageGalleryImages`
--
ALTER TABLE `ParentsPageGalleryImages`
  MODIFY `image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `ParentsPageSections`
--
ALTER TABLE `ParentsPageSections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `Payments`
--
ALTER TABLE `Payments`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `PaymentsDetails`
--
ALTER TABLE `PaymentsDetails`
  MODIFY `payment_item_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `Posts`
--
ALTER TABLE `Posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `PostsImages`
--
ALTER TABLE `PostsImages`
  MODIFY `post_image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `PricingSettings`
--
ALTER TABLE `PricingSettings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `Products`
--
ALTER TABLE `Products`
  MODIFY `product_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `ProductsImages`
--
ALTER TABLE `ProductsImages`
  MODIFY `pro_image_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `ProductSizeOptions`
--
ALTER TABLE `ProductSizeOptions`
  MODIFY `size_option_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `SystemSchedule`
--
ALTER TABLE `SystemSchedule`
  MODIFY `ss_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `UsefulInformationSections`
--
ALTER TABLE `UsefulInformationSections`
  MODIFY `section_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Auto increment gia ton pinaka: `Users`
--
ALTER TABLE `Users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- ========================================================
-- Foreign keys kai constraints
-- Edo fainontai oi sxeseis metaksy ton pinakon
-- Kai oi kanones gia delete / update opou yparxoun
-- ========================================================

--
-- Constraints gia ton pinaka: `AnnouncementAttachments`
--
ALTER TABLE `AnnouncementAttachments`
  ADD CONSTRAINT `fk_announcement_attachment` FOREIGN KEY (`announcement_id`) REFERENCES `Announcements` (`announcement_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `AnnouncementsImages`
--
ALTER TABLE `AnnouncementsImages`
  ADD CONSTRAINT `fk_an_img` FOREIGN KEY (`announcement_id`) REFERENCES `Announcements` (`announcement_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `ApplicationAttachments`
--
ALTER TABLE `ApplicationAttachments`
  ADD CONSTRAINT `fk_app_attachment_application` FOREIGN KEY (`application_id`) REFERENCES `Applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `Applications`
--
ALTER TABLE `Applications`
  ADD CONSTRAINT `fk_applications_created_by` FOREIGN KEY (`created_by`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_applications_template` FOREIGN KEY (`template_id`) REFERENCES `ApplicationTemplates` (`template_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `ApplicationsDocuments`
--
ALTER TABLE `ApplicationsDocuments`
  ADD CONSTRAINT `fk_app_doc` FOREIGN KEY (`application_id`) REFERENCES `Applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `ApplicationsFormFields`
--
ALTER TABLE `ApplicationsFormFields`
  ADD CONSTRAINT `fk_aff_app` FOREIGN KEY (`application_id`) REFERENCES `Applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `ApplicationSubmissions`
--
ALTER TABLE `ApplicationSubmissions`
  ADD CONSTRAINT `fk_appsub_application` FOREIGN KEY (`application_id`) REFERENCES `Applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appsub_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_appsub_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `Children`
--
ALTER TABLE `Children`
  ADD CONSTRAINT `fk_child_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `EventsImages`
--
ALTER TABLE `EventsImages`
  ADD CONSTRAINT `fk_ev_img` FOREIGN KEY (`event_id`) REFERENCES `Events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `InsurancePayments`
--
ALTER TABLE `InsurancePayments`
  ADD CONSTRAINT `fk_ins_child` FOREIGN KEY (`child_id`) REFERENCES `Children` (`child_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_ins_payment` FOREIGN KEY (`payment_id`) REFERENCES `Payments` (`payment_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `Logs`
--
ALTER TABLE `Logs`
  ADD CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `OrderItems`
--
ALTER TABLE `OrderItems`
  ADD CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `Orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `Orders`
--
ALTER TABLE `Orders`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `Payments`
--
ALTER TABLE `Payments`
  ADD CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `PaymentsDetails`
--
ALTER TABLE `PaymentsDetails`
  ADD CONSTRAINT `fk_pd_payment` FOREIGN KEY (`payment_id`) REFERENCES `Payments` (`payment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pd_product` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `Posts`
--
ALTER TABLE `Posts`
  ADD CONSTRAINT `fk_post_event` FOREIGN KEY (`event_id`) REFERENCES `Events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `PostsImages`
--
ALTER TABLE `PostsImages`
  ADD CONSTRAINT `fk_post_img` FOREIGN KEY (`post_id`) REFERENCES `Posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `ProductsImages`
--
ALTER TABLE `ProductsImages`
  ADD CONSTRAINT `fk_prod_img` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `ProductSizeOptions`
--
ALTER TABLE `ProductSizeOptions`
  ADD CONSTRAINT `fk_product_size_product` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints gia ton pinaka: `Submissions`
--
ALTER TABLE `Submissions`
  ADD CONSTRAINT `fk_sub_ap` FOREIGN KEY (`application_id`) REFERENCES `Applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_sub_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
