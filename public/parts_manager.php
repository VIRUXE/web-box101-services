<?php
declare(strict_types=1);

header('Content-Type: application/json');
require_once '../database.php';

$action = $_POST['action'] ?? '';
if (empty($action)) die(json_encode(['error' => 'No action specified']));

$response = ['success' => false, 'error' => null];

if ($action === 'get_part') {
    $part_id = intval($_POST['part_id'] ?? 0);
    if ($part_id <= 0) die(json_encode(['error' => 'Invalid part ID']));

    $stmt = $db->prepare("SELECT * FROM vehicle_service_parts WHERE id = ?");
    if (!$stmt) die(json_encode(['error' => 'Database error: ' . $db->error]));

    $stmt->bind_param('i', $part_id);
    if (!$stmt->execute()) die(json_encode(['error' => 'Failed to get part: ' . $stmt->error]));

    $result = $stmt->get_result();
    $part = $result->fetch_object();
    
    if (!$part) die(json_encode(['error' => 'Part not found']));
    
    die(json_encode(['success' => true, 'part' => $part]));
}

if ($action === 'add_part' || $action === 'edit_part') {
    $description = $db->real_escape_string($_POST['description'] ?? '');
    
    if (empty($description)) die(json_encode(['error' => 'Description is required']));

    $service_id = intval($_POST['service_id'] ?? 0);
    if ($service_id <= 0) die(json_encode(['error' => 'Invalid service ID']));

    $quantity          = intval($_POST['quantity'] ?? 1);
    $customer_price    = floatval(str_replace(',', '.', $_POST['customer_price'] ?? '0'));
    $supplier_price    = !empty($_POST['supplier_price']) ? floatval(str_replace(',', '.', $_POST['supplier_price'])) : null;
    $supplier_discount = !empty($_POST['supplier_discount']) ? intval($_POST['supplier_discount']) : null;
    $supplier_paid     = isset($_POST['supplier_paid']) ? 1 : 0;
    $origin            = $db->real_escape_string($_POST['origin'] ?? '');
    $added_by          = intval($_SESSION['user_id'] ?? 0);

    if ($action === 'add_part') {
        $stmt = $db->prepare("INSERT INTO vehicle_service_parts (service_id, description, quantity, customer_price, supplier_price, supplier_discount, supplier_paid, origin, added_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) die(json_encode(['error' => 'Database error: ' . $db->error]));

        $stmt->bind_param('isiddiisi', $service_id, $description, $quantity, $customer_price, $supplier_price, $supplier_discount, $supplier_paid, $origin, $added_by);

        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'part_id' => $stmt->insert_id,
                'message' => 'Part added successfully'
            ];
        } else $response['error'] = 'Failed to add part: ' . $stmt->error;

        $stmt->close();
    } else {
        $part_id = intval($_POST['part_id'] ?? 0);
        if ($part_id <= 0) die(json_encode(['error' => 'Invalid part ID']));

        $stmt = $db->prepare("UPDATE vehicle_service_parts SET description = ?, quantity = ?, customer_price = ?, supplier_price = ?, supplier_discount = ?, supplier_paid = ?, origin = ? WHERE id = ? AND service_id = ?");
        if (!$stmt) die(json_encode(['error' => 'Database error: ' . $db->error]));

        $stmt->bind_param('siddiisii', $description, $quantity, $customer_price, $supplier_price, $supplier_discount, $supplier_paid, $origin, $part_id, $service_id);

        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Part updated successfully'
            ];
        } else $response['error'] = 'Failed to update part: ' . $stmt->error;

        $stmt->close();
    }
} elseif ($action === 'delete_part') {
    $part_id = intval($_POST['part_id'] ?? 0);
    $service_id = intval($_POST['service_id'] ?? 0);
    
    if ($part_id <= 0 || $service_id <= 0) die(json_encode(['error' => 'Invalid part or service ID']));

    $stmt = $db->prepare("UPDATE vehicle_service_parts SET deleted = 1 WHERE id = ? AND service_id = ?");
    if (!$stmt) die(json_encode(['error' => 'Database error: ' . $db->error]));

    $stmt->bind_param('ii', $part_id, $service_id);

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Part deleted successfully'
        ];
    } else $response['error'] = 'Failed to delete part: ' . $stmt->error;

    $stmt->close();
} else $response['error'] = 'Invalid action';

echo json_encode($response);