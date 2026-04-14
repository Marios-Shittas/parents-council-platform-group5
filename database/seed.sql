USE parents_council;

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

INSERT INTO Users (user_id, name, surname, email, password, phone_number, number_of_children, role, account_status, token, token_expiry) VALUES
(1, 'Admin', 'User', 'admin@test.com', '$2y$10$iEzB1rYBGWURYZJnbpPg/ulK0GD/tDI/6ktzuY7hmTfQnUCOmzDxe', '+35799123456', 0, 'admin', 'active', NULL, NULL),
(2, 'John', 'Doe', 'parent1@test.com', '$2y$10$5MryI34DorxxyDm1IoDtiuit5ek4dsK14UR6rBWn8ce7LSzeRVHYW', '+35799112233', 2, 'parent', 'active', NULL, NULL),
(3, 'Jane', 'Smith', 'parent2@test.com', '$2y$10$dITemBxHXfD1VqAQTMCxnOZ7jU1ibL7u.GiNng2snsRgQ339MZNYi', '+35799445566', 1, 'parent', 'waiting_payment', NULL, NULL);

INSERT INTO Children (child_id, user_id, name, surname, date_of_birth, school_class) VALUES
(1, 2, 'Chris', 'Doe', '2015-05-10', '5A'),
(2, 2, 'Anna', 'Doe', '2017-09-22', '3B'),
(3, 3, 'Mike', 'Smith', '2016-02-11', '4A');

INSERT INTO Announcements (announcement_id, announcement_title, announcement_date, publish_date, announcement_description, gdpr_notice) VALUES
(37, 'Ενημέρωση για Εξετάσεις', '2026-03-11', '2026-03-11', 'Οι τελικές εξετάσεις θα ξεκινήσουν τον Ιούνιο. Παρακαλούνται οι μαθητές να προετοιμαστούν κατάλληλα.', 'Το φωτογραφικό υλικό και τα συνημμένα έγγραφα των ανακοινώσεων δημοσιεύονται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με την πολιτική προστασίας δεδομένων του σχολείου και τις σχετικές εγκρίσεις που ισχύουν.'),
(38, 'Νέο Ωρολόγιο Πρόγραμμα', '2026-03-14', '2026-03-05', 'Το νέο πρόγραμμα μαθημάτων θα ισχύει από τη Δευτέρα.', 'Το φωτογραφικό υλικό και τα συνημμένα έγγραφα των ανακοινώσεων δημοσιεύονται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με την πολιτική προστασίας δεδομένων του σχολείου και τις σχετικές εγκρίσεις που ισχύουν.'),
(39, 'Υπενθύμιση Εργασιών', '2026-03-11', '2026-03-11', 'Οι μαθητές πρέπει να παραδώσουν τις εργασίες τους μέχρι το τέλος της εβδομάδας.', 'Το φωτογραφικό υλικό και τα συνημμένα έγγραφα των ανακοινώσεων δημοσιεύονται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με την πολιτική προστασίας δεδομένων του σχολείου και τις σχετικές εγκρίσεις που ισχύουν.');

INSERT INTO Applications (
    application_id,
    template_id,
    application_title,
    title,
    application_description,
    description,
    submission_type,
    academic_year,
    open_date,
    due_date,
    status,
    allow_online_submission,
    allow_file_submission,
    require_signature,
    form_schema,
    target_audience,
    created_by
) VALUES
(1, NULL, 'Field Trip Permission', 'Field Trip Permission', 'Form to allow your child to attend field trip', 'Form to allow your child to attend field trip', 'file', '2025-2026', '2026-03-01', '2026-06-15', 'published', 1, 1, 0, NULL, NULL, 1),
(2, NULL, 'Library Membership', 'Library Membership', 'Sign up for school library access', 'Sign up for school library access', 'file', '2025-2026', '2026-03-01', '2026-06-30', 'published', 1, 1, 0, NULL, NULL, 1);

INSERT INTO Events (event_id, event_title, event_description, gdpr_notice, event_date, publish_date) VALUES
(1, 'Τόμπολα & Μουσική Βραδιά', 'Παρασκευή 15 Μαΐου 2026
Ώρα: 20:00 - 23:00', 'Το φωτογραφικό υλικό της εκδήλωσης δημοσιεύεται με σεβασμό στα προσωπικά δεδομένα και σύμφωνα με τις ισχύουσες εγκρίσεις/πολιτικές του σχολείου.', '2026-05-15 20:00:00', '2026-04-14');

INSERT INTO Products (product_id, product_name, product_description, price) VALUES
(1, 'School Hoodie', 'Blue hoodie with school logo', 25.00),
(2, 'Black School Trousers', 'Black school uniform trousers', 15.00),
(3, 'Notebook', 'A4 lined notebook', 3.50);

INSERT INTO SystemSchedule (ss_id, feature, start_date, end_date, ss_status) VALUES
(1, 'registration', '2026-03-01 00:00:00', '2026-03-31 23:59:59', 'active'),
(2, 'purchase', '2026-03-01 00:00:00', '2026-03-31 23:59:59', 'active');

INSERT INTO PricingSettings (id, subscription_price, insurance_price) VALUES
(1, 20.00, 7.50);

-- Intentionally no seeded Orders / OrderItems so demo products can be deleted from admin after a fresh import.

INSERT INTO Payments (payment_id, user_id, amount, payment_date, payment_status, payment_type, transaction_id) VALUES
(100, 2, 20.00, '2026-03-10 10:00:00', 'completed', 'membership', 'JCC_MEMB_001'),
(101, 2, 15.00, '2026-03-10 10:05:00', 'completed', 'insurance', 'JCC_INS_001');
INSERT INTO AnnouncementsImages (an_image_id, announcement_id, image_path) VALUES
(31, 37, '/parents-council-platform-group5/public/assets/Announcements_img/69b16b4e53d13_1773235022.png'),
(33, 38, '/parents-council-platform-group5/public/assets/Announcements_img/69b16b940919b_1773235092.jpeg'),
(34, 39, '/parents-council-platform-group5/public/assets/Announcements_img/69b16bd532d5e_1773235157.jpeg'),
(36, 38, '/parents-council-platform-group5/public/assets/Announcements_img/69b16c0566714_1773235205.jpg');

INSERT INTO UsefulInformationSections (section_id, section_key, section_title, section_subtitle, content_json) VALUES
(1, 'page_header', 'Χρήσιμοι Σύνδεσμοι & Πληροφορίες', 'Συγκεντρωμένες βασικές πληροφορίες για τη σχολική χρονιά, τις αργίες, τη στολή, την ασφάλεια και τα χρήσιμα έντυπα.', '{"eyebrow":"Οδηγός Γονέων Και Μαθητών"}'),
(2, 'quick_links', 'Γρήγοροι Σύνδεσμοι', 'Άμεση πρόσβαση στις πιο χρήσιμες επίσημες σελίδες.', '{"items":[{"title":"Ιστοσελίδα Σχολείου","description":"Η επίσημη ιστοσελίδα του Γυμνασίου Αγίου Αθανασίου.","url":"https://gym-ag-athanasios-lem.schools.ac.cy/","icon":"fas fa-school"},{"title":"Έντυπα & Εγγραφές","description":"Σελίδα με χρήσιμα έντυπα εγγραφών, μετακινήσεων και ανακοινώσεων.","url":"https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations","icon":"fas fa-file-download"},{"title":"Εκπαιδευτικοί Σύνδεσμοι","description":"Επίσημα έντυπα του ΥΠΑΝ για θέματα ασφάλειας και καταγραφής ατυχημάτων.","url":"https://www.moec.gov.cy/politiki_amyna/ay_entypa.html","icon":"fas fa-shield-alt"}]}'),
(3, 'school_year', 'Σχολική Χρονιά 2025-2026', 'Βασικές ημερομηνίες για τα δημόσια γυμνάσια στην Κύπρο.', '{"items":[{"label":"Έναρξη Α'' Τετραμήνου","date":"5 Σεπτεμβρίου 2025","description":"Έναρξη της σχολικής χρονιάς για τη Μέση Εκπαίδευση."},{"label":"Λήξη Α'' Τετραμήνου","date":"15 Ιανουαρίου 2026","description":"Ολοκλήρωση του πρώτου τετραμήνου."},{"label":"Β'' Τετράμηνο","date":"16 Ιανουαρίου 2026","description":"Συνεχίζεται μέχρι το τέλος των προαγωγικών εξετάσεων."}],"note":"Η ακριβής τελευταία ημέρα φοίτησης εξαρτάται από το πρόγραμμα των προαγωγικών εξετάσεων και τις ανακοινώσεις της σχολικής μονάδας."}'),
(4, 'holidays', 'Επίσημες Αργίες', 'Οι βασικές σχολικές αργίες που ισχύουν για τα δημόσια γυμνάσια.', '{"rows":[{"date":"1 Οκτωβρίου 2025","name":"Ημέρα Ανεξαρτησίας της Κύπρου"},{"date":"28 Οκτωβρίου 2025","name":"Εθνική Επέτειος"},{"date":"11 Δεκεμβρίου 2025","name":"Ημέρα Εκπαιδευτικού"},{"date":"24 Δεκεμβρίου 2025 - 6 Ιανουαρίου 2026","name":"Διακοπές Χριστουγέννων"},{"date":"30 Ιανουαρίου 2026","name":"Τριών Ιεραρχών και Ελληνικών Γραμμάτων"},{"date":"10 Φεβρουαρίου 2026","name":"Ημέρα Εκπαιδευτικού"},{"date":"23 Φεβρουαρίου 2026","name":"Καθαρά Δευτέρα"},{"date":"25 Μαρτίου 2026","name":"Εθνική Επέτειος"},{"date":"1 Απριλίου 2026","name":"Εθνική Επέτειος ΕΟΚΑ"},{"date":"6 Απριλίου - 19 Απριλίου 2026","name":"Διακοπές Πάσχα"},{"date":"23 Απριλίου 2026","name":"Ονομαστήρια Αρχιεπισκόπου Κύπρου"},{"date":"1 Μαΐου 2026","name":"Πρωτομαγιά"},{"date":"1 Ιουνίου 2026","name":"Αγίου Πνεύματος"},{"date":"11 Ιουνίου 2026","name":"Αποστόλου Βαρνάβα"}]}'),
(5, 'safety', 'Ασφάλεια Παιδιών & Χρήσιμα Έντυπα', 'Χρήσιμη ενημέρωση για ασφάλεια στο σχολείο και επίσημες λήψεις εντύπων.', '{"bullets":["Για θέματα πρόληψης, ασφάλειας και υγείας στο σχολείο, αρμόδιο είναι το Γραφείο Πολιτικής Άμυνας, Ασφάλειας και Υγείας του ΥΠΑΝ.","Σε περίπτωση περιστατικού ή ατυχήματος, η ενημέρωση της σχολικής μονάδας πρέπει να γίνεται άμεσα, ώστε να ακολουθηθεί η προβλεπόμενη διαδικασία.","Για επίσημα έντυπα καταγραφής ατυχημάτων και άλλα σχετικά έγγραφα, χρησιμοποιείτε τα έντυπα του ΥΠΑΝ.","Για ετήσιες ανακοινώσεις σχετικά με πιθανή ασφαλιστική κάλυψη μαθητών, οι γονείς θα πρέπει να παρακολουθούν τις ανακοινώσεις του σχολείου και του Συνδέσμου Γονέων."],"downloads":[{"title":"Έντυπα Ασφάλειας και Καταγραφής Ατυχημάτων","url":"https://www.moec.gov.cy/politiki_amyna/ay_entypa.html","icon":"fas fa-download"},{"title":"Επιμορφωτικό Υλικό Ασφάλειας και Υγείας","url":"https://www.moec.gov.cy/politiki_amyna/ay_epimorfotiko_yliko.html","icon":"fas fa-book-open"},{"title":"Έντυπα και ανακοινώσεις του σχολείου","url":"https://gym-ag-athanasios-lem.schools.ac.cy/index.php?id=student-registrations","icon":"fas fa-folder-open"}]}'),
(6, 'uniform', 'Μαθητική Στολή', 'Συνοπτική παρουσίαση με βάση τους εσωτερικούς κανονισμούς του σχολείου.', '{"cards":[{"title":"Αγόρια","items":["Γκρίζο παντελόνι","Άσπρο πουκάμισο, T-shirt ή polo","Μπλε σκούρο πουλόβερ","Δεν επιτρέπονται jeans ή αθλητικές φόρμες στην καθημερινή στολή"]},{"title":"Κορίτσια","items":["Γκρίζα φούστα ή γκρίζο παντελόνι","Άσπρο πουκάμισο, T-shirt ή polo","Μπλε σκούρο πουλόβερ","Δεν επιτρέπονται jeans ή κολάν στην καθημερινή στολή"]},{"title":"Στολή Γυμναστικής","items":["Μαύρο ή μπλε παντελόνι φόρμας","Άσπρη, γκρίζα ή σχολική φανέλα","Αθλητικά παπούτσια","Πρακτική και ασφαλής ενδυμασία για το μάθημα Φυσικής Αγωγής"]}],"note":"Για τις πλήρεις λεπτομέρειες της στολής και των κανονισμών, δείτε τους επίσημους εσωτερικούς κανονισμούς του σχολείου.","button_text":"Προβολή Κανονισμών","button_url":"https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/september/esoterikoi-kanonismoi-2025-2026.pdf"}');

INSERT INTO EpikoinoniaPageSections (section_id, section_key, section_title, section_subtitle, content_json) VALUES
(1, 'page_header', 'Επικοινωνία', 'Επικοινωνήστε μαζί μας για οποιαδήποτε ερώτηση ή πληροφορία.', '{"eyebrow":"Υποστήριξη Και Στοιχεία","icon":"fas fa-envelope"}'),
(2, 'contact_info', 'Πληροφορίες Επικοινωνίας', 'Βρείτε τη διεύθυνση, τα τηλέφωνα, το email και το ωράριο της σχολικής μονάδας.', '{"cards":[{"title":"Διεύθυνση","text":"Χρίστου Παπαδούρη 50\\n4105 Άγιος Αθανάσιος, Λεμεσός","icon":"fas fa-map-marker-alt","link_label":"","link_url":""},{"title":"Τηλέφωνο","text":"Τηλέφωνα: 25694750, 25694752\\nΤηλεομοιότυπο: 25694755","icon":"fas fa-phone","link_label":"","link_url":""},{"title":"Email","text":"","icon":"fas fa-envelope","link_label":"gym-ag-athanasios-lem@schools.ac.cy","link_url":"mailto:gym-ag-athanasios-lem@schools.ac.cy"},{"title":"Ώρες Λειτουργίας","text":"Δευ-Παρ - 7.30-13.35","icon":"fas fa-clock","link_label":"","link_url":""}]}'),
(3, 'map_section', 'Βρείτε μας στο Χάρτη', 'Η τοποθεσία της σχολικής μονάδας στο Google Maps.', '{"embed_url":"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3279.4575341666614!2d33.0611131!3d34.7188599!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14e734bc13013dc9%3A0x9c01ea2ef75a5b4d!2zzpPPhc68zr3OrM-DzrnOvyDOkc6zzq_Ov8-FIM6RzrjOsc69zrHPg86vzr_PhQ!5e0!3m2!1sel!2s!4v1773496500123!5m2!1sel!2s"}'),
(4, 'form_section', 'Στείλτε μας Μήνυμα', 'Συμπληρώστε τη φόρμα και θα επικοινωνήσουμε μαζί σας το συντομότερο δυνατό.', '{"description":"","button_text":"Αποστολή Μηνύματος","success_message":"Το μήνυμά σας λήφθηκε. Θα σας απαντήσουμε το συντομότερο δυνατό."}'),
(5, 'social_section', 'Βρείτε μας στα social networks', 'Ακολουθήστε τις επίσημες σελίδες μας για νέα και ενημερώσεις.', '{"items":[{"title":"Facebook","url":"https://www.facebook.com/profile.php?id=100085835704152","icon":"fab fa-facebook-f"},{"title":"X","url":"https://x.com/cymoec","icon":"fab fa-twitter"},{"title":"YouTube","url":"https://www.youtube.com/cymoec","icon":"fab fa-youtube"}]}');

INSERT INTO ParentsPageSections (section_id, section_key, section_title, section_subtitle, content_json) VALUES
(1, 'page_header', 'Γονείς', 'Χρήσιμες πληροφορίες και στοιχεία επικοινωνίας για τον Σύνδεσμο Γονέων και Κηδεμόνων.', '{"public_eyebrow":"Δημόσια Πύλη","parent_eyebrow":"Χώρος Γονέα","icon":"fas fa-users"}'),
(2, 'history_section', 'Σύντομα για το Γυμνάσιο Αγίου Αθανασίου', '', '{"eyebrow":"Ιστορικό Σχολείου","items":["Το σχολείο άρχισε τη λειτουργία του τον Σεπτέμβριο του 1999 και από το 2000-2001 λειτουργούν και οι τρεις τάξεις.","Φέρει το όνομα του Αγίου Αθανασίου και εξυπηρετεί μαθητές από τον Δήμο και πολλές κοινότητες της ευρύτερης περιοχής Λεμεσού.","Φοιτούν επίσης μαθητές από πολλές χώρες, ενώ από τη σχολική χρονιά 2024-2025 λειτουργεί και τμήμα μαθητών με μεταναστευτική βιογραφία.","Διαθέτει πλήρεις κτηριακές και εργαστηριακές εγκαταστάσεις (εργαστήρια, βιβλιοθήκη, αίθουσες ειδικοτήτων και αθλητικούς χώρους).","Οι εκπαιδευτικοί υλοποιούν δράσεις και προγράμματα (όπως Erasmus+) με στόχο την καλλιέργεια δημοκρατικής και κριτικής σκέψης."]}'),
(3, 'schedule_section', 'Εσωτερικοί Κανονισμοί - Ωράριο', '', '{"eyebrow":"Σχολική Χρονιά 2025 - 2026","period_label":"Περίοδος","time_label":"Ώρα","blocks":[{"title":"Δευτέρα - Τρίτη - Πέμπτη (8ωρο)","rows":[{"period":"1η","time":"07:30 - 08:10"},{"period":"2η","time":"08:10 - 08:50"},{"period":"Διάλειμμα","time":"08:50 - 09:10"},{"period":"3η","time":"09:10 - 09:50"},{"period":"4η","time":"09:50 - 10:30"},{"period":"Διάλειμμα","time":"10:30 - 10:45"},{"period":"5η","time":"10:45 - 11:25"},{"period":"6η","time":"11:25 - 12:05"},{"period":"Διάλειμμα","time":"12:05 - 12:15"},{"period":"7η","time":"12:15 - 12:55"},{"period":"8η","time":"12:55 - 13:35"}]},{"title":"Τετάρτη - Παρασκευή (7ωρο)","rows":[{"period":"1η","time":"07:30 - 08:15"},{"period":"2η","time":"08:15 - 09:00"},{"period":"Διάλειμμα","time":"09:00 - 09:20"},{"period":"3η","time":"09:20 - 10:05"},{"period":"4η","time":"10:05 - 10:50"},{"period":"Διάλειμμα","time":"10:50 - 11:10"},{"period":"5η","time":"11:10 - 11:55"},{"period":"6η","time":"11:55 - 12:40"},{"period":"Διάλειμμα","time":"12:40 - 12:50"},{"period":"7η","time":"12:50 - 13:35"}]}]}'),
(4, 'board_section', 'Σύνδεσμος Γονέων και Κηδεμόνων', 'Στην ενότητα αυτή θα βρείτε τη σύνθεση του Διοικητικού Συμβουλίου του Συνδέσμου Γονέων, βασικά στοιχεία επικοινωνίας και χρήσιμους συνδέσμους για άμεση ενημέρωση.', '{"eyebrow":"Σχολική Χρονιά 2025 - 2026","position_label":"Θέση","name_label":"Ονοματεπώνυμο","committee_label":"Μέλη","board_members":[{"role":"ΠΡΟΕΔΡΟΣ","name":"Μιχάλης Αριστείδου"},{"role":"ΑΝΤΙΠΡΟΕΔΡΟΣ","name":"Μάριος Γαβριηλίδης"},{"role":"ΓΡΑΜΜΑΤΕΑΣ","name":"Βάσια Μέζου"},{"role":"ΒΟΗΘΟΣ ΓΡΑΜΜΑΤΕΑΣ","name":"Σπυρούλα Χαραλάμπους"},{"role":"ΤΑΜΙΑΣ","name":"Γιάννα Παπαϊωάννου"},{"role":"ΒΟΗΘΟΣ ΤΑΜΙΑΣ","name":"Αρίστη Θεοδοσίου"}],"committee_members":["Χαρά Χριστοδούλου","Χρίστος Αριστοδήμου","Πέτρος Κοντογιάννης"]}'),
(5, 'class_responsibles_section', 'Υπεύθυνοι Τμημάτων', 'ΥΠΕΥΘΥΝΟΙ ΤΜΗΜΑΤΩΝ ΚΑΙ ΥΠΕΥΘΥΝΟΙ ΒΟΗΘΟΙ ΔΙΕΥΘΥΝΤΕΣ', '{"modal_title":"ΥΠΕΥΘΥΝΟΙ ΤΜΗΜΑΤΩΝ ΚΑΙ ΥΠΕΥΘΥΝΟΙ ΒΟΗΘΟΙ ΔΙΕΥΘΥΝΤΕΣ","class_label":"ΤΜΗΜΑ","responsible_label":"ΥΠΕΥΘΥΝΟΣ ΤΜΗΜΑΤΟΣ","assistant_label":"ΥΠΕΥΘΥΝΟΣ ΒΟΗΘΟΣ ΔΙΕΥΘΥΝΤΗΣ","room_label":"ΑΙΘΟΥΣΑ","rows":[{"class":"Α1","responsible":"ΑΛΕΞΑΝΔΡΟΣ ΚΟΥΝΤΟΥΡΙΩΤΗΣ","assistant":"ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ","room":"107"},{"class":"Α2","responsible":"ΓΕΩΡΓΙΑ ΧΑΤΖΗΒΑΣΙΛΗ","assistant":"ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ","room":"103"},{"class":"Α3","responsible":"ΧΡΙΣΤΙΝΑ ΡΗΓΑ","assistant":"ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ","room":"102"},{"class":"Α4","responsible":"ΓΕΩΡΓΙΑ ΒΑΡΣΑΜΗ","assistant":"ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ","room":"108"},{"class":"Α5","responsible":"ΧΡΙΣΤΙΝΑ ΚΑΜΕΝΟΥ","assistant":"ΕΛΛΗ ΜΕΛΕΤΙΟΥ","room":"109"},{"class":"Α6","responsible":"ΑΝΤΖΕΛΑ ΣΟΥΑΝ","assistant":"ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ","room":"104"},{"class":"Α7","responsible":"ΜΑΡΙΟΣ ΑΝΔΡΕΟΥ","assistant":"ΠΑΝΑΓΙΩΤΗΣ ΠΑΝΤΕΛΙΔΗΣ","room":"110"},{"class":"Α8","responsible":"ΒΡΥΩΝΟΥΛΛΑ ΘΕΟΦΥΛΑΚΤΟΥ","assistant":"ΠΑΝΑΓΙΩΤΑ ΚΩΝΣΤΑΝΤΙΝΟΥ ΚΑΤΖΗ","room":"101"},{"class":"Α9","responsible":"ΕΛΕΝΗ ΠΑΠΑΓΕΩΡΓΙΟΥ","assistant":"ΔΕΣΠΩ ΛΟΦΙΤΟΥ (ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ)","room":"201"},{"class":"ΑΕ1","responsible":"—","assistant":"ΚΥΡΙΑΚΗ ΠΑΠΑΝΙΚΟΛΑΟΥ / ΚΩΝΣΤΑΝΤΙΝΑ ΛΟΥΤΣΙΟΥ","room":"ΑΙΘ. ΨΥΧΑΓΩΓΙΑΣ"},{"class":"Β1","responsible":"ΧΑΡΗΣ ΣΙΑΚΑΛΛΗΣ","assistant":"ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ","room":"106"},{"class":"Β2","responsible":"ΑΝΤΡΗ ΜΗΝΑ","assistant":"ΔΕΣΠΩ ΛΟΦΙΤΟΥ (ΚΡΙΣΤΙΑ ΑΠΟΣΤΟΛΙΔΟΥ (Ανάθεση))","room":"215"},{"class":"Β3","responsible":"ΚΡΙΣΤΙΑ ΑΠΟΣΤΟΛΙΔΟΥ","assistant":"ΔΕΣΠΩ ΛΟΦΙΤΟΥ (ΚΡΙΣΤΙΑ ΑΠΟΣΤΟΛΙΔΟΥ (Ανάθεση))","room":"105"},{"class":"Β4","responsible":"ΠΑΝΑΓΙΩΤΑ ΒΑΣΙΛΕΙΟΥ","assistant":"ΕΛΛΗ ΜΕΛΕΤΙΟΥ","room":"111"},{"class":"Β5","responsible":"ΑΝΔΡΕΑΣ ΖΕΝΙΟΥ","assistant":"ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ","room":"202"},{"class":"Β6","responsible":"ΜΑΡΙΑ ΙΩΑΝΝΟΥ","assistant":"ΠΑΝΑΓΙΩΤΗΣ ΠΑΝΤΕΛΙΔΗΣ","room":"ΑΙΘ. ΒΙΟΛΟΓΙΑΣ"},{"class":"Β7","responsible":"ΗΛΙΑΝΑ ΛΟΪΖΙΔΟΥ","assistant":"ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ","room":"209"},{"class":"Β8","responsible":"ΣΩΤΗΡΙΑ ΛΑΖΑΡΙΔΟΥ","assistant":"ΠΑΝΑΓΙΩΤΑ ΚΩΝΣΤΑΝΤΙΝΟΥ ΚΑΤΖΗ","room":"203"},{"class":"ΒΕ2","responsible":"ΑΝΤΖΕΛΙΝΑ ΠΑΠΑΓΕΩΡΓΙΟΥ","assistant":"ΚΩΝΣΤΑΝΤΙΝΑ ΛΟΥΤΣΙΟΥ","room":"ΑΙΘ. ΨΥΧΑΓΩΓΙΑΣ"},{"class":"Γ1","responsible":"ΣΩΤΗΡΙΑ ΘΕΜΙΣΤΟΚΛΕΟΥΣ","assistant":"ΓΙΩΡΓΟΣ ΓΕΩΡΓΙΟΥ","room":"210"},{"class":"Γ2","responsible":"ΚΩΝΣΤΑΝΤΙΑ ΚΚΙΜΗ","assistant":"ΒΑΣΙΛΙΚΗ ΚΑΠΠΑΗ","room":"204"},{"class":"Γ3","responsible":"ΘΕΟΦΑΝΗΣ ΚΕΝΤΡΩΤΗΣ","assistant":"ΕΛΛΗ ΜΕΛΕΤΙΟΥ","room":"205"},{"class":"Γ4","responsible":"ΧΡΙΣΤΙΑΝΑ ΧΡΙΣΤΟΥ","assistant":"ΚΩΝΣΤΑΝΤΙΝΑ ΛΟΥΤΣΙΟΥ","room":"206"},{"class":"Γ5","responsible":"ΜΑΡΙΑ ΟΙΚΟΝΟΜΟΥ","assistant":"ΠΑΝΑΓΙΩΤΗΣ ΠΑΝΤΕΛΙΔΗΣ","room":"208"},{"class":"Γ6","responsible":"ΑΛΚΗΣ ΠΑΠΗΣ","assistant":"ΚΩΝΣΤΑΝΤΙΝΟΣ ΑΝΤΩΝΙΟΥ","room":"212"},{"class":"Γ7","responsible":"ΙΟΡΔΑΝΗΣ ΙΟΡΔΑΝΟΥ","assistant":"ΠΑΝΑΓΙΩΤΑ ΚΩΝΣΤΑΝΤΙΝΟΥ ΚΑΤΖΗ","room":"216"}]}'),
(6, 'electronic_admin_section', 'Ηλεκτρονική Διοίκηση', 'ΗΛΕΚΤΡΟΝΙΚΗ ΔΙΟΙΚΗΣΗ', '{"modal_title":"ΗΛΕΚΤΡΟΝΙΚΗ ΔΙΟΙΚΗΣΗ","registration_heading":"Οδηγίες για εγγραφή στο Σύστημα Ηλεκτρονικής Διοίκησης","registration_intro":"Αν επιθυμείτε να εγγραφείτε στο Σύστημα Ηλεκτρονικής Διοίκησης για παρακολούθηση του προγράμματος διαγωνισμάτων των παιδιών σας, ακολουθήστε τα πιο κάτω βήματα:","registration_steps":["Δηλώνετε ενδιαφέρον στο σχολείο για να σας δημιουργηθεί κωδικός επαλήθευσης, ο οποίος θα σας αποσταλεί με SMS ή θα δοθεί εκτυπωμένος στο παιδί σας.","Αφού λάβετε τον κωδικό επαλήθευσης, επισκεφθείτε την ιστοσελίδα www.eschoolsupport.com.","Συμπληρώνετε τα στοιχεία που θα σας ζητηθούν και στο τέλος εισάγετε τον κωδικό επαλήθευσης που σας δόθηκε από το σχολείο.","Στο email που δηλώσατε θα αποσταλεί μήνυμα και θα πρέπει να επιλέξετε «Επιβεβαίωση email».","Μετά την επιβεβαίωση, μπορείτε να προχωρήσετε με την είσοδό σας στο σύστημα."],"login_heading":"Οδηγίες για την είσοδο στο Σύστημα Ηλεκτρονικής Διοίκησης","login_steps":["Αν κατά την είσοδο εμφανίζεται λάθος σύνδεσης, καθαρίστε το ιστορικό του browser σας (Clear Browser History). Αυτό χρειάζεται συνήθως μόνο την πρώτη φορά που θα παρουσιαστεί το πρόβλημα.","Αν χρησιμοποιείτε Internet Explorer (Microsoft Edge), κάντε τα εξής:","Αν χρησιμοποιείτε Google Chrome, κάντε τα εξής:"],"edge_heading":"Βήματα για Internet Explorer (Microsoft Edge)","edge_steps":["Επιλέξτε στο πάνω δεξί μέρος της οθόνης το εικονίδιο Tools.","Επιλέξτε Internet Options.","Στο Browsing History επιλέξτε Delete.","Στο παράθυρο που ανοίγει βεβαιωθείτε ότι είναι επιλεγμένο το History και επιλέξτε Delete.","Μετά επισκεφθείτε ξανά τον σύνδεσμο."],"chrome_heading":"Βήματα για Google Chrome","chrome_steps":["Επιλέξτε το εικονίδιο με τις τρεις κάθετες τελείες.","Επιλέξτε Settings.","Στο κάτω μέρος επιλέξτε Advanced.","Επιλέξτε Clear Browsing data.","Επιλέξτε All time και ολοκληρώστε τη διαγραφή.","Μετά επισκεφθείτε ξανά τον σύνδεσμο."],"link_label":"Μετάβαση στο Σύστημα Ηλεκτρονικής Διοίκησης","link_url":"http://www.gym-ag-athanasios-lem.eschoolsupport.com/"}'),
(7, 'gallery_section', 'Φωτογραφικό Υλικό', '', '{"empty_message":"Δεν έχουν προστεθεί ακόμη φωτογραφίες."}');

UPDATE ParentsPageSections
SET section_title = 'Συνδεσμος Γωνεων',
    section_subtitle = 'Χρήσιμες πληροφορίες και στοιχεία επικοινωνίας για τον Συνδεσμο Γωνεων.'
WHERE section_key = 'page_header';

UPDATE ParentsPageSections
SET section_title = 'Συνδεσμος Γωνεων',
    section_subtitle = 'Στην ενότητα αυτή θα βρείτε τη σύνθεση του Διοικητικού Συμβουλίου του Συνδεσμου Γωνεων, βασικά στοιχεία επικοινωνίας και χρήσιμους συνδέσμους για άμεση ενημέρωση.',
    content_json = JSON_SET(
        content_json,
        '$.current_board_label', 'Τρέχον Διοικητικό Συμβούλιο',
        '$.contact_email_label', 'Email',
        '$.contact_email_value', 'sg-gym-ag-athanasios-lem@schools.ac.cy'
    )
WHERE section_key = 'board_section';

INSERT INTO ParentsPageSections (section_id, section_key, section_title, section_subtitle, content_json) VALUES
(8, 'association_section', 'Συνδεσμος Γωνεων', 'Η ενότητα αυτή συγκεντρώνει τον χαιρετισμό, τον σκοπό και βασικά στοιχεία για τη δράση του Συνδεσμου Γωνεων.', '{"eyebrow":"Συνεργασία Οικογένειας Και Σχολείου","greeting_title":"Χαιρετισμός","greeting_body":"Ο Σύνδεσμος Γονέων και Κηδεμόνων καλωσορίζει τις οικογένειες της σχολικής κοινότητας του Γυμνασίου Αγίου Αθανασίου.\nΣτόχος μας είναι η στενή συνεργασία με τη Διεύθυνση, το προσωπικό και τους γονείς, ώστε να στηρίζονται έμπρακτα οι μαθητές και οι δράσεις του σχολείου.","purpose_title":"Σκοπός του Σ.Γ.","purpose_body":"Ο Σύνδεσμος λειτουργεί υποστηρικτικά προς το σχολείο και επιδιώκει την ενίσχυση της επικοινωνίας ανάμεσα στους γονείς, τη σχολική μονάδα και τους μαθητές.\nΜέσα από δράσεις, ενημερώσεις και οργανωμένη συμμετοχή συμβάλλει στη βελτίωση της σχολικής ζωής και στην προώθηση πρωτοβουλιών που ωφελούν τα παιδιά.","history_title":"Ιστορικό του Συνδέσμου","history_body":"Ο Σύνδεσμος Γονέων και Κηδεμόνων δρα διαχρονικά ως βασικός πυλώνας συνεργασίας ανάμεσα στην οικογένεια και το σχολείο.\nΜε την ετήσια συμμετοχή των γονέων και τη στήριξη των μελών του, ενισχύει δράσεις, εκδηλώσεις και ανάγκες της σχολικής κοινότητας, διατηρώντας ενεργό ρόλο στην καθημερινότητα του σχολείου.","contact_label":"Email Συνδέσμου","contact_value":"sg-gym-ag-athanasios-lem@schools.ac.cy"}'),
(9, 'board_archive_section', 'Συμβούλια ανά Σχολική Χρονιά', 'Αρχείο προηγούμενων και τρεχουσών συνθέσεων του Διοικητικού Συμβουλίου.', '{"eyebrow":"Αρχείο Συμβουλίων","year_label":"Σχολική Χρονιά","position_label":"Θέση","name_label":"Ονοματεπώνυμο","rows":[{"year":"2025-2026","role":"ΠΡΟΕΔΡΟΣ","name":"Μιχάλης Αριστείδου"},{"year":"2025-2026","role":"ΑΝΤΙΠΡΟΕΔΡΟΣ","name":"Μάριος Γαβριηλίδης"},{"year":"2025-2026","role":"ΓΡΑΜΜΑΤΕΑΣ","name":"Βάσια Μέζου"},{"year":"2025-2026","role":"ΒΟΗΘΟΣ ΓΡΑΜΜΑΤΕΑΣ","name":"Σπυρούλα Χαραλάμπους"},{"year":"2025-2026","role":"ΤΑΜΙΑΣ","name":"Γιάννα Παπαϊωάννου"},{"year":"2025-2026","role":"ΒΟΗΘΟΣ ΤΑΜΙΑΣ","name":"Αρίστη Θεοδοσίου"},{"year":"2025-2026","role":"ΜΕΛΟΣ","name":"Χαρά Χριστοδούλου"},{"year":"2025-2026","role":"ΜΕΛΟΣ","name":"Χρίστος Αριστοδήμου"},{"year":"2025-2026","role":"ΜΕΛΟΣ","name":"Πέτρος Κοντογιάννης"}]}');

INSERT INTO ParentsPageGalleryImages (image_id, full_image_path, thumb_image_path, alt_text, sort_order) VALUES
(1, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/7/3.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/7/i18npic.C240x240.3.jpg', 'Φωτογραφικό υλικό σχολείου', 1),
(2, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/6/2.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/6/i18npic.C240x240.2.jpg', 'Φωτογραφικό υλικό σχολείου', 2),
(3, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/5/21.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/5/i18npic.C240x240.21.jpg', 'Φωτογραφικό υλικό σχολείου', 3),
(4, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/8.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/i18npic.C240x240.8.jpg', 'Φωτογραφικό υλικό σχολείου', 4),
(5, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/1/1.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/1/i18npic.C240x240.1.jpg', 'Φωτογραφικό υλικό σχολείου', 5),
(6, 'https://gym-ag-athanasios-lem.schools.ac.cy/data/uploads/documents/2025-2026/march/1/2.jpg', 'https://gym-ag-athanasios-lem.schools.ac.cy/data/thumbs/documents/2025-2026/march/1/i18npic.C240x240.2.jpg', 'Φωτογραφικό υλικό σχολείου', 6);

INSERT INTO ApplicationsDocuments (ap_document_id, application_id, file_path) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Applications_docs/feedback.pdf'),
(2, 2, '/parents-council-platform-group5/public/assets/Applications_docs/questionnaire.pdf');

INSERT INTO EventsImages (ev_image_id, event_id, image_path) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Events_img/d99679d4-0f91-406b-adee-8d1590850f82.jpg');

INSERT INTO Submissions (application_id, user_id, file_path, sub_status) VALUES
(1, 2, '/parents-council-platform-group5/public/assets/Submissions_docs/feedback.pdf', 'approved'),
(2, 3, '/parents-council-platform-group5/public/assets/Submissions_docs/questionnaire.pdf', 'waiting');

-- Intentionally no seeded PaymentsDetails for products so Products remain deletable in a fresh database.

INSERT INTO ProductsImages (pro_image_id, product_id, image_path) VALUES
(1, 1, '/parents-council-platform-group5/public/assets/Products_img/tshirt.jpg'),
(2, 2, '/parents-council-platform-group5/public/assets/Products_img/product_69c4ffe67280e6.61595091.jpg'),
(3, 3, '/parents-council-platform-group5/public/assets/Products_img/default-product.svg');

COMMIT;
