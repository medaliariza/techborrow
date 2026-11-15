ALTER TABLE users
MODIFY role ENUM('instructor', 'admin', 'student') NOT NULL DEFAULT 'instructor',
ADD COLUMN student_number VARCHAR(50) DEFAULT NULL UNIQUE;

ALTER TABLE borrowing_requests
ADD COLUMN student_face_path VARCHAR(255) DEFAULT NULL AFTER due_datetime;
