-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 16, 2026 at 10:57 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

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

--
-- Dumping data for table `announcements`
--

INSERT INTO `announcements` (`announcement_id`, `announcement_title`, `announcement_date`, `publish_date`, `announcement_description`) VALUES
(37, 'Ενημέρωση για Εξετάσεις', '2026-03-11', '2026-03-11', 'Οι τελικές εξετάσεις θα ξεκινήσουν τον Ιούνιο. Παρακαλούνται οι μαθητές να προετοιμαστούν κατάλληλα.'),
(38, 'Νέο Ωρολόγιο Πρόγραμμα', '2026-03-14', '2026-03-05', 'Το νέο πρόγραμμα μαθημάτων θα ισχύει από τη Δευτέρα.'),
(39, 'Υπενθύμιση Εργασιών', '2026-03-11', '2026-03-11', 'Οι μαθητές πρέπει να παραδώσουν τις εργασίες τους μέχρι το τέλος της εβδομάδας.');

--
-- Dumping data for table `announcementsimages`
--

INSERT INTO `announcementsimages` (`an_image_id`, `announcement_id`, `image_path`) VALUES
(31, 37, '/parents-council-platform-group5/public/assets/Announcements_img/69b16b4e53d13_1773235022.png'),
(33, 38, '/parents-council-platform-group5/public/assets/Announcements_img/69b16b940919b_1773235092.jpeg'),
(34, 39, '/parents-council-platform-group5/public/assets/Announcements_img/69b16bd532d5e_1773235157.jpeg'),
(36, 38, '/parents-council-platform-group5/public/assets/Announcements_img/69b16c0566714_1773235205.jpg');

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `application_title`, `application_description`) VALUES
(6, 'Test', 'this is a test'),
(7, 'Library Membership', 'βιβλιοθηκη');

--
-- Dumping data for table `applicationsdocuments`
--

INSERT INTO `applicationsdocuments` (`ap_document_id`, `application_id`, `file_path`) VALUES
(6, 6, '/parents-council-platform-group5/public/assets/Applications_docs/application_6_application_image_69b6f02d4c2a32.05745885.png'),
(7, 6, '/parents-council-platform-group5/public/assets/Applications_docs/application_6_instruction_file_69b6f02d4c9c05.40781120.pdf'),
(8, 6, '/parents-council-platform-group5/public/assets/Applications_docs/application_6_required_documents_69b6f02d4cfdf8.12329636.jpeg'),
(9, 7, '/parents-council-platform-group5/public/assets/Applications_docs/application_7_application_image_69b6f7afe28012.12900352.png');

--
-- Dumping data for table `events`
--

INSERT INTO `events` (`event_id`, `event_title`, `event_description`, `event_date`, `publish_date`) VALUES
(12, 'Σχολική Γιορτή', 'Μια μικρή γιορτή με μουσική και δραστηριότητες για τους μαθητές\r\nΤοποθεσία: Σχολική αυλή\r\nΣυμμετοχή όλων των τάξεων', '2026-03-03 09:00:00', '2026-03-11'),
(13, 'Διαγωνισμός Πληροφορικής', 'Μαθητές θα συμμετάσχουν σε βασικές δραστηριότητες προγραμματισμού.\r\nΤοποθεσία: Εργαστήριο Η/Υ\r\nΣυμμετοχή: Τάξεις Γυμνασίου', '2026-03-15 12:10:00', '2026-03-11'),
(14, 'Τουρνουά Ποδοσφαίρου', 'Φιλικοί αγώνες ποδοσφαίρου μεταξύ τάξεων\r\nΤοποθεσία: Σχολικό γήπεδο\r\n\r\nΟμάδες ανά τάξη', '2026-01-24 11:00:00', '2026-03-11'),
(15, 'Ημέρα Περιβάλλοντος', 'Δραστηριότητες καθαρισμού και ενημέρωσης για το περιβάλλον.\r\nΤοποθεσία: Σχολικός χώρος\r\n\r\nΣυμμετοχή μαθητών και καθηγητών', '2026-03-18 17:00:00', '2026-03-11'),
(16, 'Έκθεση Τέχνης Μαθητών', 'Παρουσίαση έργων ζωγραφικής και κατασκευών των μαθητών.\r\nΗμερομηνία: 3 Μαΐου\r\n\r\nΤοποθεσία: Αίθουσα εκδηλώσεων\r\n\r\nΣυμμετοχή: Όλες οι τάξεις', '2026-05-03 10:00:00', '2026-03-11'),
(17, 'Σεμινάριο Σταδιοδρομίας', 'Παρουσίαση επαγγελματικών επιλογών για τους μαθητές.\r\n\r\n15 Μαΐου\r\n\r\nΤοποθεσία: Αίθουσα πολλαπλών χρήσεων\r\n\r\nΟμιλητές: Επαγγελματίες από διάφορους κλάδους', '2026-05-15 16:00:00', '2026-03-11');

--
-- Dumping data for table `eventsimages`
--

INSERT INTO `eventsimages` (`ev_image_id`, `event_id`, `image_path`) VALUES
(14, 12, '/parents-council-platform-group5/public/assets/Events_img/69b16c93f0d2a_1773235347.jpeg'),
(15, 13, '/parents-council-platform-group5/public/assets/Events_img/69b16ce5186ba_1773235429.jpg'),
(16, 14, '/parents-council-platform-group5/public/assets/Events_img/69b16d57535a4_1773235543.jpeg'),
(17, 14, '/parents-council-platform-group5/public/assets/Events_img/69b16d5753d6d_1773235543.jpeg'),
(18, 15, '/parents-council-platform-group5/public/assets/Events_img/69b16d994fcd7_1773235609.png'),
(19, 15, '/parents-council-platform-group5/public/assets/Events_img/69b16d99502c3_1773235609.jpg'),
(20, 15, '/parents-council-platform-group5/public/assets/Events_img/69b16d995079d_1773235609.jpg'),
(21, 16, '/parents-council-platform-group5/public/assets/Events_img/69b16e10d2fd6_1773235728.jpg'),
(22, 17, '/parents-council-platform-group5/public/assets/Events_img/69b16e67cf009_1773235815.jpeg');

--
-- Dumping data for table `orderitems`
--

INSERT INTO `orderitems` (`order_id`, `product_id`, `price_at_purchase`, `quantity`, `size`) VALUES
(1, 1, 25.00, 1, NULL),
(1, 2, 3.50, 1, NULL),
(2, 1, 25.00, 1, NULL);

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`order_id`, `user_id`, `total_price`, `created_at`, `order_status`) VALUES
(1, 2, 28.50, '2026-03-07 11:59:24', 'paid'),
(2, 3, 25.00, '2026-03-07 11:59:24', 'pending');

--
-- Dumping data for table `payments`
--

INSERT INTO `payments` (`payment_id`, `user_id`, `amount`, `payment_date`, `payment_status`) VALUES
(1, 2, 28.50, '2026-03-07 11:59:24', 'completed'),
(2, 3, 25.00, '2026-03-07 11:59:24', 'failed');

--
-- Dumping data for table `paymentsdetails`
--

INSERT INTO `paymentsdetails` (`payment_item_id`, `payment_id`, `product_id`, `quantity`, `price_at_purchase`, `size`) VALUES
(1, 1, 1, 1, 25.00, NULL),
(2, 1, 2, 1, 3.50, NULL);

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`product_id`, `product_name`, `product_description`, `price`) VALUES
(1, 'School Hoodie', 'Blue hoodie with school logo', 25.00),
(2, 'Notebook', 'A4 lined notebook', 3.50);

--
-- Dumping data for table `productsimages`
--

INSERT INTO `productsimages` (`pro_image_id`, `product_id`, `image_path`) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Products_img/tshirt.jpg'),
(2, 2, '/parents-council-platform-group5/public/assets/Products_img/jeans.jpg');

--
-- Dumping data for table `systemschedule`
--

INSERT INTO `systemschedule` (`ss_id`, `feature`, `start_date`, `end_date`, `ss_status`) VALUES
(1, 'registration', '2026-03-01 00:00:00', '2026-03-31 23:59:59', 'active'),
(2, 'purchase', '2026-03-01 00:00:00', '2026-03-31 23:59:59', 'active');

--
-- Dumping data for table `usefulinformationsections`
--

INSERT INTO `usefulinformationsections` (`section_id`, `section_key`, `section_title`, `section_subtitle`, `content_json`, `updated_at`) VALUES
(1, 'page_header', 'Χρήσιμες Πληροφορίες', 'Συγκεντρωμένες βασικές πληροφορίες για τη σχολική χρονιά, τις αργίες, τη στολή, την ασφάλεια και τα χρήσιμα έντυπα.', '{\"eyebrow\":\"Οδηγός Γονέων Και Μαθητών\"}', '2026-03-16 09:03:54'),
(2, 'quick_links', 'Γρήγοροι Σύνδεσμοι', 'Άμεση πρόσβαση στις πιο χρήσιμες επίσημες σελίδες.', '{\"items\":[{\"title\":\"Ιστοσελίδα Σχολείου\",\"description\":\"Η επίσημη ιστοσελίδα του Γυμνασίου Αγίου Αθανασίου.\",\"url\":\"https://gym-ag-athanasios-lem.schools.ac.cy/\",\"icon\":\"fas fa-school\"},{\"title\":\"Έντυπα & Εγγραφές\",\"description\":\"Σελίδα με χρήσιμα έντυπα εγγραφών, μετακινήσεων και ανακοινώσεων.\",\"url\":\"https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations\",\"icon\":\"fas fa-file-download\"},{\"title\":\"Έντυπα Ασφάλειας\",\"description\":\"Επίσημα έντυπα του ΥΠΑΝ για θέματα ασφάλειας και καταγραφής ατυχημάτων.\",\"url\":\"https://www.moec.gov.cy/politiki_amyna/ay_entypa.html\",\"icon\":\"fas fa-shield-alt\"}]}', '2026-03-16 09:03:54'),
(3, 'school_year', 'Σχολική Χρονιά 2025-2026', 'Βασικές ημερομηνίες για τα δημόσια γυμνάσια στην Κύπρο.', '{\"items\":[{\"label\":\"Έναρξη Α\' Τετραμήνου\",\"date\":\"5 Σεπτεμβρίου 2025\",\"description\":\"Έναρξη της σχολικής χρονιάς για τη Μέση Εκπαίδευση.\"},{\"label\":\"Λήξη Α\' Τετραμήνου\",\"date\":\"15 Ιανουαρίου 2026\",\"description\":\"Ολοκλήρωση του πρώτου τετραμήνου.\"},{\"label\":\"Β\' Τετράμηνο\",\"date\":\"16 Ιανουαρίου 2026\",\"description\":\"Συνεχίζεται μέχρι το τέλος των προαγωγικών εξετάσεων.\"}],\"note\":\"Η ακριβής τελευταία ημέρα φοίτησης εξαρτάται από το πρόγραμμα των προαγωγικών εξετάσεων και τις ανακοινώσεις της σχολικής μονάδας.\"}', '2026-03-16 09:03:54'),
(4, 'holidays', 'Επίσημες Αργίες', 'Οι βασικές σχολικές αργίες που ισχύουν για τα δημόσια γυμνάσια.', '{\"rows\":[{\"date\":\"1 Οκτωβρίου 2025\",\"name\":\"Ημέρα Ανεξαρτησίας της Κύπρου\"},{\"date\":\"28 Οκτωβρίου 2025\",\"name\":\"Εθνική Επέτειος\"},{\"date\":\"11 Δεκεμβρίου 2025\",\"name\":\"Ημέρα Εκπαιδευτικού\"},{\"date\":\"24 Δεκεμβρίου 2025 - 6 Ιανουαρίου 2026\",\"name\":\"Διακοπές Χριστουγέννων\"},{\"date\":\"30 Ιανουαρίου 2026\",\"name\":\"Τριών Ιεραρχών και Ελληνικών Γραμμάτων\"},{\"date\":\"10 Φεβρουαρίου 2026\",\"name\":\"Ημέρα Εκπαιδευτικού\"},{\"date\":\"23 Φεβρουαρίου 2026\",\"name\":\"Καθαρά Δευτέρα\"},{\"date\":\"25 Μαρτίου 2026\",\"name\":\"Εθνική Επέτειος\"},{\"date\":\"1 Απριλίου 2026\",\"name\":\"Εθνική Επέτειος ΕΟΚΑ\"},{\"date\":\"6 Απριλίου - 19 Απριλίου 2026\",\"name\":\"Διακοπές Πάσχα\"},{\"date\":\"23 Απριλίου 2026\",\"name\":\"Ονομαστήρια Αρχιεπισκόπου Κύπρου\"},{\"date\":\"1 Μαΐου 2026\",\"name\":\"Πρωτομαγιά\"},{\"date\":\"1 Ιουνίου 2026\",\"name\":\"Αγίου Πνεύματος\"},{\"date\":\"11 Ιουνίου 2026\",\"name\":\"Αποστόλου Βαρνάβα\"}]}', '2026-03-16 09:03:54'),
(5, 'safety', 'Ασφάλεια Παιδιών & Χρήσιμα Έντυπα', 'Χρήσιμη ενημέρωση για ασφάλεια στο σχολείο και επίσημες λήψεις εντύπων.', '{\"bullets\":[\"Για θέματα πρόληψης, ασφάλειας και υγείας στο σχολείο, αρμόδιο είναι το Γραφείο Πολιτικής Άμυνας, Ασφάλειας και Υγείας του ΥΠΑΝ.\",\"Σε περίπτωση περιστατικού ή ατυχήματος, η ενημέρωση της σχολικής μονάδας πρέπει να γίνεται άμεσα, ώστε να ακολουθηθεί η προβλεπόμενη διαδικασία.\",\"Για επίσημα έντυπα καταγραφής ατυχημάτων και άλλα σχετικά έγγραφα, χρησιμοποιείτε τα έντυπα του ΥΠΑΝ.\",\"Για ετήσιες ανακοινώσεις σχετικά με πιθανή ασφαλιστική κάλυψη μαθητών, οι γονείς θα πρέπει να παρακολουθούν τις ανακοινώσεις του σχολείου και του Συνδέσμου Γονέων.\"],\"downloads\":[{\"title\":\"Έντυπα Ασφάλειας και Καταγραφής Ατυχημάτων\",\"url\":\"https://www.moec.gov.cy/politiki_amyna/ay_entypa.html\",\"icon\":\"fas fa-download\"},{\"title\":\"Επιμορφωτικό Υλικό Ασφάλειας και Υγείας\",\"url\":\"https://www.moec.gov.cy/politiki_amyna/ay_epimorfotiko_yliko.html\",\"icon\":\"fas fa-book-open\"},{\"title\":\"Έντυπα και ανακοινώσεις του σχολείου\",\"url\":\"https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations\",\"icon\":\"fas fa-folder-open\"}]}', '2026-03-16 09:03:54'),
(6, 'uniform', 'Μαθητική Στολή', 'Συνοπτική παρουσίαση με βάση τους εσωτερικούς κανονισμούς του σχολείου.', '{\"cards\":[{\"title\":\"Αγόρια\",\"items\":[\"Γκρίζο παντελόνι\",\"Άσπρο πουκάμισο, T-shirt ή polo\",\"Μπλε σκούρο πουλόβερ\",\"Δεν επιτρέπονται jeans ή αθλητικές φόρμες στην καθημερινή στολή\"]},{\"title\":\"Κορίτσια\",\"items\":[\"Γκρίζα φούστα ή γκρίζο παντελόνι\",\"Άσπρο πουκάμισο, T-shirt ή polo\",\"Μπλε σκούρο πουλόβερ\",\"Δεν επιτρέπονται jeans ή κολάν στην καθημερινή στολή\"]},{\"title\":\"Στολή Γυμναστικής\",\"items\":[\"Μαύρο ή μπλε παντελόνι φόρμας\",\"Άσπρη, γκρίζα ή σχολική φανέλα\",\"Αθλητικά παπούτσια\",\"Πρακτική και ασφαλής ενδυμασία για το μάθημα Φυσικής Αγωγής\"]}],\"note\":\"Για τις πλήρεις λεπτομέρειες της στολής και των κανονισμών, δείτε τους επίσημους εσωτερικούς κανονισμούς του σχολείου.\",\"button_text\":\"Προβολή Κανονισμών\",\"button_url\":\"https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/september/esoterikoi-kanonismoi-2025-2026.pdf\"}', '2026-03-16 09:03:54');

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `name`, `surname`, `email`, `password`, `phone_number`, `number_of_children`, `role`, `account_status`, `token`, `token_expiry`) VALUES
(1, 'Admin', 'User', 'admin@test.com', 'admin', '1234567890', 0, 'admin', 'approved', NULL, NULL),
(2, 'John', 'Doe', 'parent1@test.com', 'parent1', '1112223333', 2, 'parent', 'approved', NULL, NULL),
(3, 'Jane', 'Smith', 'parent2@test.com', 'parent2', '4445556666', 1, 'parent', 'approved', NULL, NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
