<?php
require_once '../database.php';
require_once '../User.class.php';

session_start();
if (!User::isLogged()) die(json_encode(['success' => false, 'message' => 'Unauthorized']));
$logged_user = User::getLogged();

if (!isset($_POST['action']) || !isset($_POST['item_id'])) die(json_encode(['success' => false, 'message' => 'Missing required parameters']));

$action = $_POST['action'];
$item_id = (int)$_POST['item_id'];

$item = $db->query("SELECT * FROM vehicle_service_items WHERE id = $item_id")->fetch_object();
if (!$item) die(json_encode(['success' => false, 'message' => 'Item not found']));

$new_status = match($action) {
    'start' => 'STARTED',
    'pause' => 'PAUSED',
    'resume' => 'STARTED',
    'finish' => 'SUCCESS',
    default => die(json_encode(['success' => false, 'message' => 'Invalid action']))
};

$db->begin_transaction();

try {
    // Update item status
    $update_query = "UPDATE vehicle_service_items SET status = '$new_status'";
    
    // Set start_date if starting
    if (($action === 'start' || $action === 'resume') && !$item->start_date) $update_query .= ", start_date = NOW()";
    
    // Set end_date if finishing
    if ($action === 'finish') $update_query .= ", end_date = NOW()";
    
    $update_query .= " WHERE id = $item_id";
    
    if (!$db->query($update_query)) throw new Exception('Failed to update item status');

    // Close previous tracking entry if exists
    $db->query("UPDATE vehicle_service_item_tracking SET end_date = NOW() WHERE service_item_id = $item_id AND end_date IS NULL");

    // Insert new tracking entry
    if (!$db->query("INSERT INTO vehicle_service_item_tracking (service_item_id, user_id, start_date) VALUES ($item_id, {$logged_user->id}, NOW())")) throw new Exception('Failed to track status change');

    $db->commit();
    echo json_encode(['success' => true, 'new_status' => $new_status]);
} catch (Exception $e) {
    $db->rollback();
    die(json_encode(['success' => false, 'message' => $e->getMessage()]));
}
