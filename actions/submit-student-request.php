<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/student-borrow.php');
}

$db = getDbConnection();
$getString = static function ($key) {
    $value = $_POST[$key] ?? '';
    return is_string($value) ? trim($value) : '';
};
$fullName = $getString('full_name');
$studentNumber = $getString('student_number');
$email = strtolower($getString('email'));
$department = $getString('department');
$purpose = $getString('purpose');
$locationRoom = $getString('location_room');
$startInput = $_POST['start_datetime'] ?? '';
$dueInput = $_POST['due_datetime'] ?? '';
$equipmentIds = $_POST['equipment_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$photo = $_FILES['student_photo'] ?? null;

$start = is_string($startInput) ? DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $startInput) : false;
$startErrors = DateTimeImmutable::getLastErrors();
$due = is_string($dueInput) ? DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i', $dueInput) : false;
$dueErrors = DateTimeImmutable::getLastErrors();
$validStart = $start && $start->format('Y-m-d\\TH:i') === $startInput && ($startErrors === false || ($startErrors['warning_count'] === 0 && $startErrors['error_count'] === 0));
$validDue = $due && $due->format('Y-m-d\\TH:i') === $dueInput && ($dueErrors === false || ($dueErrors['warning_count'] === 0 && $dueErrors['error_count'] === 0));

$photoConsent = $_POST['photo_consent'] ?? '';
if ($fullName === '' || strlen($fullName) > 150 || $studentNumber === '' || strlen($studentNumber) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150 || $department === '' || strlen($department) > 150 || $purpose === '' || strlen($purpose) > 255 || $locationRoom === '' || strlen($locationRoom) > 255 || !$validStart || !$validDue || $start >= $due || $photoConsent !== '1' || !is_array($equipmentIds) || !is_array($quantities) || count($equipmentIds) === 0) {
    setFlash('danger', 'Please complete every field with valid information, consent to the portrait review, and select at least one equipment item.');
    redirect(BASE_URL . '/student-borrow.php');
}

if (!$photo || $photo['error'] !== UPLOAD_ERR_OK || $photo['size'] <= 0 || $photo['size'] > 5 * 1024 * 1024 || !is_uploaded_file($photo['tmp_name'])) {
    setFlash('danger', 'Upload a portrait photo smaller than 5 MB.');
    redirect(BASE_URL . '/student-borrow.php');
}

if (!class_exists('finfo')) {
    setFlash('danger', 'Photo validation is unavailable on the server. Please contact the administrator.');
    redirect(BASE_URL . '/student-borrow.php');
}

$fileInfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $fileInfo->file($photo['tmp_name']);
$allowedPhotoTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
if (!isset($allowedPhotoTypes[$mimeType])) {
    setFlash('danger', 'Portrait must be a JPEG, PNG, or WebP image.');
    redirect(BASE_URL . '/student-borrow.php');
}

$itemsToInsert = [];
$seenEquipment = [];
foreach ($equipmentIds as $index => $rawEquipmentId) {
    $rawQuantity = $quantities[$index] ?? null;
    $equipmentId = is_scalar($rawEquipmentId) ? filter_var($rawEquipmentId, FILTER_VALIDATE_INT) : false;
    $quantity = is_scalar($rawQuantity) ? filter_var($rawQuantity, FILTER_VALIDATE_INT) : false;
    if (!$equipmentId || !$quantity || $quantity < 1 || isset($seenEquipment[$equipmentId])) {
        setFlash('danger', 'Select each equipment item only once and enter a quantity of at least one.');
        redirect(BASE_URL . '/student-borrow.php');
    }

    $equipmentCheck = $db->prepare('SELECT status FROM equipment WHERE equipment_id = :id LIMIT 1');
    $equipmentCheck->execute(['id' => $equipmentId]);
    $equipmentRow = $equipmentCheck->fetch();
    if (!$equipmentRow || in_array($equipmentRow['status'], ['maintenance', 'unavailable', 'archived'], true)) {
        setFlash('danger', 'One of the selected equipment items is not available for requests.');
        redirect(BASE_URL . '/student-borrow.php');
    }

    $availability = getEffectiveEquipmentAvailability(
        $db,
        $equipmentId,
        $start->format('Y-m-d H:i:s'),
        $due->format('Y-m-d H:i:s')
    );
    if ($quantity > $availability['available']) {
        setFlash('danger', 'Requested quantity exceeds availability for the selected dates.');
        redirect(BASE_URL . '/student-borrow.php');
    }

    $seenEquipment[$equipmentId] = true;
    $itemsToInsert[] = ['equipment_id' => $equipmentId, 'quantity' => $quantity];
}

$uploadDirectory = __DIR__ . '/../uploads/student-faces';
if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0750, true) && !is_dir($uploadDirectory)) {
    setFlash('danger', 'The portrait upload folder could not be created. Please contact the administrator.');
    redirect(BASE_URL . '/student-borrow.php');
}

$fileName = bin2hex(random_bytes(16)) . '.' . $allowedPhotoTypes[$mimeType];
$photoFilePath = $uploadDirectory . '/' . $fileName;
$photoDatabasePath = 'uploads/student-faces/' . $fileName;
if (!move_uploaded_file($photo['tmp_name'], $photoFilePath)) {
    setFlash('danger', 'The portrait could not be saved. Please try again.');
    redirect(BASE_URL . '/student-borrow.php');
}

try {
    $db->beginTransaction();
    $existingUser = $db->prepare('SELECT user_id, role, email, student_number FROM users WHERE student_number = :student_number OR email = :email LIMIT 1 FOR UPDATE');
    $existingUser->execute(['student_number' => $studentNumber, 'email' => $email]);
    $existingUser = $existingUser->fetch();

    if ($existingUser) {
        if ($existingUser['role'] !== 'student' || $existingUser['student_number'] !== $studentNumber || strcasecmp($existingUser['email'], $email) !== 0) {
            throw new InvalidArgumentException('That email or student number is already associated with a different account. Contact the administrator.');
        }
        $userId = (int) $existingUser['user_id'];
    } else {
        $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $userInsert = $db->prepare('INSERT INTO users (full_name, email, password_hash, role, department, student_number, account_status, created_at) VALUES (:full_name, :email, :password_hash, "student", :department, :student_number, "active", NOW())');
        $userInsert->execute([
            'full_name' => $fullName,
            'email' => $email,
            'password_hash' => $passwordHash,
            'department' => $department,
            'student_number' => $studentNumber,
        ]);
        $userId = (int) $db->lastInsertId();
    }

    $requestInsert = $db->prepare('INSERT INTO borrowing_requests (user_id, purpose, activity_name, start_datetime, due_datetime, student_face_path, status, created_at) VALUES (:user_id, :purpose, :activity_name, :start_datetime, :due_datetime, :student_face_path, "pending", NOW())');
    $requestInsert->execute([
        'user_id' => $userId,
        'purpose' => $purpose,
        'activity_name' => $locationRoom,
        'start_datetime' => $start->format('Y-m-d H:i:s'),
        'due_datetime' => $due->format('Y-m-d H:i:s'),
        'student_face_path' => $photoDatabasePath,
    ]);
    $requestId = (int) $db->lastInsertId();

    $itemInsert = $db->prepare('INSERT INTO request_items (request_id, equipment_id, quantity_requested, quantity_approved) VALUES (:request_id, :equipment_id, :quantity_requested, 0)');
    foreach ($itemsToInsert as $item) {
        $itemInsert->execute([
            'request_id' => $requestId,
            'equipment_id' => $item['equipment_id'],
            'quantity_requested' => $item['quantity'],
        ]);
    }

    $db->commit();
    setFlash('success', 'Request #' . $requestId . ' submitted. An administrator will review it and mark it approved or unavailable.');
    redirect(BASE_URL . '/student-borrow.php');
} catch (InvalidArgumentException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    unlink($photoFilePath);
    setFlash('danger', $e->getMessage());
    redirect(BASE_URL . '/student-borrow.php');
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    unlink($photoFilePath);
    error_log('Student borrowing request failed: ' . $e->getMessage());
    setFlash('danger', 'The request could not be saved. Please try again or contact the administrator.');
    redirect(BASE_URL . '/student-borrow.php');
}
