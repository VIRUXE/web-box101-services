<?php
declare(strict_types=1);

class ServicePart {
    public function __construct(
        private int $id = 0,
        private int $service_id = 0,
        private string $description = '',
        private float $customer_price = 0.0,
        private ?float $supplier_price = null,
        private int $quantity = 1,
        private ?string $supplier = null,
        private bool $supplier_paid = false,
        private int $created_by = 0,
        private string $created_at = '',
        private ?float $supplier_discount = null,
        private ?string $origin = null,
        array $data = []
    ) {
        foreach ($data as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public static function getById(int $id): ?self {
        global $db;
        
        $query = $db->query("SELECT * FROM vehicle_service_parts WHERE id = $id");
        if (!$query->num_rows) return null;
        
        return new self($query->fetch_assoc());
    }

    public static function create(
        int $service_id,
        string $description,
        float $customer_price,
        ?float $supplier_price = null,
        int $quantity = 1,
        ?string $supplier = null,
        bool $supplier_paid = false,
        ?float $supplier_discount = null,
        ?string $origin = null
    ): ?self {
        global $db;
        
        try {
            $db->begin_transaction();
            
            $stmt = $db->prepare("
                INSERT INTO vehicle_service_parts (
                    service_id, description, customer_price, supplier_price,
                    quantity, supplier, supplier_paid, created_by,
                    supplier_discount, origin
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $created_by = User::getLogged()->id;
            $stmt->bind_param(
                "isddisiisd",
                $service_id,
                $description,
                $customer_price,
                $supplier_price,
                $quantity,
                $supplier,
                $supplier_paid,
                $created_by,
                $supplier_discount,
                $origin
            );
            
            if (!$stmt->execute()) throw new Exception('Failed to create part');
            
            $id = $db->insert_id;
            $db->commit();
            
            return self::getById($id);
        } catch (Exception $e) {
            $db->rollback();
            return null;
        }
    }

    public function update(
        string $description,
        float $customer_price,
        ?float $supplier_price = null,
        int $quantity = 1,
        ?string $supplier = null,
        bool $supplier_paid = false,
        ?float $supplier_discount = null,
        ?string $origin = null
    ): bool {
        global $db;
        
        try {
            $stmt = $db->prepare("
                UPDATE vehicle_service_parts 
                SET description = ?, 
                    customer_price = ?,
                    supplier_price = ?,
                    quantity = ?,
                    supplier = ?,
                    supplier_paid = ?,
                    supplier_discount = ?,
                    origin = ?
                WHERE id = ?
            ");
            
            $stmt->bind_param(
                "sddisidsi",
                $description,
                $customer_price,
                $supplier_price,
                $quantity,
                $supplier,
                $supplier_paid,
                $supplier_discount,
                $origin,
                $this->id
            );
            
            if (!$stmt->execute()) return false;
            
            $this->description = $description;
            $this->customer_price = $customer_price;
            $this->supplier_price = $supplier_price;
            $this->quantity = $quantity;
            $this->supplier = $supplier;
            $this->supplier_paid = $supplier_paid;
            $this->supplier_discount = $supplier_discount;
            $this->origin = $origin;
            
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete(): bool {
        global $db;
        
        $stmt = $db->prepare("DELETE FROM vehicle_service_parts WHERE id = ?");
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }

    public function getCustomerTotal(): float {
        return $this->customer_price * $this->quantity;
    }

    public function getSupplierTotal(): ?float {
        if ($this->supplier_price === null) return null;
        return $this->supplier_price * $this->quantity;
    }

    // Getters
    public function getId(): int { return $this->id; }
    public function getServiceId(): int { return $this->service_id; }
    public function getDescription(): string { return $this->description; }
    public function getCustomerPrice(): float { return $this->customer_price; }
    public function getSupplierPrice(): ?float { return $this->supplier_price; }
    public function getQuantity(): int { return $this->quantity; }
    public function getSupplier(): ?string { return $this->supplier; }
    public function isSupplierPaid(): bool { return $this->supplier_paid; }
    public function getCreatedBy(): int { return $this->created_by; }
    public function getCreatedAt(): string { return $this->created_at; }
    public function getSupplierDiscount(): ?float { return $this->supplier_discount; }
    public function getOrigin(): ?string { return $this->origin; }
}
