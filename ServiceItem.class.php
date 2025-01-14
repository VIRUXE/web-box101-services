<?php
declare(strict_types=1);

require_once 'ServiceItemState.enum.php';

class ServiceItem {
    public function __construct(
        private int $id = 0,
        private int $service_id = 0,
        private string $description = '',
        private float $price = 0.0,
        private ServiceItemState $status = ServiceItemState::PENDING,
        private ?string $start_date = null,
        private ?string $end_date = null,
        private ?string $start_notes = null,
        private ?string $end_notes = null,
        private int $created_by = 0,
        private string $created_at = '',
        array $data = []
    ) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                if ($key === 'status') {
                    $this->status = ServiceItemState::from($value);
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    public static function getById(int $id): ?self {
        global $db;
        
        $query = $db->query("SELECT * FROM vehicle_service_items WHERE id = $id");
        if (!$query->num_rows) return null;
        
        return new self($query->fetch_assoc());
    }

    public function updateStatus(ServiceItemState $status, ?string $notes = null): bool {
        global $db;
        
        // Validate state transition
        if (!$this->status->canTransitionTo($status)) return false;

        $db->begin_transaction();
        try {
            $update_query = "UPDATE vehicle_service_items SET status = ?";
            
            // Set start_date if starting
            if ($status === ServiceItemState::STARTED && !$this->start_date) {
                $update_query .= ", start_date = NOW()";
            }
            
            // Set end_date if finishing
            if (in_array($status, [ServiceItemState::SUCCESS, ServiceItemState::FAILED])) {
                $update_query .= ", end_date = NOW()";
            }
            
            if ($notes) {
                if (in_array($status, [ServiceItemState::SUCCESS, ServiceItemState::FAILED])) {
                    $update_query .= ", end_notes = ?";
                } else {
                    $update_query .= ", start_notes = ?";
                }
            }
            
            $update_query .= " WHERE id = ?";
            
            $stmt = $db->prepare($update_query);
            $statusValue = $status->value;
            if ($notes) {
                $stmt->bind_param("ssi", $statusValue, $notes, $this->id);
            } else {
                $stmt->bind_param("si", $statusValue, $this->id);
            }
            
            if (!$stmt->execute()) throw new Exception('Failed to update item status');

            // Close previous tracking entry if exists
            $db->query("UPDATE vehicle_service_item_tracking SET end_date = NOW() WHERE service_item_id = {$this->id} AND end_date IS NULL");

            // Insert new tracking entry
            $stmt = $db->prepare("INSERT INTO vehicle_service_item_tracking (service_item_id, user_id, start_date) VALUES (?, ?, NOW())");
            $user_id = User::getLogged()->id;
            $stmt->bind_param("ii", $this->id, $user_id);
            if (!$stmt->execute()) throw new Exception('Failed to track status change');

            $db->commit();
            $this->status = $status;
            return true;
        } catch (Exception $e) {
            $db->rollback();
            return false;
        }
    }

    public function getTracking(): array {
        global $db;
        
        $query = $db->query("
            SELECT t.*, 
                   CONCAT(u.first_name, ' ', u.last_name) as user_name
            FROM vehicle_service_item_tracking t
            LEFT JOIN users u ON t.user_id = u.id
            WHERE t.service_item_id = {$this->id}
            ORDER BY t.start_date DESC
        ");
        
        $tracking = [];
        while ($row = $query->fetch_object()) {
            $tracking[] = $row;
        }
        
        return $tracking;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getServiceId(): int { return $this->service_id; }
    public function getDescription(): string { return $this->description; }
    public function getPrice(): float { return $this->price; }
    public function getStatus(): ServiceItemState { return $this->status; }
    public function getStartDate(): ?string { return $this->start_date; }
    public function getEndDate(): ?string { return $this->end_date; }
    public function getStartNotes(): ?string { return $this->start_notes; }
    public function getEndNotes(): ?string { return $this->end_notes; }
    public function getCreatedBy(): int { return $this->created_by; }
    public function getCreatedAt(): string { return $this->created_at; }
}
