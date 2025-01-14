<?php
require_once '../database.php';
require_once '../User.class.php';
require_once '../ServiceItem.class.php';
require_once '../ServiceItemState.enum.php';

session_start();
if (!User::isLogged()) die(json_encode(['success' => false, 'message' => 'Unauthorized']));

$action = $_POST['action'];
if (!isset($action) || !isset($_POST['item_id'])) die(json_encode(['success' => false, 'message' => 'Missing required parameters']));

$item = ServiceItem::getById((int)$_POST['item_id']);
if (!$item) die(json_encode(['success' => false, 'message' => 'Item not found']));

$new_status = match($action) {
    'start'  => ServiceItemState::STARTED,
    'pause'  => ServiceItemState::PAUSED,
    'resume' => ServiceItemState::STARTED,
    'finish' => ServiceItemState::SUCCESS,
    default  => die(json_encode(['success' => false, 'message' => 'Invalid action']))
};

if ($item->updateStatus($new_status)) {
    echo json_encode([
        'success'    => true,
        'new_status' => $new_status->value,
        'label'      => $new_status->label(),
        'color'      => $new_status->color()
    ]);
} else {
    die(json_encode([
        'success' => false, 
        'message' => 'Failed to update item status'
    ]));
}
