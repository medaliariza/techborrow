<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/admin/equipment.php');
}

$db = getDbConnection();
$action = $_POST['action'] ?? 'create';
$allowedConditions = ['excellent', 'good', 'fair', 'damaged', 'maintenance'];
$uploadedPhoto = null;
$oldPhoto = null;
$deleteStoredPhoto = static function ($storedPath) {
    $photoDirectory = realpath(__DIR__ . '/../uploads/equipment');
    if (!$storedPath || $photoDirectory === false) {
        return;
    }

    $fileName = basename(str_replace('\\', '/', $storedPath));
    $photoPath = $photoDirectory . DIRECTORY_SEPARATOR . $fileName;
    if ($fileName !== '' && is_file($photoPath)) {
        unlink($photoPath);
    }
};

try {
    if ($action === 'delete') {
        $id = (int) ($_POST['equipment_id'] ?? 0);
        if ($id <= 0) {
            throw new InvalidArgumentException('Invalid equipment selected.');
        }

        $db->beginTransaction();
        $equipmentCheck = $db->prepare('SELECT equipment_id, photo_url FROM equipment WHERE equipment_id = :id LIMIT 1 FOR UPDATE');
        $equipmentCheck->execute(['id' => $id]);
        $equipmentRow = $equipmentCheck->fetch();
        if (!$equipmentRow) {
            throw new InvalidArgumentException('Equipment item not found.');
        }

        $referenceCheck = $db->prepare('SELECT EXISTS(SELECT 1 FROM request_items WHERE equipment_id = :request_id) OR EXISTS(SELECT 1 FROM borrow_transaction_items WHERE equipment_id = :transaction_id) OR EXISTS(SELECT 1 FROM return_items WHERE equipment_id = :return_id) OR EXISTS(SELECT 1 FROM maintenance_records WHERE equipment_id = :maintenance_id)');
        $referenceCheck->execute([
            'request_id' => $id,
            'transaction_id' => $id,
            'return_id' => $id,
            'maintenance_id' => $id,
        ]);

        if ((int) $referenceCheck->fetchColumn() > 0) {
            $archive = $db->prepare('UPDATE equipment SET status = :status, available_quantity = 0, updated_at = NOW() WHERE equipment_id = :id');
            $archive->execute(['status' => 'archived', 'id' => $id]);
            $db->commit();
            setFlash('success', 'Equipment has linked records, so it was archived and removed from availability while its history was preserved.');
        } else {
            $delete = $db->prepare('DELETE FROM equipment WHERE equipment_id = :id');
            $delete->execute(['id' => $id]);
            $db->commit();
            setFlash('success', 'Equipment item deleted successfully.');
            $deleteStoredPhoto($equipmentRow['photo_url']);
        }
        redirect(BASE_URL . '/admin/equipment.php');
    }

    $equipmentCode = trim($_POST['equipment_code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $condition = trim($_POST['condition'] ?? 'good');
    $brand = trim($_POST['brand'] ?? '');
    $model = trim($_POST['model'] ?? '');
    $serialNumber = trim($_POST['serial_number'] ?? '');
    $equipmentId = (int) ($_POST['equipment_id'] ?? 0);

    if ($equipmentCode === '' || $name === '' || $categoryId <= 0) {
        throw new InvalidArgumentException('Please fill in all required equipment fields.');
    }

    if (!in_array($condition, $allowedConditions, true)) {
        throw new InvalidArgumentException('Invalid equipment condition.');
    }

    if ($action === 'update') {
        if ($equipmentId <= 0) {
            throw new InvalidArgumentException('Invalid equipment id.');
        }

        $photoLookup = $db->prepare('SELECT photo_url FROM equipment WHERE equipment_id = :id LIMIT 1');
        $photoLookup->execute(['id' => $equipmentId]);
        $oldPhoto = $photoLookup->fetchColumn();
        if ($oldPhoto === false) {
            throw new InvalidArgumentException('Equipment item not found.');
        }
    }

    $photoUrl = $oldPhoto ?: null;
    if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
        $photoUrl = null;
    }

    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $photo = $_FILES['photo'];
        if ($photo['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('The equipment photo could not be uploaded. Please try again.');
        }
        if ($photo['size'] > 5 * 1024 * 1024) {
            throw new InvalidArgumentException('Equipment photos must be 5 MB or smaller.');
        }

        $imageInfo = getimagesize($photo['tmp_name']);
        $imageExtensions = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];
        if ($imageInfo === false || !isset($imageExtensions[$imageInfo[2]])) {
            throw new InvalidArgumentException('Choose a valid JPG, PNG, GIF, or WEBP image.');
        }

        $uploadDirectory = __DIR__ . '/../uploads/equipment';
        if (!is_dir($uploadDirectory) && !mkdir($uploadDirectory, 0755, true) && !is_dir($uploadDirectory)) {
            throw new RuntimeException('The equipment photo folder could not be created.');
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $imageExtensions[$imageInfo[2]];
        $uploadedPhoto = $uploadDirectory . '/' . $fileName;
        if (!move_uploaded_file($photo['tmp_name'], $uploadedPhoto)) {
            $uploadedPhoto = null;
            throw new RuntimeException('The equipment photo could not be saved.');
        }
        $photoUrl = 'uploads/equipment/' . $fileName;
    }

    if ($action === 'update') {
        $stmt = $db->prepare('UPDATE equipment SET category_id = :category_id, equipment_code = :equipment_code, name = :name, brand = :brand, model = :model, serial_number = :serial_number, `condition` = :condition, photo_url = :photo_url, updated_at = NOW() WHERE equipment_id = :equipment_id');
        $stmt->execute([
            'category_id' => $categoryId,
            'equipment_code' => $equipmentCode,
            'name' => $name,
            'brand' => $brand,
            'model' => $model,
            'serial_number' => $serialNumber,
            'condition' => $condition,
            'photo_url' => $photoUrl,
            'equipment_id' => $equipmentId,
        ]);
        setFlash('success', 'Equipment item updated successfully.');
    } else {
        if ($action !== 'create') {
            throw new InvalidArgumentException('Invalid equipment action.');
        }

        $existing = $db->prepare('SELECT equipment_id FROM equipment WHERE equipment_code = :equipment_code LIMIT 1');
        $existing->execute(['equipment_code' => $equipmentCode]);
        if ($existing->fetch()) {
            throw new InvalidArgumentException('An equipment code already exists.');
        }

        $isServiceable = !in_array($condition, ['damaged', 'maintenance'], true);
        $initialQuantity = $isServiceable ? 1 : 0;
        $initialStatus = $isServiceable ? 'available' : 'maintenance';
        $stmt = $db->prepare('INSERT INTO equipment (category_id, equipment_code, name, brand, model, serial_number, total_quantity, available_quantity, `condition`, status, photo_url, created_at) VALUES (:category_id, :equipment_code, :name, :brand, :model, :serial_number, 1, :available_quantity, :condition, :status, :photo_url, NOW())');
        $stmt->execute([
            'category_id' => $categoryId,
            'equipment_code' => $equipmentCode,
            'name' => $name,
            'brand' => $brand,
            'model' => $model,
            'serial_number' => $serialNumber,
            'available_quantity' => $initialQuantity,
            'condition' => $condition,
            'status' => $initialStatus,
            'photo_url' => $photoUrl,
        ]);
        setFlash('success', 'Equipment item added successfully.');
    }

    if ($oldPhoto && ($uploadedPhoto || !$photoUrl)) {
        $deleteStoredPhoto($oldPhoto);
    }

    redirect(BASE_URL . '/admin/equipment.php');
} catch (Throwable $e) {
    if ($uploadedPhoto && is_file($uploadedPhoto)) {
        unlink($uploadedPhoto);
    }
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    setFlash('danger', $e->getMessage());
    redirect(BASE_URL . '/admin/equipment.php');
}
