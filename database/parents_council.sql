/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `AnnouncementAttachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AnnouncementAttachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attachment_id`),
  KEY `fk_announcement_attachment` (`announcement_id`),
  CONSTRAINT `fk_announcement_attachment` FOREIGN KEY (`announcement_id`) REFERENCES `Announcements` (`announcement_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Announcements` (
  `announcement_id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_title` varchar(255) NOT NULL,
  `announcement_date` date DEFAULT NULL,
  `publish_date` date NOT NULL,
  `announcement_description` text DEFAULT NULL,
  `gdpr_notice` text DEFAULT NULL,
  PRIMARY KEY (`announcement_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `AnnouncementsImages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `AnnouncementsImages` (
  `an_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  PRIMARY KEY (`an_image_id`),
  KEY `fk_an_img` (`announcement_id`),
  CONSTRAINT `fk_an_img` FOREIGN KEY (`announcement_id`) REFERENCES `Announcements` (`announcement_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ApplicationAttachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApplicationAttachments` (
  `attachment_id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_filename` varchar(255) DEFAULT NULL,
  `upload_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`attachment_id`),
  KEY `idx_app_attachment_order` (`application_id`,`upload_order`),
  CONSTRAINT `fk_app_attachment_application` FOREIGN KEY (`application_id`) REFERENCES `Applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ApplicationSubmissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApplicationSubmissions` (
  `submission_id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `submission_type` varchar(50) NOT NULL DEFAULT 'manual',
  `status` varchar(50) NOT NULL DEFAULT 'submitted',
  `form_data` longtext DEFAULT NULL,
  `uploaded_files` longtext DEFAULT NULL,
  `signature_data` longtext DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_at` datetime DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`submission_id`),
  KEY `idx_app_submissions_application` (`application_id`,`submitted_at`),
  KEY `idx_app_submissions_user` (`user_id`),
  KEY `idx_app_submissions_reviewed_by` (`reviewed_by`),
  CONSTRAINT `fk_appsub_application` FOREIGN KEY (`application_id`) REFERENCES `Applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_appsub_reviewed_by` FOREIGN KEY (`reviewed_by`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_appsub_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ApplicationTemplates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApplicationTemplates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `template_key` varchar(150) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) NOT NULL DEFAULT 'standard',
  `form_schema` longtext DEFAULT NULL,
  `is_system_template` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`template_id`),
  UNIQUE KEY `uq_application_templates_template_key` (`template_key`),
  KEY `idx_application_templates_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Applications` (
  `application_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`application_id`),
  KEY `idx_applications_template_id` (`template_id`),
  KEY `idx_applications_status_dates` (`status`,`open_date`,`due_date`),
  KEY `idx_applications_created_by` (`created_by`),
  CONSTRAINT `fk_applications_created_by` FOREIGN KEY (`created_by`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_applications_template` FOREIGN KEY (`template_id`) REFERENCES `ApplicationTemplates` (`template_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ApplicationsDocuments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApplicationsDocuments` (
  `ap_document_id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  PRIMARY KEY (`ap_document_id`),
  KEY `fk_app_doc` (`application_id`),
  CONSTRAINT `fk_app_doc` FOREIGN KEY (`application_id`) REFERENCES `Applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ApplicationsFormFields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ApplicationsFormFields` (
  `field_id` int(11) NOT NULL AUTO_INCREMENT,
  `application_id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_type` varchar(50) NOT NULL DEFAULT 'text',
  `field_order` int(11) NOT NULL DEFAULT 0,
  `is_required` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`field_id`),
  KEY `idx_application_order` (`application_id`,`field_order`),
  CONSTRAINT `fk_aff_app` FOREIGN KEY (`application_id`) REFERENCES `Applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Children`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Children` (
  `child_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `surname` varchar(100) NOT NULL,
  `date_of_birth` date NOT NULL,
  `school_class` varchar(20) NOT NULL,
  PRIMARY KEY (`child_id`),
  KEY `fk_child_user` (`user_id`),
  CONSTRAINT `fk_child_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `EpikoinoniaPageSections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EpikoinoniaPageSections` (
  `section_id` int(11) NOT NULL AUTO_INCREMENT,
  `section_key` varchar(100) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_subtitle` text DEFAULT NULL,
  `content_json` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `uq_epikoinonia_page_section_key` (`section_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Events` (
  `event_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_title` varchar(255) NOT NULL,
  `event_description` text DEFAULT NULL,
  `gdpr_notice` text DEFAULT NULL,
  `event_date` datetime NOT NULL,
  `publish_date` date NOT NULL,
  PRIMARY KEY (`event_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `EventsImages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `EventsImages` (
  `ev_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  PRIMARY KEY (`ev_image_id`),
  KEY `fk_ev_img` (`event_id`),
  CONSTRAINT `fk_ev_img` FOREIGN KEY (`event_id`) REFERENCES `Events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `HomeBannerSlides`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HomeBannerSlides` (
  `slide_id` int(11) NOT NULL AUTO_INCREMENT,
  `image_path` varchar(255) NOT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`slide_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `HomePageSections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `HomePageSections` (
  `section_id` int(11) NOT NULL AUTO_INCREMENT,
  `section_key` varchar(100) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_subtitle` text DEFAULT NULL,
  `content_json` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `uq_home_page_section_key` (`section_key`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `InsurancePayments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `InsurancePayments` (
  `payment_id` int(11) NOT NULL,
  `child_id` int(11) NOT NULL,
  PRIMARY KEY (`payment_id`,`child_id`),
  KEY `fk_ins_child` (`child_id`),
  CONSTRAINT `fk_ins_child` FOREIGN KEY (`child_id`) REFERENCES `Children` (`child_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ins_payment` FOREIGN KEY (`payment_id`) REFERENCES `Payments` (`payment_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_logs_user_id` (`user_id`),
  CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=23 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `OrderItems`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `OrderItems` (
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `price_at_purchase` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `size` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`order_id`,`product_id`),
  KEY `fk_oi_product` (`product_id`),
  CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `Orders` (`order_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_oi_product` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `order_status` enum('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
  PRIMARY KEY (`order_id`),
  KEY `fk_order_user` (`user_id`),
  CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ParentsPageGalleryImages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ParentsPageGalleryImages` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `full_image_path` varchar(255) NOT NULL,
  `thumb_image_path` varchar(255) DEFAULT NULL,
  `alt_text` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`image_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ParentsPageSections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ParentsPageSections` (
  `section_id` int(11) NOT NULL AUTO_INCREMENT,
  `section_key` varchar(100) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_subtitle` text DEFAULT NULL,
  `content_json` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `uq_parents_page_section_key` (`section_key`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` datetime NOT NULL DEFAULT current_timestamp(),
  `payment_status` enum('pending','completed','failed','refunded') NOT NULL DEFAULT 'pending',
  `payment_type` enum('membership','insurance','product') NOT NULL,
  `transaction_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`payment_id`),
  KEY `idx_payments_transaction_id` (`transaction_id`),
  KEY `fk_pay_user` (`user_id`),
  CONSTRAINT `fk_pay_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=103 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `PaymentsDetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PaymentsDetails` (
  `payment_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `payment_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price_at_purchase` decimal(10,2) NOT NULL,
  `size` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`payment_item_id`),
  KEY `fk_pd_payment` (`payment_id`),
  KEY `fk_pd_product` (`product_id`),
  CONSTRAINT `fk_pd_payment` FOREIGN KEY (`payment_id`) REFERENCES `Payments` (`payment_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_pd_product` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Posts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Posts` (
  `post_id` int(11) NOT NULL AUTO_INCREMENT,
  `event_id` int(11) NOT NULL,
  `post_title` varchar(255) NOT NULL,
  `post_content` text DEFAULT NULL,
  PRIMARY KEY (`post_id`),
  KEY `fk_post_event` (`event_id`),
  CONSTRAINT `fk_post_event` FOREIGN KEY (`event_id`) REFERENCES `Events` (`event_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `PostsImages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PostsImages` (
  `post_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  PRIMARY KEY (`post_image_id`),
  KEY `fk_post_img` (`post_id`),
  CONSTRAINT `fk_post_img` FOREIGN KEY (`post_id`) REFERENCES `Posts` (`post_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `PricingSettings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `PricingSettings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subscription_price` decimal(10,2) NOT NULL,
  `insurance_price` decimal(10,2) NOT NULL,
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `product_description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`product_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ProductsImages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ProductsImages` (
  `pro_image_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  PRIMARY KEY (`pro_image_id`),
  KEY `fk_prod_img` (`product_id`),
  CONSTRAINT `fk_prod_img` FOREIGN KEY (`product_id`) REFERENCES `Products` (`product_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Submissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Submissions` (
  `application_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `text_content` text DEFAULT NULL,
  `submission_data` longtext DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp(),
  `admin_seen_at` datetime DEFAULT NULL,
  `sub_status` enum('waiting','approved','rejected') DEFAULT 'waiting',
  PRIMARY KEY (`application_id`,`user_id`),
  KEY `fk_sub_user` (`user_id`),
  CONSTRAINT `fk_sub_ap` FOREIGN KEY (`application_id`) REFERENCES `Applications` (`application_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_sub_user` FOREIGN KEY (`user_id`) REFERENCES `Users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `SystemSchedule`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SystemSchedule` (
  `ss_id` int(11) NOT NULL AUTO_INCREMENT,
  `feature` enum('registration','delete_users','cleanup_submissions') NOT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime NOT NULL,
  `ss_status` enum('active','inactive') DEFAULT 'inactive',
  PRIMARY KEY (`ss_id`),
  UNIQUE KEY `uq_system_schedule_feature` (`feature`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `UsefulInformationSections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `UsefulInformationSections` (
  `section_id` int(11) NOT NULL AUTO_INCREMENT,
  `section_key` varchar(100) NOT NULL,
  `section_title` varchar(255) NOT NULL,
  `section_subtitle` text DEFAULT NULL,
  `content_json` longtext DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`section_id`),
  UNIQUE KEY `uq_useful_information_section_key` (`section_key`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `Users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `Users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
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
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contact_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contact_messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` longtext NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`message_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
