CREATE DATABASE IF NOT EXISTS parents_council
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE parents_council;

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS PaymentsDetails;
DROP TABLE IF EXISTS Payments;
DROP TABLE IF EXISTS OrderItems;
DROP TABLE IF EXISTS Orders;
DROP TABLE IF EXISTS ProductsImages;
DROP TABLE IF EXISTS Products;
DROP TABLE IF EXISTS Submissions;
DROP TABLE IF EXISTS ApplicationsDocuments;
DROP TABLE IF EXISTS Applications;
DROP TABLE IF EXISTS PostsImages;
DROP TABLE IF EXISTS Posts;
DROP TABLE IF EXISTS EventsImages;
DROP TABLE IF EXISTS Events;
DROP TABLE IF EXISTS AnnouncementsImages;
DROP TABLE IF EXISTS Announcements;
DROP TABLE IF EXISTS SystemSchedule;
DROP TABLE IF EXISTS Users;
DROP TABLE IF EXISTS Children;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS Users(
    user_id              INT NOT NULL AUTO_INCREMENT,
    name                 VARCHAR(100) NOT NULL,
    surname              VARCHAR(100) NOT NULL,
    email                VARCHAR(150) NOT NULL UNIQUE,
    password             VARCHAR(255) NOT NULL,
    phone_number         VARCHAR(20) DEFAULT NULL,
    role                 ENUM('parent','admin') NOT NULL DEFAULT 'parent',
    account_status       ENUM('pending','approved','rejected','waiting_payment','active') NOT NULL DEFAULT 'pending',
    token                VARCHAR(255) DEFAULT NULL,
    token_expiry         DATETIME DEFAULT NULL,
    created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Children(
    child_id            INT NOT NULL AUTO_INCREMENT,
    user_id             INT NOT NULL,
    name                VARCHAR(100) NOT NULL,
    surname             VARCHAR(100) NOT NULL,
    date_of_birth       DATE NOT NULL,
    school_class        VARCHAR(20) NOT NULL,
    PRIMARY KEY (child_id),
    CONSTRAINT fk_child_user
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Announcements(
    announcement_id          INT NOT NULL AUTO_INCREMENT,
    announcement_title       VARCHAR(255) NOT NULL,
    announcement_date        DATE DEFAULT NULL,
    publish_date             DATE NOT NULL,
    announcement_description TEXT DEFAULT NULL,
    PRIMARY KEY (announcement_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS AnnouncementsImages(
    an_image_id       INT NOT NULL AUTO_INCREMENT,
    announcement_id   INT NOT NULL,
    image_path        VARCHAR(255) NOT NULL,
    PRIMARY KEY (an_image_id),
    CONSTRAINT fk_an_img
        FOREIGN KEY (announcement_id) REFERENCES Announcements(announcement_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Events(
    event_id          INT NOT NULL AUTO_INCREMENT,
    event_title       VARCHAR(255) NOT NULL,
    event_description TEXT DEFAULT NULL,
    event_date        DATETIME NOT NULL,
    publish_date      DATE NOT NULL,
    PRIMARY KEY (event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS EventsImages(
    ev_image_id       INT NOT NULL AUTO_INCREMENT,
    event_id          INT NOT NULL,
    image_path        VARCHAR(255) NOT NULL,
    PRIMARY KEY (ev_image_id),
    CONSTRAINT fk_ev_img
        FOREIGN KEY (event_id) REFERENCES Events(event_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Posts(
    post_id           INT NOT NULL AUTO_INCREMENT,
    event_id          INT NOT NULL,
    post_title        VARCHAR(255) NOT NULL,
    post_content      TEXT DEFAULT NULL,
    PRIMARY KEY (post_id),
    CONSTRAINT fk_post_event
        FOREIGN KEY (event_id) REFERENCES Events(event_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS PostsImages(
    post_image_id     INT NOT NULL AUTO_INCREMENT,
    post_id           INT NOT NULL,
    image_path        VARCHAR(255) NOT NULL,
    PRIMARY KEY (post_image_id),
    CONSTRAINT fk_post_img
        FOREIGN KEY (post_id) REFERENCES Posts(post_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Applications(
    application_id          INT NOT NULL AUTO_INCREMENT,
    application_title       VARCHAR(255) NOT NULL,
    application_description TEXT DEFAULT NULL,
    PRIMARY KEY (application_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ApplicationsDocuments(
    ap_document_id    INT NOT NULL AUTO_INCREMENT,
    application_id    INT NOT NULL,
    file_path         VARCHAR(255) NOT NULL,
    PRIMARY KEY (ap_document_id),
    CONSTRAINT fk_app_doc
        FOREIGN KEY (application_id) REFERENCES Applications(application_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Submissions(
    application_id    INT NOT NULL,
    user_id           INT NOT NULL,
    file_path         VARCHAR(255) NULL DEFAULT NULL,
    submission_data   TEXT DEFAULT NULL,
    submitted_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sub_status        ENUM('waiting','approved','rejected') DEFAULT 'waiting',
    PRIMARY KEY (application_id, user_id),
    CONSTRAINT fk_sub_ap
        FOREIGN KEY (application_id) REFERENCES Applications(application_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_sub_user
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Products(
    product_id          INT NOT NULL AUTO_INCREMENT,
    product_name        VARCHAR(255) NOT NULL,
    product_description TEXT DEFAULT NULL,
    price               DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ProductsImages(
    pro_image_id       INT NOT NULL AUTO_INCREMENT,
    product_id         INT NOT NULL,
    image_path         VARCHAR(255) NOT NULL,
    PRIMARY KEY (pro_image_id),
    CONSTRAINT fk_prod_img
        FOREIGN KEY (product_id) REFERENCES Products(product_id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Orders(
    order_id           INT NOT NULL AUTO_INCREMENT,
    user_id            INT NOT NULL,
    total_price        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    order_status       ENUM('pending','paid','cancelled') NOT NULL DEFAULT 'pending',
    PRIMARY KEY (order_id),
    CONSTRAINT fk_order_user
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS OrderItems(
    order_id           INT NOT NULL,
    product_id         INT NOT NULL,
    price_at_purchase  DECIMAL(10,2) NOT NULL,
    quantity           INT NOT NULL DEFAULT 1,
    size               VARCHAR(20) DEFAULT NULL,
    PRIMARY KEY (order_id, product_id),
    CONSTRAINT fk_oi_order
        FOREIGN KEY (order_id) REFERENCES Orders(order_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_oi_product
        FOREIGN KEY (product_id) REFERENCES Products(product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS Payments(
    payment_id         INT NOT NULL AUTO_INCREMENT,
    user_id            INT NOT NULL,
    amount             DECIMAL(10,2) NOT NULL,
    payment_date       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    payment_status     ENUM('completed','failed','refunded') NOT NULL DEFAULT 'completed',
    payment_type       ENUM('membership','insurance','product') NOT NULL,
    PRIMARY KEY (payment_id),
    CONSTRAINT fk_pay_user
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS PaymentsDetails(
    payment_item_id      INT NOT NULL AUTO_INCREMENT,
    payment_id           INT NOT NULL,
    product_id           INT NOT NULL,
    quantity             INT NOT NULL DEFAULT 1,
    price_at_purchase    DECIMAL(10,2) NOT NULL,
    size                 VARCHAR(10) DEFAULT NULL,
    PRIMARY KEY (payment_item_id),
    CONSTRAINT fk_pd_payment
        FOREIGN KEY (payment_id) REFERENCES Payments(payment_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_pd_product
        FOREIGN KEY (product_id) REFERENCES Products(product_id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS SystemSchedule(
    ss_id             INT NOT NULL AUTO_INCREMENT,
    feature           ENUM('registration','purchase','applications','delete_pending_users','cleanup_applications') NOT NULL,
    start_date        DATETIME NOT NULL,
    end_date          DATETIME NOT NULL,
    ss_status         ENUM('active','inactive') DEFAULT 'inactive',
    PRIMARY KEY (ss_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;