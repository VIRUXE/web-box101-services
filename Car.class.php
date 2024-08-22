<?php
class Car {
	public string $matricula;
	public ?int $year;
	public ?int $month;
	public string $brand;
	public string $model;
	public ?string $colour;
	public ?string $trim;
	public ?User $owner;

	public function __construct(array $car) {
		$this->matricula = $this->formatPlate($car['matricula']);
		$this->year      = $car['year'] ?? NULL;
		$this->month     = $car['month'] ?? NULL;
		$this->brand     = ucwords(strtolower($car['brand']));
		$this->model     = ucwords(strtolower($car['model']));
		$this->colour    = $car['colour'] ? ucwords(strtolower($car['colour'])) : NULL;
		$this->trim      = $car['trim'] ? ucwords(strtolower($car['trim'])) : NULL;

		// Get the most recent owner
		global $db;
		$result = $db->query("SELECT u.* FROM users u JOIN user_cars uc ON u.id = uc.owner_id WHERE uc.matricula = '{$this->matricula}' ORDER BY uc.registration_date DESC LIMIT 1;");

		if ($result->num_rows) $this->owner = new User($result->fetch_assoc());
	}

	private function formatPlate(string $plate): string {
		// Try to figure out the plate format
		// Portuguese plates are in sequence with characters and numbers dashes are optional
		// UK plates can be AA00 AAA or AA00 AAAA
		// Swiss plates are 00-0000 or VD 12345

		
		return strtoupper($plate);
	}

	public function getManufactureYear(): string {
		return $this->year ?: 'N/D';
	}

	public function getManufactureMonth(): string {
		return $this->month ?: 'N/D';
	}

	public function getManufactureDate(): string {
		return $this->year . ($this->month ? '/' . $this->month : '');
	}

	public function getColour(): string {
		return $this->colour ?: 'N/D';
	}

	public function getTrim(): string {
		return $this->trim ?: 'N/D';
	}

	public function __toString(): string {
		return "{$this->brand} {$this->model} ({$this->getManufactureDate()}, {$this->getColour()})";
	}
}