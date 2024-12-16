<?php
class Vehicle {
	public string $matricula;
	public ?int $odometer;
	public ?int $year;
	public ?int $month;
	public string $brand;
	public string $model;
	public ?string $colour;
	public ?string $trim;
	public ?string $notes;
	public ?User $owner;

	public function __construct(array $vehicle) {
		$this->matricula = $this->formatPlate($vehicle['matricula']);
		$this->odometer  = $vehicle['odometer'] ?? 0;
		$this->year      = $vehicle['year'] ?? NULL;
		$this->month     = $vehicle['month'] ?? NULL;
		$this->brand     = ucwords(strtolower($vehicle['brand']));
		$this->model     = $vehicle['model'] ?? NULL;
		$this->colour    = $vehicle['colour'] ? ucwords(strtolower($vehicle['colour'])) : NULL;
		$this->trim      = $vehicle['trim'] ?? NULL;
		$this->notes     = $vehicle['notes'] ?? NULL;
	}

	private function formatPlate(string $plate): string {
		// Try to figure out the plate format
		// Portuguese plates are in sequence with characters and numbers dashes are optional
		// UK plates can be AA00 AAA or AA00 AAAA
		// Swiss plates are 00-0000 or VD 12345

		
		return strtoupper($plate);
	}

	public function getOdometer(): string {
		return $this->odometer == 0 ? 'N/D' : $this->odometer;
	}

	public function getManufactureYear(): string {
		return $this->year ?? 'N/D';
	}

	public function getManufactureMonth(): string {
		return str_pad($this->month, 2, '0', STR_PAD_LEFT) ?? 'N/D';
	}

	public function getManufactureDate(): string {
		return $this->getManufactureYear() . ($this->month ? '/' . $this->getManufactureMonth() : '');
	}

	public function getColour(): string {
		return $this->colour ?? 'N/D';
	}

	public function getTrim(): string {
		return $this->trim ?? 'N/D';
	}

	public function getNotes(): string {
		return $this->notes ?? 'Não existem notas.';
	}

	public function __toString(): string {
		return "{$this->brand} {$this->model} ({$this->getManufactureDate()}, {$this->colour}, {$this->getTrim()})";
	}
}