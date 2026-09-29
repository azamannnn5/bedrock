<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vendors.php');
    exit;
}

$id = $_POST['id'] ?: null;
$name = trim($_POST['name'] ?? '');

if ($name === '') {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Vendor name cannot be empty.'];
    header('Location: vendors.php');
    exit;
}

$db = get_db();

try {
    if ($id) {
        $stmt = $db->prepare("SELECT name FROM vendors WHERE id = ?");
        $stmt->execute([$id]);
        $old = $stmt->fetch();

        $db->beginTransaction();
        $db->prepare("UPDATE vendors SET name = ? WHERE id = ?")->execute([$name, $id]);
        if ($old && $old['name'] !== $name) {
            // Keep existing products in sync with the renamed vendor
            $db->prepare("UPDATE products SET vendor = ? WHERE vendor = ?")->execute([$name, $old['name']]);
        }
        $db->commit();
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Renamed to \"$name\"."];
    } else {
        $stmt = $db->prepare("INSERT INTO vendors (name) VALUES (?)");
        $stmt->execute([$name]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => "Added \"$name\"."];
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log('Vendor save failed: ' . $e->getMessage());
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Could not save, that name may already exist.'];
}

header('Location: vendors.php');
exit;
