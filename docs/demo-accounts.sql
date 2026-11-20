-- TEST ONLY: import only into a disposable TechBorrow database.
-- All three accounts use BorrowDemo#2026. Change or remove these accounts before
-- exposing the application publicly. The password hash was generated with PHP
-- password_hash(..., PASSWORD_DEFAULT).
INSERT INTO users
    (full_name, email, password_hash, role, department, student_number, account_status)
VALUES
    ('TechBorrow Demo Admin', 'demo.admin@example.test', '$2y$10$E7KUjW9c6nfhCh1reQZygudfXvAYIGHjH9b81NWeAB2SpOM80wN4G', 'admin', 'DEMO', NULL, 'active'),
    ('TechBorrow Demo Instructor', 'demo.instructor@example.test', '$2y$10$E7KUjW9c6nfhCh1reQZygudfXvAYIGHjH9b81NWeAB2SpOM80wN4G', 'instructor', 'DEMO', NULL, 'active'),
    ('TechBorrow Demo Student', 'demo.student@example.test', '$2y$10$E7KUjW9c6nfhCh1reQZygudfXvAYIGHjH9b81NWeAB2SpOM80wN4G', 'student', 'DEMO', 'DEMO-STU-001', 'active');
