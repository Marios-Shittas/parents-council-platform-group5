USE parents_council;

-- USERS
INSERT INTO Users (name, surname, email, password, phone_number, number_of_children, role, account_status)
VALUES
('Admin', 'User', 'admin@test.com', 'admin', '1234567890', 0, 'admin', 'approved'),
('John', 'Doe', 'parent1@test.com', 'parent1', '1112223333', 2, 'parent', 'approved'),
('Jane', 'Smith', 'parent2@test.com', 'parent2', '4445556666', 1, 'parent', 'approved');

-- ANNOUNCEMENTS
INSERT INTO Announcements (announcement_title, publish_date, announcement_description)
VALUES
('School Closed', '2026-03-05', 'The school will be closed for national holiday.'),
('Parent Meeting', '2026-03-07', 'Monthly parent council meeting at 6 PM.');

-- ANNOUNCEMENTS IMAGES
INSERT INTO AnnouncementsImages (announcement_id, image_path)
VALUES
(1, '/parents-council-platform-group5/public/assets/Announcements_img/church.jpg'),
(2, '/parents-council-platform-group5/public/assets/Announcements_img/orchistra.jpg');

-- EVENTS
INSERT INTO Events (event_title, event_description, event_date, publish_date)
VALUES
('Science Fair', 'Annual school science fair', '2026-04-10 09:00:00', '2026-03-01'),
('Sports Day', 'Inter-class sports competitions', '2026-04-20 10:00:00', '2026-03-02');

-- EVENTS IMAGES
INSERT INTO EventsImages (event_id, image_path)
VALUES
(1, '/parents-council-platform-group5/public/assets/Events_img/books.jpg'),
(2, '/parents-council-platform-group5/public/assets/Events_img/parastasi.jpg');

-- POSTS
INSERT INTO Posts (event_id, post_title, post_content)
VALUES
(1, 'Science Fair Winners', 'Congratulations to all participants!'),
(2, 'Sports Day Results', 'Class B2 wins the relay race!');

-- POSTS IMAGES
INSERT INTO PostsImages (post_id, image_path)
VALUES
(1, '/parents-council-platform-group5/public/assets/Posts_img/parelasi.jpg'),
(2, '/parents-council-platform-group5/public/assets/Posts_img/dancers.jpg');

-- APPLICATIONS
INSERT INTO Applications (application_title, application_description, submission_type)
VALUES
('Field Trip Permission', 'Form to allow your child to attend field trip', 'file'),
('Library Membership', 'Sign up for school library access - fill in your child''s details below', 'text');

-- APPLICATIONS DOCUMENTS
INSERT INTO ApplicationsDocuments (application_id, file_path)
VALUES
(1, '/parents-council-platform-group5/public/assets/Applications_docs/feedback.pdf'),
(2, '/parents-council-platform-group5/public/assets/Applications_docs/questionnaire.pdf');

-- SUBMISSIONS
INSERT INTO Submissions (application_id, user_id, file_path, text_content, sub_status)
VALUES
(1, 2, '/parents-council-platform-group5/public/assets/Submissions_docs/feedback.pdf', NULL, 'approved'),
(2, 3, NULL, 'Child name: Alice Smith, Class: B2, Age: 8', 'waiting');

-- PRODUCTS
INSERT INTO Products (product_name, product_description, price)
VALUES
('School Hoodie', 'Blue hoodie with school logo', 25.00),
('Notebook', 'A4 lined notebook', 3.50);

-- PRODUCTS IMAGES
INSERT INTO ProductsImages (product_id, image_path)
VALUES
(1, '/parents-council-platform-group5/public/assets/Products_img/tshirt.jpg'),
(2, '/parents-council-platform-group5/public/assets/Products_img/jeans.jpg');

-- ORDERS
INSERT INTO Orders (user_id, total_price, order_status)
VALUES
(2, 28.50, 'paid'),
(3, 25.00, 'pending');

-- ORDER ITEMS
INSERT INTO OrderItems (order_id, product_id, price_at_purchase, quantity)
VALUES
(1, 1, 25.00, 1),
(1, 2, 3.50, 1),
(2, 1, 25.00, 1);

-- PAYMENTS
INSERT INTO Payments (user_id, amount, payment_status)
VALUES
(2, 28.50, 'completed'),
(3, 25.00, 'pending');

-- PAYMENTS DETAILS
INSERT INTO PaymentsDetails (payment_id, product_id, quantity, price_at_purchase)
VALUES
(1, 1, 1, 25.00),
(1, 2, 1, 3.50);

-- SYSTEM SCHEDULE
INSERT INTO SystemSchedule (feature, start_date, end_date, ss_status)
VALUES
('registration', '2026-03-01 00:00:00', '2026-03-31 23:59:59', 'active'),
('purchase', '2026-03-01 00:00:00', '2026-03-31 23:59:59', 'active');