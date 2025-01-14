<?php
declare(strict_types=1);

require_once 'ServiceState.enum.php';

class Service {
    private int $id;
    private string $matricula;
    private ?int $client_id;
    private ?string $starting_date;
    private ?string $ending_date;
    private int $created_by;
    private ServiceState $state;
    private ?int $starting_odometer;
    private ?int $finished_odometer;
    private ?float $paid_amount;
    private string $created_at;
    private ?string $notes;

    public function __construct(array $data = []) {
        $this->id                = (int)($data['id'] ?? 0);
        $this->matricula         = $data['matricula'] ?? '';
        $this->client_id         = isset($data['client_id']) ? (int)$data['client_id'] : null;
        $this->starting_date     = $data['starting_date'] ?? null;
        $this->ending_date       = $data['ending_date'] ?? null;
        $this->created_by        = (int)($data['created_by'] ?? 0);
        $this->state             = isset($data['state']) ? ServiceState::from($data['state']) : ServiceState::PENDING;
        $this->starting_odometer = isset($data['starting_odometer']) ? (int)$data['starting_odometer'] : null;
        $this->finished_odometer = isset($data['finished_odometer']) ? (int)$data['finished_odometer'] : null;
        $this->paid_amount       = isset($data['paid_amount']) ? (float)$data['paid_amount'] : 0.0;
        $this->created_at        = $data['created_at'] ?? '';
        $this->notes             = $data['notes'] ?? null;
    }

    public static function create(
        string $matricula, 
        int $created_by, 
        ServiceState $state = ServiceState::PENDING,
        ?int $client_id = null,
        ?string $starting_date = null,
        ?int $starting_odometer = null
    ): ?self {
        global $db;
        
        try {
            $db->begin_transaction();
            
            $stmt = $db->prepare("INSERT INTO vehicle_services (matricula, client_id, starting_date, created_by, state, starting_odometer) VALUES (?, ?, ?, ?, ?, ?)");
            $stateValue = $state->value;
            $stmt->bind_param("sisssi", $matricula, $client_id, $starting_date, $created_by, $stateValue, $starting_odometer);
            
            if (!$stmt->execute()) return null;
            
            $id = $db->insert_id;
            $db->commit();
            
            return self::getById($id);
        } catch (Exception $e) {
            $db->rollback();
            return null;
        }
    }

    public static function getById(int $id): ?self {
        global $db;
        
        $query = $db->query("SELECT * FROM vehicle_services WHERE id = $id");
        
        if (!$query->num_rows) return null;
        
        return new self($query->fetch_assoc());
    }

    public function addItem(string $description, float $price): bool {
        global $db;
        
        $stmt = $db->prepare("INSERT INTO vehicle_service_items (service_id, description, price, created_by, status) VALUES (?, ?, ?, ?, 'PENDING')");
        
        return $stmt->execute([$this->id, $description, $price, $this->created_by]);
    }

    public function addPart(
        string $description, 
        float $customer_price,
        float $supplier_price,
        int $quantity = 1,
        ?string $supplier = null,
        bool $supplier_paid = false
    ): bool {
        global $db;
        
        $stmt = $db->prepare("INSERT INTO vehicle_service_parts (service_id, description, customer_price, supplier_price, quantity, supplier, supplier_paid, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        
        return $stmt->execute([$this->id, $description, $customer_price, $supplier_price, $quantity, $supplier, $supplier_paid, $this->created_by]);
    }

    public function getTotalCost(): array {
        global $db;
        
        $query = $db->query("
            SELECT 
                COALESCE(SUM(servicePart.customer_price * servicePart.quantity), 0) as parts_total,
                COALESCE(SUM(serviceItem.price), 0) as labor_total
            FROM vehicle_services service
            LEFT JOIN vehicle_service_parts servicePart ON service.id = servicePart.service_id
            LEFT JOIN vehicle_service_items serviceItem ON service.id = serviceItem.service_id
            WHERE service.id = {$this->id}
            GROUP BY service.id
        ");
        
        if (!$query->num_rows) return ['parts' => 0, 'labor' => 0, 'total' => 0];
        
        $result = $query->fetch_object();
        return [
            'parts' => (float)$result->parts_total,
            'labor' => (float)$result->labor_total,
            'total' => (float)$result->parts_total + (float)$result->labor_total
        ];
    }

    public function updateState(ServiceState $state): bool {
        global $db;
        
        // Validate state transition
        if ($this->state === ServiceState::COMPLETED && $state !== ServiceState::CANCELLED) return false;
        if ($this->state === ServiceState::CANCELLED) return false;
        
        $stateValue = $state->value;
        $stmt = $db->prepare("UPDATE vehicle_services SET state = ? WHERE id = ?");
        if (!$stmt->execute([$stateValue, $this->id])) return false;
        
        $this->state = $state;
        return true;
    }

    public function complete(?int $finished_odometer = null, ?string $ending_date = null): bool {
        global $db;
        
        $ending_date = $ending_date ?? date('Y-m-d H:i:s');
        
        $stmt = $db->prepare("UPDATE vehicle_services SET state = ?, ending_date = ?, finished_odometer = ? WHERE id = ?");
        
        $stateValue = ServiceState::COMPLETED->value;
        if (!$stmt->execute([$stateValue, $ending_date, $finished_odometer, $this->id])) return false;
        
        $this->state = ServiceState::COMPLETED;
        $this->ending_date = $ending_date;
        $this->finished_odometer = $finished_odometer;
        return true;
    }

    public function recordPayment(float $amount): bool {
        global $db;
        
        $stmt = $db->prepare("UPDATE vehicle_services SET paid_amount = COALESCE(paid_amount, 0) + ? WHERE id = ?");
        
        if (!$stmt->execute([$amount, $this->id])) return false;
        
        $this->paid_amount =+ $amount;
        return true;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getMatricula(): string { return $this->matricula; }
    public function getClientId(): ?int { return $this->client_id; }
    public function getStartingDate(): ?string { return $this->starting_date; }
    public function getEndingDate(): ?string { return $this->ending_date; }
    public function getCreatedBy(): int { return $this->created_by; }
    public function getState(): ServiceState { return $this->state; }
    public function getStartingOdometer(): ?int { return $this->starting_odometer; }
    public function getFinishedOdometer(): ?int { return $this->finished_odometer; }
    public function getPaidAmount(): ?float { return $this->paid_amount; }
    public function getCreatedAt(): string { return $this->created_at; }
    public function getNotes(): ?string { return $this->notes; }
}
