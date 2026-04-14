-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Apr 14, 2026 at 04:09 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";
USE `parents_council`;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS;
SET FOREIGN_KEY_CHECKS = 0;


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `parents_council`
--


INSERT INTO `Announcements` (`announcement_id`, `announcement_title`, `announcement_date`, `publish_date`, `announcement_description`, `gdpr_notice`) VALUES
(1, 'Πρόγραμμα Ενιαίων Τελικών Προαγωγικών & Απολυτήριων Γραπτών Εξετάσεων Γυμνασίων', '2026-04-03', '2026-04-14', 'Στο πιο κάτω αρχείο παρουσιάζεται το πρόγραμμα των Ενιαίων Τελικών Προαγωγικών και Απολυτήριων Γραπτών Εξετάσεων Γυμνασίων, με αναλυτική καταγραφή των ημερομηνιών και των μαθημάτων.', ''),
(2, 'Κλήρωση Πασχαλινού Λαχείου', '2026-04-03', '2026-04-14', 'Στο πιο κάτω αρχείο παρουσιάζονται πληροφορίες σχετικά με την κλήρωση του Πασχαλινού Λαχείου, συμπεριλαμβανομένων των αποτελεσμάτων και των σχετικών λεπτομερειών.', ''),
(3, 'Προγραμματισμός Απριλίου – Γυμνάσιο Αγίου Αθανασίου', '2026-03-30', '2026-04-14', 'Στο πιο κάτω αρχείο παρουσιάζεται ο προγραμματισμός του Απριλίου για το Γυμνάσιο Αγίου Αθανασίου, με αναλυτική καταγραφή των δραστηριοτήτων και των προγραμματισμένων εκδηλώσεων.', '');

--
-- Dumping data for table `AnnouncementAttachments`
--

INSERT INTO `AnnouncementAttachments` (`attachment_id`, `announcement_id`, `file_path`, `original_name`, `created_at`) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Announcements_docs/announcement_attachment_69de2f521560b8.13949445.pdf', 'Arxeio 1.pdf', '2026-04-14 12:13:06'),
(2, 2, '/parents-council-platform-group5/public/assets/Announcements_docs/announcement_attachment_69de3157c4c3d0.96612284.pdf', 'Arxeio 2.pdf', '2026-04-14 12:21:43'),
(3, 3, '/parents-council-platform-group5/public/assets/Announcements_docs/announcement_attachment_69de32711daff6.13168279.pdf', 'Arxeio3.pdf', '2026-04-14 12:26:25');

--
-- Dumping data for table `AnnouncementsImages`
--

INSERT INTO `AnnouncementsImages` (`an_image_id`, `announcement_id`, `image_path`) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Announcements_img/69de2fd660c6a_1776168918.jpg'),
(2, 3, '/parents-council-platform-group5/public/assets/Announcements_img/69de32711d583_1776169585.png'),
(3, 2, '/parents-council-platform-group5/public/assets/Announcements_img/69de37639bd60_1776170851.png'),
(4, 2, '/parents-council-platform-group5/public/assets/Announcements_img/69de37639c39c_1776170851.png');

--
-- Dumping data for table `Users`
--

INSERT INTO `Users` (`user_id`, `name`, `surname`, `email`, `password`, `phone_number`, `number_of_children`, `role`, `account_status`, `token`, `token_expiry`, `created_at`) VALUES
(1, 'Admin', 'User', 'admin@test.com', '$2y$10$iEzB1rYBGWURYZJnbpPg/ulK0GD/tDI/6ktzuY7hmTfQnUCOmzDxe', '+35799123456', 0, 'admin', 'active', 'fdb9e92eb8dd2097f3b7d080f31834ff477ce5a7bf912b699730bcd93d20b09b', '2026-04-14 15:10:57', '2026-04-11 19:03:11'),
(2, 'John', 'Doe', 'parent1@test.com', '$2y$10$5MryI34DorxxyDm1IoDtiuit5ek4dsK14UR6rBWn8ce7LSzeRVHYW', '+35799112233', 2, 'parent', 'active', NULL, NULL, '2026-04-11 19:03:11'),
(3, 'Jane', 'Smith', 'parent2@test.com', '$2y$10$dITemBxHXfD1VqAQTMCxnOZ7jU1ibL7u.GiNng2snsRgQ339MZNYi', '+35799445566', 1, 'parent', 'waiting_payment', NULL, NULL, '2026-04-11 19:03:11');

--
-- Dumping data for table `ApplicationTemplates`
--

INSERT INTO `ApplicationTemplates` (`template_id`, `template_key`, `name`, `description`, `category`, `form_schema`, `is_system_template`, `created_at`, `updated_at`) VALUES
(1, 'subscription-insurance', 'Συνδρομή / Ασφάλιση', 'Ετήσια συνδρομή και ασφαλιστική κάλυψη μαθητή', 'standard', '{\"sections\":[{\"title\":\"Στοιχεία Μαθητή\",\"fields\":[{\"name\":\"student_name\",\"label\":\"Ονοματεπώνυμο Μαθητή\",\"type\":\"text\",\"required\":true,\"help_text\":\"\"},{\"name\":\"student_birthdate\",\"label\":\"Ημερομηνία Γέννησης\",\"type\":\"date\",\"required\":true,\"help_text\":\"\"},{\"name\":\"student_class\",\"label\":\"Τμήμα \\/ Τάξη\",\"type\":\"select\",\"required\":true,\"options\":[\"A\",\"B\",\"C\",\"Γ\'\"],\"help_text\":\"\"}]},{\"title\":\"Στοιχεία Κηδεμόνα\",\"fields\":[{\"name\":\"guardian_name\",\"label\":\"Ονοματεπώνυμο Κηδεμόνα\",\"type\":\"text\",\"required\":true,\"help_text\":\"\"},{\"name\":\"guardian_phone\",\"label\":\"Τηλέφωνο Επικοινωνίας\",\"type\":\"tel\",\"required\":true,\"help_text\":\"\"},{\"name\":\"guardian_email\",\"label\":\"Email Επικοινωνίας\",\"type\":\"email\",\"required\":true,\"help_text\":\"\"}]},{\"title\":\"Εγγραφές\",\"fields\":[{\"name\":\"subscription_checkbox\",\"label\":\"Συνδρομή στο Σύνδεσμο\",\"type\":\"checkbox\",\"required\":false,\"help_text\":\"Αποδέχομαι τη συνδρομή\"},{\"name\":\"insurance_checkbox\",\"label\":\"Ασφαλιστική Κάλυψη\",\"type\":\"checkbox\",\"required\":false,\"help_text\":\"Αποδέχομαι την ασφαλιστική κάλυψη\"}]},{\"title\":\"Συναίνεση Επικοινωνίας\",\"fields\":[{\"name\":\"consent_communication\",\"label\":\"Λήψη Ειδοποιήσεων\",\"type\":\"radio\",\"required\":true,\"options\":[\"Ναι\",\"Όχι\"],\"help_text\":\"Αποδέχομαι να λαμβάνω ειδοποιήσεις\"},{\"name\":\"consent_viber\",\"label\":\"Viber Community\",\"type\":\"radio\",\"required\":true,\"options\":[\"Ναι\",\"Όχι\"],\"help_text\":\"Αποδέχομαι συμμετοχή στην ομάδα Viber\"}]},{\"title\":\"Επιβεβαίωση\",\"fields\":[{\"name\":\"signature\",\"label\":\"Υπογραφή Κηδεμόνα\",\"type\":\"signature\",\"required\":true,\"help_text\":\"\"},{\"name\":\"signature_date\",\"label\":\"Ημερομηνία\",\"type\":\"date\",\"required\":true,\"help_text\":\"\"}]}]}', 1, '2026-04-11 19:10:16', '2026-04-11 19:10:16'),
(2, 'event-consent', 'Συναίνεση Συμμετοχής σε Εκδήλωση', 'Μορφή συναίνεσης για συμμετοχή σε σχολική εκδήλωση ή δραστηριότητα', 'event', '{\"sections\":[{\"title\":\"Πληροφορίες Μαθητή\",\"fields\":[{\"name\":\"student_name_event\",\"label\":\"Ονοματεπώνυμο Μαθητή\",\"type\":\"text\",\"required\":true,\"help_text\":\"\"},{\"name\":\"student_class_event\",\"label\":\"Τάξη\\/Τμήμα\",\"type\":\"select\",\"required\":true,\"options\":[\"A\",\"B\",\"C\",\"Γ\'\"],\"help_text\":\"\"}]},{\"title\":\"Πληροφορίες Κηδεμόνα\",\"fields\":[{\"name\":\"guardian_name_event\",\"label\":\"Ονοματεπώνυμο Κηδεμόνα\",\"type\":\"text\",\"required\":true,\"help_text\":\"\"}]},{\"title\":\"Συναίνεση\",\"fields\":[{\"name\":\"consent\",\"label\":\"Δηλώνω ότι:\",\"type\":\"radio\",\"required\":true,\"options\":[\"Συναινώ\",\"Δεν Συναινώ\"],\"help_text\":\"\"},{\"name\":\"comments\",\"label\":\"Σχόλια \\/ Παρατηρήσεις\",\"type\":\"textarea\",\"required\":false,\"help_text\":\"Προαιρετικό\"}]},{\"title\":\"Υπογραφή\",\"fields\":[{\"name\":\"signature_event\",\"label\":\"Υπογραφή\",\"type\":\"signature\",\"required\":true,\"help_text\":\"\"},{\"name\":\"signature_date_event\",\"label\":\"Ημερομηνία\",\"type\":\"date\",\"required\":true,\"help_text\":\"\"}]}]}', 1, '2026-04-11 19:10:16', '2026-04-11 19:10:16');

--
-- Dumping data for table `Applications`
--

INSERT INTO `Applications` (`application_id`, `template_id`, `application_title`, `title`, `application_description`, `description`, `submission_type`, `academic_year`, `open_date`, `due_date`, `status`, `allow_online_submission`, `allow_file_submission`, `require_signature`, `form_schema`, `target_audience`, `created_by`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Field Trip Permission', 'Field Trip Permission', 'Form to allow your child to attend field trip', 'Form to allow your child to attend field trip', 'file', '2025-2026', '2026-03-01', '2026-06-15', 'published', 1, 1, 0, NULL, NULL, 1, '2026-04-11 19:03:11', '2026-04-11 19:03:11'),
(2, NULL, 'Library Membership', 'Library Membership', 'Sign up for school library access', 'Sign up for school library access', 'file', '2025-2026', '2026-03-01', '2026-06-30', 'published', 1, 1, 0, NULL, NULL, 1, '2026-04-11 19:03:11', '2026-04-11 19:03:11');

--
-- Dumping data for table `ApplicationsDocuments`
--

INSERT INTO `ApplicationsDocuments` (`ap_document_id`, `application_id`, `file_path`) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Applications_docs/feedback.pdf'),
(2, 2, '/parents-council-platform-group5/public/assets/Applications_docs/questionnaire.pdf');

--
-- Dumping data for table `Children`
--

INSERT INTO `Children` (`child_id`, `user_id`, `name`, `surname`, `date_of_birth`, `school_class`) VALUES
(1, 2, 'Chris', 'Doe', '2015-05-10', '5A'),
(2, 2, 'Anna', 'Doe', '2017-09-22', '3B'),
(3, 3, 'Mike', 'Smith', '2016-02-11', '4A');

--
-- Dumping data for table `EpikoinoniaPageSections`
--

INSERT INTO `EpikoinoniaPageSections` (`section_id`, `section_key`, `section_title`, `section_subtitle`, `content_json`, `updated_at`) VALUES
(1, 'page_header', 'Επικοινωνία', 'Επικοινωνήστε μαζί μας για οποιαδήποτε ερώτηση ή πληροφορία.', '{\"eyebrow\":\"Υποστήριξη Και Στοιχεία\",\"icon\":\"fas fa-envelope\"}', '2026-04-11 19:03:11'),
(2, 'contact_info', 'Πληροφορίες Επικοινωνίας', 'Βρείτε τη διεύθυνση, τα τηλέφωνα, το email και το ωράριο της σχολικής μονάδας.', '{\"cards\":[{\"title\":\"Διεύθυνση\",\"text\":\"Χρίστου Παπαδούρη 50\\n4105 Άγιος Αθανάσιος, Λεμεσός\",\"icon\":\"fas fa-map-marker-alt\",\"link_label\":\"\",\"link_url\":\"\"},{\"title\":\"Τηλέφωνο\",\"text\":\"Τηλέφωνα: 25694750, 25694752\\nΤηλεομοιότυπο: 25694755\",\"icon\":\"fas fa-phone\",\"link_label\":\"\",\"link_url\":\"\"},{\"title\":\"Email\",\"text\":\"\",\"icon\":\"fas fa-envelope\",\"link_label\":\"gym-ag-athanasios-lem@schools.ac.cy\",\"link_url\":\"mailto:gym-ag-athanasios-lem@schools.ac.cy\"},{\"title\":\"Ώρες Λειτουργίας\",\"text\":\"Δευ-Παρ - 7.30-13.35\",\"icon\":\"fas fa-clock\",\"link_label\":\"\",\"link_url\":\"\"}]}', '2026-04-11 19:03:11'),
(3, 'map_section', 'Βρείτε μας στο Χάρτη', 'Η τοποθεσία της σχολικής μονάδας στο Google Maps.', '{\"embed_url\":\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3279.4575341666614!2d33.0611131!3d34.7188599!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14e734bc13013dc9%3A0x9c01ea2ef75a5b4d!2zzpPPhc68zr3OrM-DzrnOvyDOkc6zzq_Ov8-FIM6RzrjOsc69zrHPg86vzr_PhQ!5e0!3m2!1sel!2s!4v1773496500123!5m2!1sel!2s\"}', '2026-04-11 19:03:11'),
(4, 'form_section', 'Στείλτε μας Μήνυμα', 'Συμπληρώστε τη φόρμα και θα επικοινωνήσουμε μαζί σας το συντομότερο δυνατό.', '{\"description\":\"\",\"button_text\":\"Αποστολή Μηνύματος\",\"success_message\":\"Το μήνυμά σας λήφθηκε. Θα σας απαντήσουμε το συντομότερο δυνατό.\"}', '2026-04-11 19:03:11'),
(5, 'social_section', 'Βρείτε μας στα social networks', 'Ακολουθήστε τις επίσημες σελίδες μας για νέα και ενημερώσεις.', '{\"items\":[{\"title\":\"Facebook\",\"url\":\"https://www.facebook.com/profile.php?id=100085835704152\",\"icon\":\"fab fa-facebook-f\"},{\"title\":\"X\",\"url\":\"https://x.com/cymoec\",\"icon\":\"fab fa-twitter\"},{\"title\":\"YouTube\",\"url\":\"https://www.youtube.com/cymoec\",\"icon\":\"fab fa-youtube\"}]}', '2026-04-11 19:03:11');
INSERT INTO Events (event_id, event_title, event_description, gdpr_notice, event_date, publish_date) VALUES
(1, 'Τόμπολα & Μουσική Βραδιά', 'Παρασκευή 15 Μαΐου 2026
Ώρα: 20:00 - 23:00', 'Το φωτογραφικό υλικό της εκδήλωσης δημοσιεύεται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με τις ισχύουσες εγκρίσεις/πολιτικές του σχολείου.', '2026-05-15 20:00:00', '2026-04-14');

INSERT INTO EventsImages (ev_image_id, event_id, image_path) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Events_img/d99679d4-0f91-406b-adee-8d1590850f82.jpg');

--
-- Dumping data for table `HomeBannerSlides`
--

INSERT INTO `HomeBannerSlides` (`slide_id`, `image_path`, `alt_text`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '/parents-council-platform-group5/public/assets/img/home-school-banner.png', 'Γυμνάσιο Αγίου Αθανασίου - Banner 1', 1, 1, '2026-04-13 18:33:27', '2026-04-13 18:33:27'),
(2, '/parents-council-platform-group5/public/assets/img/home-school-banner-2.png', 'Γυμνάσιο Αγίου Αθανασίου - Banner 2', 2, 1, '2026-04-13 18:33:27', '2026-04-13 18:33:27'),
(3, '/parents-council-platform-group5/public/assets/img/home-school-banner-3.png', 'Γυμνάσιο Αγίου Αθανασίου - Banner 3', 3, 1, '2026-04-13 18:33:27', '2026-04-13 18:33:27');

--
-- Dumping data for table `HomePageSections`
--

INSERT INTO `HomePageSections` (`section_id`, `section_key`, `section_title`, `section_subtitle`, `content_json`, `updated_at`) VALUES
(1, 'hero_section', 'Σύνδεσμος Γονέων & Κηδεμόνων Γυμνασίου Αγίου Αθανασίου', 'Στην ιστοσελίδα μας μπορείτε να ενημερώνεστε για όλες τις ανακοινώσεις, δράσεις και εκδηλώσεις του Συνδέσμου Γονέων. Μπορείτε να βρείτε χρήσιμες πληροφορίες, αιτήσεις, φωτογραφικό υλικό και πρωτοβουλίες που συμβάλλουν στη δημιουργία ενός καλύτερου σχολικού περιβάλλοντος για τα παιδιά μας.', '{\"kicker\":\"Καλωσορίσατε στην επίσημη ιστοσελίδα\",\"announcements_button_label\":\"Ανακοινώσεις\",\"events_button_label\":\"Εκδηλώσεις\"}', '2026-04-14 13:06:00'),
(2, 'calendar_section', 'Ημερολόγιο', '', '[]', '2026-04-14 13:07:01'),
(3, 'announcements_section', 'Τελευταίες Ανακοινώσεις', '', '{\"button_label\":\"Όλες οι Ανακοινώσεις\"}', '2026-04-14 13:06:00'),
(4, 'events_section', 'Τελευταίες Εκδηλώσεις', '', '{\"button_label\":\"Όλες οι Εκδηλώσεις\"}', '2026-04-14 13:06:00'),
(5, 'banner_section', 'Banner Αρχικής', '', '{\"slides\":[{\"src\":\"/parents-council-platform-group5/public/assets/Home_img/home_banner_69de461935c6e8.55358859.png\",\"alt\":\"Γυμνάσιο Αγίου Αθανασίου - Banner 1\",\"hidden\":false},{\"src\":\"/parents-council-platform-group5/public/assets/Home_img/home_banner_69de49201f29e2.96931567.png\",\"alt\":\"Γυμνάσιο Αγίου Αθανασίου - Banner 2\",\"hidden\":false},{\"src\":\"/parents-council-platform-group5/public/assets/Home_img/home_banner_69de47d4c95ee6.67743613.png\",\"alt\":\"Γυμνάσιο Αγίου Αθανασίου - Banner 3\",\"hidden\":false}]}', '2026-04-14 14:03:12');

--
-- Dumping data for table `Logs`
--

INSERT INTO `Logs` (`log_id`, `user_id`, `action`, `description`, `created_at`) VALUES
(1, 2, 'PAYMENT_CREATED', 'User created insurance payment (ID: 102) for 2 children; JCC orderId: 99681fbc-4657-7fae-a146-d9772eeb7620', '2026-04-13 20:27:08'),
(2, NULL, 'user_registration', 'New parent registered with email: mariosshittas@gmail.com', '2026-04-13 20:29:10'),
(3, 1, 'ADMIN_USER_APPROVAL_EMAIL_SENT', 'Approval email sent to user #4 (mariosshittas@gmail.com).', '2026-04-13 20:29:32'),
(4, 1, 'ADMIN_USER_UPDATED', 'Updated user #4 (mariosshittas@gmail.com); role=parent, status=waiting_payment.', '2026-04-13 20:29:32'),
(5, NULL, 'PAYMENT_CREATED', 'User created membership payment (ID: 103) and insurance payment (ID: 104); JCC orderId: ee49d5fb-38c8-766f-a886-a40f2eeb7620', '2026-04-13 20:29:47'),
(6, NULL, 'PAYMENT_COMPLETED', 'JCC payment completed. Order ID: ee49d5fb-38c8-766f-a886-a40f2eeb7620, Transaction ID: 88747638109143495572', '2026-04-13 20:30:09'),
(7, NULL, 'ACTIVATION_CREDENTIALS_SENT', 'Activation credentials email sent.', '2026-04-13 20:30:11'),
(8, 1, 'ADMIN_USER_UPDATED', 'Updated user #4 (mariosshittas@gmail.com); role=parent, status=active.', '2026-04-13 20:39:00'),
(9, 1, 'ADMIN_USER_REJECTION_EMAIL_SENT', 'Rejection email sent to user #4 (mariosshittas@gmail.com).', '2026-04-13 20:40:39'),
(10, 1, 'ADMIN_USER_UPDATED', 'Updated user #4 (mariosshittas@gmail.com); role=parent, status=rejected.', '2026-04-13 20:40:39'),
(11, 1, 'ADMIN_USER_DELETED', 'Deleted user #4 (mariosshittas@gmail.com).', '2026-04-13 20:51:12'),
(12, NULL, 'user_registration', 'New parent registered with email: mariosshittas@gmail.com', '2026-04-13 20:54:34'),
(13, 1, 'ADMIN_USER_APPROVAL_EMAIL_SENT', 'Approval email sent to user #5 (mariosshittas@gmail.com).', '2026-04-13 20:55:08'),
(14, 1, 'ADMIN_USER_UPDATED', 'Updated user #5 (mariosshittas@gmail.com); role=parent, status=waiting_payment.', '2026-04-13 20:55:08'),
(15, NULL, 'PAYMENT_CREATED', 'User created membership payment (ID: 105) and insurance payment (ID: 106); JCC orderId: f7e13147-b5ea-78d2-94aa-56fe2eeb7620', '2026-04-13 20:55:24'),
(16, NULL, 'PAYMENT_COMPLETED', 'JCC payment completed. Order ID: f7e13147-b5ea-78d2-94aa-56fe2eeb7620, Transaction ID: 06313994115347811422', '2026-04-13 20:55:40'),
(17, NULL, 'ACTIVATION_CREDENTIALS_SENT', 'Activation credentials email sent.', '2026-04-13 20:55:41'),
(18, 1, 'ADMIN_USER_DELETED', 'Deleted user #5 (mariosshittas@gmail.com).', '2026-04-14 15:38:55');

--
-- Dumping data for table `Orders`
--

INSERT INTO `Orders` (`order_id`, `user_id`, `total_price`, `created_at`, `order_status`) VALUES
(1, 2, 0.00, '2026-04-11 22:21:41', 'pending');

--
-- Dumping data for table `ParentsPageGalleryImages`
--

INSERT INTO `ParentsPageGalleryImages` (`image_id`, `full_image_path`, `thumb_image_path`, `alt_text`, `sort_order`, `created_at`) VALUES
(1, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/7/3.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/7/i18npic.C240x240.3.jpg', 'Φωτογραφικό υλικό σχολείου', 1, '2026-04-11 19:03:11'),
(2, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/6/2.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/6/i18npic.C240x240.2.jpg', 'Φωτογραφικό υλικό σχολείου', 2, '2026-04-11 19:03:11'),
(3, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/5/21.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/5/i18npic.C240x240.21.jpg', 'Φωτογραφικό υλικό σχολείου', 3, '2026-04-11 19:03:11'),
(4, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/8.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/i18npic.C240x240.8.jpg', 'Φωτογραφικό υλικό σχολείου', 4, '2026-04-11 19:03:11'),
(5, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/1/1.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/1/i18npic.C240x240.1.jpg', 'Φωτογραφικό υλικό σχολείου', 5, '2026-04-11 19:03:11'),
(6, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/1/2.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/1/i18npic.C240x240.2.jpg', 'Φωτογραφικό υλικό σχολείου', 6, '2026-04-11 19:03:11');

--
-- Dumping data for table `ParentsPageSections`
--

INSERT INTO `ParentsPageSections` (`section_id`, `section_key`, `section_title`, `section_subtitle`, `content_json`, `updated_at`) VALUES
(1, 'page_header', 'Συνδεσμος Γωνεων', 'Χρήσιμες πληροφορίες και στοιχεία επικοινωνίας για τον Συνδεσμο Γωνεων.', '{\"public_eyebrow\":\"Δημόσια Πύλη\",\"parent_eyebrow\":\"Χώρος Γονέα\",\"icon\":\"fas fa-users\"}', '2026-04-11 19:03:11'),
(2, 'history_section', 'Σύντομα για το Γυμνάσιο Αγίου Αθανασίου', '', '{\"eyebrow\":\"Ιστορικό Σχολείου\",\"items\":[\"Το σχολείο άρχισε τη λειτουργία του τον Σεπτέμβριο του 1999 και από το 2000-2001 λειτουργούν και οι τρεις τάξεις.\",\"Φέρει το όνομα του Αγίου Αθανασίου και εξυπηρετεί μαθητές από τον Δήμο και πολλές κοινότητες της ευρύτερης περιοχής Λεμεσού.\",\"Φοιτούν επίσης μαθητές από πολλές χώρες, ενώ από τη σχολική χρονιά 2024-2025 λειτουργεί και τμήμα μαθητών με μεταναστευτική βιογραφία.\",\"Διαθέτει πλήρεις κτηριακές και εργαστηριακές εγκαταστάσεις (εργαστήρια, βιβλιοθήκη, αίθουσες ειδικοτήτων και αθλητικούς χώρους).\",\"Οι εκπαιδευτικοί υλοποιούν δράσεις και προγράμματα (όπως Erasmus+) με στόχο την καλλιέργεια δημοκρατικής και κριτικής σκέψης.\"]}', '2026-04-11 19:03:11'),
(3, 'schedule_section', 'Εσωτερικοί Κανονισμοί - Ωράριο', '', '{\"eyebrow\":\"Σχολική Χρονιά 2025 - 2026\",\"period_label\":\"Περίοδος\",\"time_label\":\"Ώρα\",\"blocks\":[{\"title\":\"Δευτέρα - Τρίτη - Πέμπτη (8ωρο)\",\"rows\":[{\"period\":\"1η\",\"time\":\"07:30 - 08:10\"},{\"period\":\"2η\",\"time\":\"08:10 - 08:50\"},{\"period\":\"Διάλειμμα\",\"time\":\"08:50 - 09:10\"},{\"period\":\"3η\",\"time\":\"09:10 - 09:50\"},{\"period\":\"4η\",\"time\":\"09:50 - 10:30\"},{\"period\":\"Διάλειμμα\",\"time\":\"10:30 - 10:45\"},{\"period\":\"5η\",\"time\":\"10:45 - 11:25\"},{\"period\":\"6η\",\"time\":\"11:25 - 12:05\"},{\"period\":\"Διάλειμμα\",\"time\":\"12:05 - 12:15\"},{\"period\":\"7η\",\"time\":\"12:15 - 12:55\"},{\"period\":\"8η\",\"time\":\"12:55 - 13:35\"}]},{\"title\":\"Τετάρτη - Παρασκευή (7ωρο)\",\"rows\":[{\"period\":\"1η\",\"time\":\"07:30 - 08:15\"},{\"period\":\"2η\",\"time\":\"08:15 - 09:00\"},{\"period\":\"Διάλειμμα\",\"time\":\"09:00 - 09:20\"},{\"period\":\"3η\",\"time\":\"09:20 - 10:05\"},{\"period\":\"4η\",\"time\":\"10:05 - 10:50\"},{\"period\":\"Διάλειμμα\",\"time\":\"10:50 - 11:10\"},{\"period\":\"5η\",\"time\":\"11:10 - 11:55\"},{\"period\":\"6η\",\"time\":\"11:55 - 12:40\"},{\"period\":\"Διάλειμμα\",\"time\":\"12:40 - 12:50\"},{\"period\":\"7η\",\"time\":\"12:50 - 13:35\"}]}]}', '2026-04-11 19:03:11'),
(4, 'board_section', 'Συνδεσμος Γωνεων', 'Στην ενότητα αυτή θα βρείτε τη σύνθεση του Διοικητικού Συμβουλίου του Συνδεσμου Γωνεων, βασικά στοιχεία επικοινωνίας και χρήσιμους συνδέσμους για άμεση ενημέρωση.', '{\"eyebrow\": \"Σχολική Χρονιά 2025 - 2026\", \"position_label\": \"Θέση\", \"name_label\": \"Ονοματεπώνυμο\", \"committee_label\": \"Μέλη\", \"board_members\": [{\"role\": \"ΠΡΟΕΔΡΟΣ\", \"name\": \"Μιχάλης Αριστείδου\"}, {\"role\": \"ΑΝΤΙΠΡΟΕΔΡΟΣ\", \"name\": \"Μάριος Γαβριηλίδης\"}, {\"role\": \"ΓΡΑΜΜΑΤΕΑΣ\", \"name\": \"Βάσια Μέζου\"}, {\"role\": \"ΒΟΗΘΟΣ ΓΡΑΜΜΑΤΕΑΣ\", \"name\": \"Σπυρούλα Χαραλάμπους\"}, {\"role\": \"ΤΑΜΙΑΣ\", \"name\": \"Γιάννα Παπαϊωάννου\"}, {\"role\": \"ΒΟΗΘΟΣ ΤΑΜΙΑΣ\", \"name\": \"Αρίστη Θεοδοσίου\"}], \"committee_members\": [\"Χαρά Χριστοδούλου\", \"Χρίστος Αριστοδήμου\", \"Πέτρος Κοντογιάννης\"], \"current_board_label\": \"Τρέχον Διοικητικό Συμβούλιο\", \"contact_email_label\": \"Email\", \"contact_email_value\": \"sg-gym-ag-athanasios-lem@schools.ac.cy\"}', '2026-04-11 19:03:11'),
(5, 'class_responsibles_section', 'Υπεύθυνοι Τμημάτων', 'ΥΠΕΥΘΥΝΟΙ ΤΜΗΜΑΤΩΝ ΚΑΙ ΥΠΕΥΘΥΝΟΙ ΒΟΗΘΟΙ ΔΙΕΥΘΥΝΤΕΣ', '{\"modal_title\":\"ΥΠΕΥΘΥΝΟΙ ΤΜΗΜΑΤΩΝ ΚΑΙ ΥΠΕΥΘΥΝΟΙ ΒΟΗΘΟΙ ΔΙΕΥΘΥΝΤΕΣ\",\"class_label\":\"ΤΜΗΜΑ\",\"responsible_label\":\"ΥΠΕΥΘΥΝΟΣ ΤΜΗΜΑΤΟΣ\",\"assistant_label\":\"ΥΠΕΥΘΥΝΟΣ ΒΟΗΘΟΣ ΔΙΕΥΘΥΝΤΗΣ\",\"room_label\":\"ΑΙΘΟΥΣΑ\",\"rows\":[{\"class\":\"Α1\",\"responsible\":\"ΑΛΕΞΑΝΔΡΟΣ ΚΟΥΝΤΟΥΡΙΩΤΗΣ\",\"assistant\":\"ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ\",\"room\":\"107\"},{\"class\":\"Α2\",\"responsible\":\"ΓΕΩΡΓΙΑ ΧΑΤΖΗΒΑΣΙΛΗ\",\"assistant\":\"ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ\",\"room\":\"103\"},{\"class\":\"Α3\",\"responsible\":\"ΧΡΙΣΤΙΝΑ ΡΗΓΑ\",\"assistant\":\"ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ\",\"room\":\"102\"},{\"class\":\"Α4\",\"responsible\":\"ΓΕΩΡΓΙΑ ΒΑΡΣΑΜΗ\",\"assistant\":\"ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ\",\"room\":\"108\"},{\"class\":\"Α5\",\"responsible\":\"ΧΡΙΣΤΙΝΑ ΚΑΜΕΝΟΥ\",\"assistant\":\"ΕΛΛΗ ΜΕΛΕΤΙΟΥ\",\"room\":\"109\"},{\"class\":\"Α6\",\"responsible\":\"ΑΝΤΖΕΛΑ ΣΟΥΑΝ\",\"assistant\":\"ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ\",\"room\":\"104\"},{\"class\":\"Α7\",\"responsible\":\"ΜΑΡΙΟΣ ΑΝΔΡΕΟΥ\",\"assistant\":\"ΠΑΝΑΓΙΩΤΗΣ ΠΑΝΤΕΛΙΔΗΣ\",\"room\":\"110\"},{\"class\":\"Α8\",\"responsible\":\"ΒΡΥΩΝΟΥΛΛΑ ΘΕΟΦΥΛΑΚΤΟΥ\",\"assistant\":\"ΠΑΝΑΓΙΩΤΑ ΚΩΝΣΤΑΝΤΙΝΟΥ ΚΑΤΖΗ\",\"room\":\"101\"},{\"class\":\"Α9\",\"responsible\":\"ΕΛΕΝΗ ΠΑΠΑΓΕΩΡΓΙΟΥ\",\"assistant\":\"ΔΕΣΠΩ ΛΟΦΙΤΟΥ (ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ)\",\"room\":\"201\"},{\"class\":\"ΑΕ1\",\"responsible\":\"—\",\"assistant\":\"ΚΥΡΙΑΚΗ ΠΑΠΑΝΙΚΟΛΑΟΥ / ΚΩΝΣΤΑΝΤΙΝΑ ΛΟΥΤΣΙΟΥ\",\"room\":\"ΑΙΘ. ΨΥΧΑΓΩΓΙΑΣ\"},{\"class\":\"Β1\",\"responsible\":\"ΧΑΡΗΣ ΣΙΑΚΑΛΛΗΣ\",\"assistant\":\"ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ\",\"room\":\"106\"},{\"class\":\"Β2\",\"responsible\":\"ΑΝΤΡΗ ΜΗΝΑ\",\"assistant\":\"ΔΕΣΠΩ ΛΟΦΙΤΟΥ (ΚΡΙΣΤΙΑ ΑΠΟΣΤΟΛΙΔΟΥ (Ανάθεση))\",\"room\":\"215\"},{\"class\":\"Β3\",\"responsible\":\"ΚΡΙΣΤΙΑ ΑΠΟΣΤΟΛΙΔΟΥ\",\"assistant\":\"ΔΕΣΠΩ ΛΟΦΙΤΟΥ (ΚΡΙΣΤΙΑ ΑΠΟΣΤΟΛΙΔΟΥ (Ανάθεση))\",\"room\":\"105\"},{\"class\":\"Β4\",\"responsible\":\"ΠΑΝΑΓΙΩΤΑ ΒΑΣΙΛΕΙΟΥ\",\"assistant\":\"ΕΛΛΗ ΜΕΛΕΤΙΟΥ\",\"room\":\"111\"},{\"class\":\"Β5\",\"responsible\":\"ΑΝΔΡΕΑΣ ΖΕΝΙΟΥ\",\"assistant\":\"ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ\",\"room\":\"202\"},{\"class\":\"Β6\",\"responsible\":\"ΜΑΡΙΑ ΙΩΑΝΝΟΥ\",\"assistant\":\"ΠΑΝΑΓΙΩΤΗΣ ΠΑΝΤΕΛΙΔΗΣ\",\"room\":\"ΑΙΘ. ΒΙΟΛΟΓΙΑΣ\"},{\"class\":\"Β7\",\"responsible\":\"ΗΛΙΑΝΑ ΛΟΪΖΙΔΟΥ\",\"assistant\":\"ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ\",\"room\":\"209\"},{\"class\":\"Β8\",\"responsible\":\"ΣΩΤΗΡΙΑ ΛΑΖΑΡΙΔΟΥ\",\"assistant\":\"ΠΑΝΑΓΙΩΤΑ ΚΩΝΣΤΑΝΤΙΝΟΥ ΚΑΤΖΗ\",\"room\":\"203\"},{\"class\":\"ΒΕ2\",\"responsible\":\"ΑΝΤΖΕΛΙΝΑ ΠΑΠΑΓΕΩΡΓΙΟΥ\",\"assistant\":\"ΚΩΝΣΤΑΝΤΙΝΑ ΛΟΥΤΣΙΟΥ\",\"room\":\"ΑΙΘ. ΨΥΧΑΓΩΓΙΑΣ\"},{\"class\":\"Γ1\",\"responsible\":\"ΣΩΤΗΡΙΑ ΘΕΜΙΣΤΟΚΛΕΟΥΣ\",\"assistant\":\"ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ\",\"room\":\"210\"},{\"class\":\"Γ2\",\"responsible\":\"ΚΩΝΣΤΑΝΤΙΑ ΚΚΙΜΗ\",\"assistant\":\"ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ\",\"room\":\"204\"},{\"class\":\"Γ3\",\"responsible\":\"ΘΕΟΦΑΝΗΣ ΚΕΝΤΡΩΤΗΣ\",\"assistant\":\"ΕΛΛΗ ΜΕΛΕΤΙΟΥ\",\"room\":\"205\"},{\"class\":\"Γ4\",\"responsible\":\"ΧΡΙΣΤΙΑΝΑ ΧΡΙΣΤΟΥ\",\"assistant\":\"ΚΩΝΣΤΑΝΤΙΝΑ ΛΟΥΤΣΙΟΥ\",\"room\":\"206\"},{\"class\":\"Γ5\",\"responsible\":\"ΜΑΡΙΑ ΟΙΚΟΝΟΜΟΥ\",\"assistant\":\"ΠΑΝΑΓΙΩΤΗΣ ΠΑΝΤΕΛΙΔΗΣ\",\"room\":\"208\"},{\"class\":\"Γ6\",\"responsible\":\"ΑΛΚΗΣ ΠΑΠΗΣ\",\"assistant\":\"ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ\",\"room\":\"212\"},{\"class\":\"Γ7\",\"responsible\":\"ΙΟΡΔΑΝΗΣ ΙΟΡΔΑΝΟΥ\",\"assistant\":\"ΠΑΝΑΓΙΩΤΑ ΚΩΝΣΤΑΝΤΙΝΟΥ ΚΑΤΖΗ\",\"room\":\"216\"}]}', '2026-04-11 19:03:11'),
(6, 'electronic_admin_section', 'Ηλεκτρονική Διοίκηση', 'ΗΛΕΚΤΡΟΝΙΚΗ ΔΙΟΙΚΗΣΗ', '{\"modal_title\":\"ΗΛΕΚΤΡΟΝΙΚΗ ΔΙΟΙΚΗΣΗ\",\"registration_heading\":\"Οδηγίες για εγγραφή στο Σύστημα Ηλεκτρονικής Διοίκησης\",\"registration_intro\":\"Αν επιθυμείτε να εγγραφείτε στο Σύστημα Ηλεκτρονικής Διοίκησης για παρακολούθηση του προγράμματος διαγωνισμάτων των παιδιών σας, ακολουθήστε τα πιο κάτω βήματα:\",\"registration_steps\":[\"Δηλώνετε ενδιαφέρον στο σχολείο για να σας δημιουργηθεί κωδικός επαλήθευσης, ο οποίος θα σας αποσταλεί με SMS ή θα δοθεί εκτυπωμένος στο παιδί σας.\",\"Αφού λάβετε τον κωδικό επαλήθευσης, επισκεφθείτε την ιστοσελίδα www.eschoolsupport.com.\",\"Συμπληρώνετε τα στοιχεία που θα σας ζητηθούν και στο τέλος εισάγετε τον κωδικό επαλήθευσης που σας δόθηκε από το σχολείο.\",\"Στο email που δηλώσατε θα αποσταλεί μήνυμα και θα πρέπει να επιλέξετε «Επιβεβαίωση email».\",\"Μετά την επιβεβαίωση, μπορείτε να προχωρήσετε με την είσοδό σας στο σύστημα.\"],\"login_heading\":\"Οδηγίες για την είσοδο στο Σύστημα Ηλεκτρονικής Διοίκησης\",\"login_steps\":[\"Αν κατά την είσοδο εμφανίζεται λάθος σύνδεσης, καθαρίστε το ιστορικό του browser σας (Clear Browser History). Αυτό χρειάζεται συνήθως μόνο την πρώτη φορά που θα παρουσιαστεί το πρόβλημα.\",\"Αν χρησιμοποιείτε Internet Explorer (Microsoft Edge), κάντε τα εξής:\",\"Αν χρησιμοποιείτε Google Chrome, κάντε τα εξής:\"],\"edge_heading\":\"Βήματα για Internet Explorer (Microsoft Edge)\",\"edge_steps\":[\"Επιλέξτε στο πάνω δεξί μέρος της οθόνης το εικονίδιο Tools.\",\"Επιλέξτε Internet Options.\",\"Στο Browsing History επιλέξτε Delete.\",\"Στο παράθυρο που ανοίγει βεβαιωθείτε ότι είναι επιλεγμένο το History και επιλέξτε Delete.\",\"Μετά επισκεφθείτε ξανά τον σύνδεσμο.\"],\"chrome_heading\":\"Βήματα για Google Chrome\",\"chrome_steps\":[\"Επιλέξτε το εικονίδιο με τις τρεις κάθετες τελείες.\",\"Επιλέξτε Settings.\",\"Στο κάτω μέρος επιλέξτε Advanced.\",\"Επιλέξτε Clear Browsing data.\",\"Επιλέξτε All time και ολοκληρώστε τη διαγραφή.\",\"Μετά επισκεφθείτε ξανά τον σύνδεσμο.\"],\"link_label\":\"Μετάβαση στο Σύστημα Ηλεκτρονικής Διοίκησης\",\"link_url\":\"http://www.gym-ag-athanasios-lem.eschoolsupport.com/\"}', '2026-04-11 19:03:11'),
(7, 'gallery_section', 'Φωτογραφικό Υλικό', '', '{\"empty_message\":\"Δεν έχουν προστεθεί ακόμη φωτογραφίες.\"}', '2026-04-11 19:03:11'),
(8, 'association_section', 'Συνδεσμος Γωνεων', 'Η ενότητα αυτή συγκεντρώνει τον χαιρετισμό, τον σκοπό και βασικά στοιχεία για τη δράση του Συνδεσμου Γωνεων.', '{\"eyebrow\":\"Συνεργασία Οικογένειας Και Σχολείου\",\"greeting_title\":\"Χαιρετισμός\",\"greeting_body\":\"Ο Σύνδεσμος Γονέων και Κηδεμόνων καλωσορίζει τις οικογένειες της σχολικής κοινότητας του Γυμνασίου Αγίου Αθανασίου.\nΣτόχος μας είναι η στενή συνεργασία με τη Διεύθυνση, το προσωπικό και τους γονείς, ώστε να στηρίζονται έμπρακτα οι μαθητές και οι δράσεις του σχολείου.\",\"purpose_title\":\"Σκοπός του Σ.Γ.\",\"purpose_body\":\"Ο Σύνδεσμος λειτουργεί υποστηρικτικά προς το σχολείο και επιδιώκει την ενίσχυση της επικοινωνίας ανάμεσα στους γονείς, τη σχολική μονάδα και τους μαθητές.\nΜέσα από δράσεις, ενημερώσεις και οργανωμένη συμμετοχή συμβάλλει στη βελτίωση της σχολικής ζωής και στην προώθηση πρωτοβουλιών που ωφελούν τα παιδιά.\",\"history_title\":\"Ιστορικό του Συνδέσμου\",\"history_body\":\"Ο Σύνδεσμος Γονέων και Κηδεμόνων δρα διαχρονικά ως βασικός πυλώνας συνεργασίας ανάμεσα στην οικογένεια και το σχολείο.\nΜε την ετήσια συμμετοχή των γονέων και τη στήριξη των μελών του, ενισχύει δράσεις, εκδηλώσεις και ανάγκες της σχολικής κοινότητας, διατηρώντας ενεργό ρόλο στην καθημερινότητα του σχολείου.\",\"contact_label\":\"Email Συνδέσμου\",\"contact_value\":\"sg-gym-ag-athanasios-lem@schools.ac.cy\"}', '2026-04-11 19:03:11'),
(9, 'board_archive_section', 'Συμβούλια ανά Σχολική Χρονιά', 'Αρχείο προηγούμενων και τρεχουσών συνθέσεων του Διοικητικού Συμβουλίου.', '{\"eyebrow\":\"Αρχείο Συμβουλίων\",\"year_label\":\"Σχολική Χρονιά\",\"position_label\":\"Θέση\",\"name_label\":\"Ονοματεπώνυμο\",\"rows\":[{\"year\":\"2025-2026\",\"role\":\"ΠΡΟΕΔΡΟΣ\",\"name\":\"Μιχάλης Αριστείδου\"},{\"year\":\"2025-2026\",\"role\":\"ΑΝΤΙΠΡΟΕΔΡΟΣ\",\"name\":\"Μάριος Γαβριηλίδης\"},{\"year\":\"2025-2026\",\"role\":\"ΓΡΑΜΜΑΤΕΑΣ\",\"name\":\"Βάσια Μέζου\"},{\"year\":\"2025-2026\",\"role\":\"ΒΟΗΘΟΣ ΓΡΑΜΜΑΤΕΑΣ\",\"name\":\"Σπυρούλα Χαραλάμπους\"},{\"year\":\"2025-2026\",\"role\":\"ΤΑΜΙΑΣ\",\"name\":\"Γιάννα Παπαϊωάννου\"},{\"year\":\"2025-2026\",\"role\":\"ΒΟΗΘΟΣ ΤΑΜΙΑΣ\",\"name\":\"Αρίστη Θεοδοσίου\"},{\"year\":\"2025-2026\",\"role\":\"ΜΕΛΟΣ\",\"name\":\"Χαρά Χριστοδούλου\"},{\"year\":\"2025-2026\",\"role\":\"ΜΕΛΟΣ\",\"name\":\"Χρίστος Αριστοδήμου\"},{\"year\":\"2025-2026\",\"role\":\"ΜΕΛΟΣ\",\"name\":\"Πέτρος Κοντογιάννης\"}]}', '2026-04-11 19:03:11');

--
-- Dumping data for table `Payments`
--

INSERT INTO `Payments` (`payment_id`, `user_id`, `amount`, `payment_date`, `payment_status`, `payment_type`, `transaction_id`) VALUES
(100, 2, 20.00, '2026-03-10 10:00:00', 'completed', 'membership', 'JCC_MEMB_001'),
(101, 2, 15.00, '2026-03-10 10:05:00', 'completed', 'insurance', 'JCC_INS_001'),
(102, 2, 15.00, '2026-04-13 20:27:08', 'pending', 'insurance', NULL);

--
-- Dumping data for table `PricingSettings`
--

INSERT INTO `PricingSettings` (`id`, `subscription_price`, `insurance_price`, `updated_at`) VALUES
(1, 20.00, 7.50, '2026-04-11 19:03:11');

--
-- Dumping data for table `Products`
--

INSERT INTO `Products` (`product_id`, `product_name`, `product_description`, `price`) VALUES
(1, 'School Hoodie', 'Blue hoodie with school logo', 25.00),
(2, 'Black School Trousers', 'Black school uniform trousers', 15.00),
(3, 'Notebook', 'A4 lined notebook', 3.50);

--
-- Dumping data for table `ProductsImages`
--

INSERT INTO `ProductsImages` (`pro_image_id`, `product_id`, `image_path`) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Products_img/tshirt.jpg'),
(2, 2, '/parents-council-platform-group5/public/assets/Products_img/product_69c4ffe67280e6.61595091.jpg'),
(3, 3, '/parents-council-platform-group5/public/assets/Products_img/default-product.svg');

--
-- Dumping data for table `Submissions`
--

INSERT INTO `Submissions` (`application_id`, `user_id`, `file_path`, `text_content`, `submission_data`, `submitted_at`, `sub_status`) VALUES
(1, 2, '/parents-council-platform-group5/public/assets/Submissions_docs/feedback.pdf', NULL, NULL, '2026-04-11 19:03:11', 'approved'),
(2, 3, '/parents-council-platform-group5/public/assets/Submissions_docs/questionnaire.pdf', NULL, NULL, '2026-04-11 19:03:11', 'waiting');

--
-- Dumping data for table `SystemSchedule`
--

INSERT INTO `SystemSchedule` (`ss_id`, `feature`, `start_date`, `end_date`, `ss_status`) VALUES
(1, 'registration', '2026-03-01 00:00:00', '2026-03-31 23:59:59', 'active'),
(2, 'purchase', '2026-03-01 00:00:00', '2026-03-31 23:59:59', 'active');

--
-- Dumping data for table `UsefulInformationSections`
--

INSERT INTO `UsefulInformationSections` (`section_id`, `section_key`, `section_title`, `section_subtitle`, `content_json`, `updated_at`) VALUES
(1, 'page_header', 'Χρήσιμες Πληροφορίες', 'Συγκεντρωμένες βασικές πληροφορίες για τη σχολική χρονιά, τις αργίες, τη στολή, την ασφάλεια και τα χρήσιμα έντυπα.', '{\"eyebrow\":\"Οδηγός Γονέων Και Μαθητών\"}', '2026-04-11 19:03:11'),
(2, 'quick_links', 'Γρήγοροι Σύνδεσμοι', 'Άμεση πρόσβαση στις πιο χρήσιμες επίσημες σελίδες.', '{\"items\":[{\"title\":\"Ιστοσελίδα Σχολείου\",\"description\":\"Η επίσημη ιστοσελίδα του Γυμνασίου Αγίου Αθανασίου.\",\"url\":\"https://gym-ag-athanasios-lem.schools.ac.cy/\",\"icon\":\"fas fa-school\"},{\"title\":\"Έντυπα & Εγγραφές\",\"description\":\"Σελίδα με χρήσιμα έντυπα εγγραφών, μετακινήσεων και ανακοινώσεων.\",\"url\":\"https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations\",\"icon\":\"fas fa-file-download\"},{\"title\":\"Έντυπα Ασφάλειας\",\"description\":\"Επίσημα έντυπα του ΥΠΑΝ για θέματα ασφάλειας και καταγραφής ατυχημάτων.\",\"url\":\"https://www.moec.gov.cy/politiki_amyna/ay_entypa.html\",\"icon\":\"fas fa-shield-alt\"}]}', '2026-04-11 19:03:11'),
(3, 'school_year', 'Σχολική Χρονιά 2025-2026', 'Βασικές ημερομηνίες για τα δημόσια γυμνάσια στην Κύπρο.', '{\"items\":[{\"label\":\"Έναρξη Α\' Τετραμήνου\",\"date\":\"5 Σεπτεμβρίου 2025\",\"description\":\"Έναρξη της σχολικής χρονιάς για τη Μέση Εκπαίδευση.\"},{\"label\":\"Λήξη Α\' Τετραμήνου\",\"date\":\"15 Ιανουαρίου 2026\",\"description\":\"Ολοκλήρωση του πρώτου τετραμήνου.\"},{\"label\":\"Β\' Τετράμηνο\",\"date\":\"16 Ιανουαρίου 2026\",\"description\":\"Συνεχίζεται μέχρι το τέλος των προαγωγικών εξετάσεων.\"}],\"note\":\"Η ακριβής τελευταία ημέρα φοίτησης εξαρτάται από το πρόγραμμα των προαγωγικών εξετάσεων και τις ανακοινώσεις της σχολικής μονάδας.\"}', '2026-04-11 19:03:11'),
(4, 'holidays', 'Επίσημες Αργίες', 'Οι βασικές σχολικές αργίες που ισχύουν για τα δημόσια γυμνάσια.', '{\"rows\":[{\"date\":\"1 Οκτωβρίου 2025\",\"name\":\"Ημέρα Ανεξαρτησίας της Κύπρου\"},{\"date\":\"28 Οκτωβρίου 2025\",\"name\":\"Εθνική Επέτειος\"},{\"date\":\"11 Δεκεμβρίου 2025\",\"name\":\"Ημέρα Εκπαιδευτικού\"},{\"date\":\"24 Δεκεμβρίου 2025 - 6 Ιανουαρίου 2026\",\"name\":\"Διακοπές Χριστουγέννων\"},{\"date\":\"30 Ιανουαρίου 2026\",\"name\":\"Τριών Ιεραρχών και Ελληνικών Γραμμάτων\"},{\"date\":\"10 Φεβρουαρίου 2026\",\"name\":\"Ημέρα Εκπαιδευτικού\"},{\"date\":\"23 Φεβρουαρίου 2026\",\"name\":\"Καθαρά Δευτέρα\"},{\"date\":\"25 Μαρτίου 2026\",\"name\":\"Εθνική Επέτειος\"},{\"date\":\"1 Απριλίου 2026\",\"name\":\"Εθνική Επέτειος ΕΟΚΑ\"},{\"date\":\"6 Απριλίου - 19 Απριλίου 2026\",\"name\":\"Διακοπές Πάσχα\"},{\"date\":\"23 Απριλίου 2026\",\"name\":\"Ονομαστήρια Αρχιεπισκόπου Κύπρου\"},{\"date\":\"1 Μαΐου 2026\",\"name\":\"Πρωτομαγιά\"},{\"date\":\"1 Ιουνίου 2026\",\"name\":\"Αγίου Πνεύματος\"},{\"date\":\"11 Ιουνίου 2026\",\"name\":\"Αποστόλου Βαρνάβα\"}]}', '2026-04-11 19:42:19'),
(5, 'safety', 'Ασφάλεια Παιδιών & Χρήσιμα Έντυπα', 'Χρήσιμη ενημέρωση για ασφάλεια στο σχολείο και επίσημες λήψεις εντύπων.', '{\"bullets\":[\"Για θέματα πρόληψης, ασφάλειας και υγείας στο σχολείο, αρμόδιο είναι το Γραφείο Πολιτικής Άμυνας, Ασφάλειας και Υγείας του ΥΠΑΝ.\",\"Σε περίπτωση περιστατικού ή ατυχήματος, η ενημέρωση της σχολικής μονάδας πρέπει να γίνεται άμεσα, ώστε να ακολουθηθεί η προβλεπόμενη διαδικασία.\",\"Για επίσημα έντυπα καταγραφής ατυχημάτων και άλλα σχετικά έγγραφα, χρησιμοποιείτε τα έντυπα του ΥΠΑΝ.\",\"Για ετήσιες ανακοινώσεις σχετικά με πιθανή ασφαλιστική κάλυψη μαθητών, οι γονείς θα πρέπει να παρακολουθούν τις ανακοινώσεις του σχολείου και του Συνδέσμου Γονέων.\"],\"downloads\":[{\"title\":\"Έντυπα Ασφάλειας και Καταγραφής Ατυχημάτων\",\"url\":\"https://www.moec.gov.cy/politiki_amyna/ay_entypa.html\",\"icon\":\"fas fa-download\"},{\"title\":\"Επιμορφωτικό Υλικό Ασφάλειας και Υγείας\",\"url\":\"https://www.moec.gov.cy/politiki_amyna/ay_epimorfotiko_yliko.html\",\"icon\":\"fas fa-book-open\"},{\"title\":\"Έντυπα και ανακοινώσεις του σχολείου\",\"url\":\"https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations\",\"icon\":\"fas fa-folder-open\"}]}', '2026-04-11 19:03:11'),
(6, 'uniform', 'Μαθητική Στολή', 'Συνοπτική παρουσίαση με βάση τους εσωτερικούς κανονισμούς του σχολείου.', '{\"cards\":[{\"title\":\"Αγόρια\",\"items\":[\"Γκρίζο παντελόνι\",\"Άσπρο πουκάμισο, T-shirt ή polo\",\"Μπλε σκούρο πουλόβερ\",\"Δεν επιτρέπονται jeans ή αθλητικές φόρμες στην καθημερινή στολή\"]},{\"title\":\"Κορίτσια\",\"items\":[\"Γκρίζα φούστα ή γκρίζο παντελόνι\",\"Άσπρο πουκάμισο, T-shirt ή polo\",\"Μπλε σκούρο πουλόβερ\",\"Δεν επιτρέπονται jeans ή κολάν στην καθημερινή στολή\"]},{\"title\":\"Στολή Γυμναστικής\",\"items\":[\"Μαύρο ή μπλε παντελόνι φόρμας\",\"Άσπρη, γκρίζα ή σχολική φανέλα\",\"Αθλητικά παπούτσια\",\"Πρακτική και ασφαλής ενδυμασία για το μάθημα Φυσικής Αγωγής\"]}],\"note\":\"Για τις πλήρεις λεπτομέρειες της στολής και των κανονισμών, δείτε τους επίσημους εσωτερικούς κανονισμούς του σχολείου.\",\"button_text\":\"Προβολή Κανονισμών\",\"button_url\":\"https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/september/esoterikoi-kanonismoi-2025-2026.pdf\"}', '2026-04-11 19:03:11');

COMMIT;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
