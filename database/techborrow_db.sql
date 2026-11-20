-- Import this file after selecting your existing TechBorrow database in phpMyAdmin.
-- This file creates the application tables; it does not create or drop a database.
-- After importing, register your own account, then promote it in phpMyAdmin:
-- UPDATE users SET role = 'admin' WHERE email = 'your-account-email';

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('instructor', 'admin', 'student') NOT NULL DEFAULT 'instructor',
    department VARCHAR(150) DEFAULT NULL,
    student_number VARCHAR(50) DEFAULT NULL UNIQUE,
    account_status ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    equipment_code VARCHAR(80) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    brand VARCHAR(100) DEFAULT NULL,
    model VARCHAR(100) DEFAULT NULL,
    serial_number VARCHAR(100) DEFAULT NULL,
    total_quantity INT NOT NULL DEFAULT 1,
    available_quantity INT NOT NULL DEFAULT 1,
    `condition` ENUM('excellent', 'good', 'fair', 'damaged', 'maintenance') NOT NULL DEFAULT 'good',
    status ENUM('available', 'reserved', 'borrowed', 'maintenance', 'unavailable', 'archived') NOT NULL DEFAULT 'available',
    location VARCHAR(150) DEFAULT NULL,
    photo_url VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_category FOREIGN KEY (category_id) REFERENCES categories(category_id)
);

CREATE TABLE borrowing_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    activity_name VARCHAR(255) NOT NULL,
    start_datetime DATETIME NOT NULL,
    due_datetime DATETIME NOT NULL,
    student_face_path VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'borrowed', 'returned', 'unavailable', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_borrowing_requests_user FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE request_items (
    request_item_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    equipment_id INT NOT NULL,
    quantity_requested INT NOT NULL,
    quantity_approved INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_request_items_request FOREIGN KEY (request_id) REFERENCES borrowing_requests(request_id) ON DELETE CASCADE,
    CONSTRAINT fk_request_items_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id)
);

CREATE TABLE request_reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    reviewed_by INT NOT NULL,
    decision ENUM('approve', 'reject') NOT NULL,
    reason TEXT,
    reviewed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_request_reviews_request FOREIGN KEY (request_id) REFERENCES borrowing_requests(request_id) ON DELETE CASCADE,
    CONSTRAINT fk_request_reviews_admin FOREIGN KEY (reviewed_by) REFERENCES users(user_id)
);

CREATE TABLE borrow_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT DEFAULT NULL,
    user_id INT NOT NULL,
    released_by INT NOT NULL,
    release_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expected_return_datetime DATETIME NOT NULL,
    status ENUM('borrowed', 'returned', 'overdue') NOT NULL DEFAULT 'borrowed',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_borrow_transactions_request FOREIGN KEY (request_id) REFERENCES borrowing_requests(request_id),
    CONSTRAINT fk_borrow_transactions_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_borrow_transactions_released_by FOREIGN KEY (released_by) REFERENCES users(user_id)
);

CREATE TABLE borrow_transaction_items (
    transaction_item_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    equipment_id INT NOT NULL,
    quantity_released INT NOT NULL,
    quantity_returned INT NOT NULL DEFAULT 0,
    CONSTRAINT fk_transaction_items_transaction FOREIGN KEY (transaction_id) REFERENCES borrow_transactions(transaction_id) ON DELETE CASCADE,
    CONSTRAINT fk_transaction_items_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id)
);

CREATE TABLE return_records (
    return_id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    user_id INT NOT NULL,
    inspected_by INT NOT NULL,
    return_datetime DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `condition` ENUM('good', 'damaged', 'incomplete', 'maintenance') NOT NULL DEFAULT 'good',
    remarks TEXT,
    CONSTRAINT fk_return_records_transaction FOREIGN KEY (transaction_id) REFERENCES borrow_transactions(transaction_id) ON DELETE CASCADE,
    CONSTRAINT fk_return_records_user FOREIGN KEY (user_id) REFERENCES users(user_id),
    CONSTRAINT fk_return_records_inspector FOREIGN KEY (inspected_by) REFERENCES users(user_id)
);

CREATE TABLE return_items (
    return_item_id INT AUTO_INCREMENT PRIMARY KEY,
    return_id INT NOT NULL,
    equipment_id INT NOT NULL,
    quantity_returned INT NOT NULL,
    returned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `condition` ENUM('good', 'damaged', 'incomplete', 'maintenance') NOT NULL DEFAULT 'good',
    remarks TEXT,
    CONSTRAINT fk_return_items_return FOREIGN KEY (return_id) REFERENCES return_records(return_id) ON DELETE CASCADE,
    CONSTRAINT fk_return_items_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id)
);

CREATE TABLE maintenance_records (
    maintenance_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    reported_by INT NOT NULL,
    issue_description TEXT NOT NULL,
    status ENUM('reported', 'in_progress', 'completed') NOT NULL DEFAULT 'reported',
    assigned_to VARCHAR(150) DEFAULT NULL,
    reported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    CONSTRAINT fk_maintenance_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id),
    CONSTRAINT fk_maintenance_reported_by FOREIGN KEY (reported_by) REFERENCES users(user_id)
);

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(user_id)
);

CREATE TABLE announcements (
    announcement_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_announcements_user FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE audit_logs (
    audit_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(150) NOT NULL,
    entity_type VARCHAR(100) NOT NULL,
    entity_id INT NOT NULL,
    details TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(user_id)
);
