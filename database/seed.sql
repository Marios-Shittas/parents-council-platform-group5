USE parents_council;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

INSERT INTO Users (user_id, name, surname, email, password, phone_number, number_of_children, role, account_status, token, token_expiry) VALUES
(1, 'Admin', 'User', 'admin@test.com', 'admin', '1234567890', 0, 'admin', 'approved', NULL, NULL),
(2, 'John', 'Doe', 'parent1@test.com', 'parent1', '1112223333', 2, 'parent', 'approved', NULL, NULL),
(3, 'Jane', 'Smith', 'parent2@test.com', 'parent2', '4445556666', 1, 'parent', 'approved', NULL, NULL);

INSERT INTO Announcements (announcement_id, announcement_title, announcement_date, publish_date, announcement_description) VALUES
(37, 'Ενημέρωση για Εξετάσεις', '2026-03-11', '2026-03-11', 'Οι τελικές εξετάσεις θα ξεκινήσουν τον Ιούνιο. Παρακαλούνται οι μαθητές να προετοιμαστούν κατάλληλα.'),
(38, 'Νέο Ωρολόγιο Πρόγραμμα', '2026-03-14', '2026-03-05', 'Το νέο πρόγραμμα μαθημάτων θα ισχύει από τη Δευτέρα.'),
(39, 'Υπενθύμιση Εργασιών', '2026-03-11', '2026-03-11', 'Οι μαθητές πρέπει να παραδώσουν τις εργασίες τους μέχρι το τέλος της εβδομάδας.');

INSERT INTO Applications (application_id, application_title, application_description) VALUES
(1, 'Field Trip Permission', 'Form to allow your child to attend field trip'),
(2, 'Library Membership', 'Sign up for school library access');

INSERT INTO Events (event_id, event_title, event_description, event_date, publish_date) VALUES
(12, 'Σχολική Γιορτή', 'Μια μικρή γιορτή με μουσική και δραστηριότητες για τους μαθητές
Τοποθεσία: Σχολική αυλή
Συμμετοχή όλων των τάξεων', '2026-03-03 09:00:00', '2026-03-11'),
(13, 'Διαγωνισμός Πληροφορικής', 'Μαθητές θα συμμετάσχουν σε βασικές δραστηριότητες προγραμματισμού.
Τοποθεσία: Εργαστήριο Η/Υ
Συμμετοχή: Τάξεις Γυμνασίου', '2026-03-15 12:10:00', '2026-03-11'),
(14, 'Τουρνουά Ποδοσφαίρου', 'Φιλικοί αγώνες ποδοσφαίρου μεταξύ τάξεων
Τοποθεσία: Σχολικό γήπεδο

Ομάδες ανά τάξη', '2026-01-24 11:00:00', '2026-03-11'),
(15, 'Ημέρα Περιβάλλοντος', 'Δραστηριότητες καθαρισμού και ενημέρωσης για το περιβάλλον.
Τοποθεσία: Σχολικός χώρος

Συμμετοχή μαθητών και καθηγητών', '2026-03-18 17:00:00', '2026-03-11'),
(16, 'Έκθεση Τέχνης Μαθητών', 'Παρουσίαση έργων ζωγραφικής και κατασκευών των μαθητών.
Ημερομηνία: 3 Μαΐου

Τοποθεσία: Αίθουσα εκδηλώσεων

Συμμετοχή: Όλες οι τάξεις', '2026-05-03 10:00:00', '2026-03-11'),
(17, 'Σεμινάριο Σταδιοδρομίας', 'Παρουσίαση επαγγελματικών επιλογών για τους μαθητές.

15 Μαΐου

Τοποθεσία: Αίθουσα πολλαπλών χρήσεων

Ομιλητές: Επαγγελματίες από διάφορους κλάδους', '2026-05-15 16:00:00', '2026-03-11');

INSERT INTO Products (product_id, product_name, product_description, price) VALUES
(1, 'School Hoodie', 'Blue hoodie with school logo', 25.00),
(2, 'Notebook', 'A4 lined notebook', 3.50);

INSERT INTO SystemSchedule (ss_id, feature, start_date, end_date, ss_status) VALUES
(1, 'registration', '2026-03-01 00:00:00', '2026-03-31 23:59:59', 'active'),
(2, 'purchase', '2026-03-01 00:00:00', '2026-03-31 23:59:59', 'active');

INSERT INTO Orders (order_id, user_id, total_price, created_at, order_status) VALUES
(1, 2, 28.50, '2026-03-07 11:59:24', 'paid'),
(2, 3, 25.00, '2026-03-07 11:59:24', 'pending');

INSERT INTO Payments (payment_id, user_id, amount, payment_date, payment_status) VALUES
(1, 2, 28.50, '2026-03-07 11:59:24', 'completed'),
(2, 3, 25.00, '2026-03-07 11:59:24', 'failed');

INSERT INTO AnnouncementsImages (an_image_id, announcement_id, image_path) VALUES
(31, 37, '/parents-council-platform-group5/public/assets/Announcements_img/69b16b4e53d13_1773235022.png'),
(33, 38, '/parents-council-platform-group5/public/assets/Announcements_img/69b16b940919b_1773235092.jpeg'),
(34, 39, '/parents-council-platform-group5/public/assets/Announcements_img/69b16bd532d5e_1773235157.jpeg'),
(36, 38, '/parents-council-platform-group5/public/assets/Announcements_img/69b16c0566714_1773235205.jpg');

INSERT INTO ApplicationsDocuments (ap_document_id, application_id, file_path) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Applications_docs/feedback.pdf'),
(2, 2, '/parents-council-platform-group5/public/assets/Applications_docs/questionnaire.pdf');

INSERT INTO EventsImages (ev_image_id, event_id, image_path) VALUES
(14, 12, '/parents-council-platform-group5/public/assets/Events_img/69b16c93f0d2a_1773235347.jpeg'),
(15, 13, '/parents-council-platform-group5/public/assets/Events_img/69b16ce5186ba_1773235429.jpg'),
(16, 14, '/parents-council-platform-group5/public/assets/Events_img/69b16d57535a4_1773235543.jpeg'),
(17, 14, '/parents-council-platform-group5/public/assets/Events_img/69b16d5753d6d_1773235543.jpeg'),
(18, 15, '/parents-council-platform-group5/public/assets/Events_img/69b16d994fcd7_1773235609.png'),
(19, 15, '/parents-council-platform-group5/public/assets/Events_img/69b16d99502c3_1773235609.jpg'),
(20, 15, '/parents-council-platform-group5/public/assets/Events_img/69b16d995079d_1773235609.jpg'),
(21, 16, '/parents-council-platform-group5/public/assets/Events_img/69b16e10d2fd6_1773235728.jpg'),
(22, 17, '/parents-council-platform-group5/public/assets/Events_img/69b16e67cf009_1773235815.jpeg');

INSERT INTO Submissions (application_id, user_id, file_path, sub_status) VALUES
(1, 2, '/parents-council-platform-group5/public/assets/Submissions_docs/feedback.pdf', 'approved'),
(2, 3, '/parents-council-platform-group5/public/assets/Submissions_docs/questionnaire.pdf', 'waiting');

INSERT INTO OrderItems (order_id, product_id, price_at_purchase, quantity, size) VALUES
(1, 1, 25.00, 1, NULL),
(1, 2, 3.50, 1, NULL),
(2, 1, 25.00, 1, NULL);

INSERT INTO PaymentsDetails (payment_item_id, payment_id, product_id, quantity, price_at_purchase, size) VALUES
(1, 1, 1, 1, 25.00, NULL),
(2, 1, 2, 1, 3.50, NULL);

INSERT INTO ProductsImages (pro_image_id, product_id, image_path) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Products_img/tshirt.jpg'),
(2, 2, '/parents-council-platform-group5/public/assets/Products_img/jeans.jpg');

COMMIT;