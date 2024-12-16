<?php
enum UserLevel: string {
    case Customer = 'CUSTOMER';
    case Helper   = 'HELPER';
    case Admin    = 'ADMIN';

    static function toArray(): array {
        return [ UserLevel::Customer, UserLevel::Helper, UserLevel::Admin ];
    }
}

class User {
    public int $id;
    public ?string $email;
    public string $first_name;
    public string $last_name;
    public ?int $nif;
    public ?string $address;
    public string $phone;
    public UserLevel $level;
    public int $pin;
    public ?string $notes;
    public bool $active;

    public function __construct(array $user) {
        if (count($user) === 0) throw new Exception('User not found.');

        $this->id         = $user['id'];
        $this->email      = $user['email'];
        $this->first_name = ucwords(strtolower($user['first_name']));
        $this->last_name  = ucwords(strtolower($user['last_name']));
        $this->nif        = $user['nif'];
        $this->address    = $user['address'];
        $this->phone      = $user['phone'];
        $this->level      = UserLevel::tryFrom($user['level']) ?? UserLevel::Customer;
        $this->pin        = str_pad($user['pin'], 4, '0', STR_PAD_LEFT);
        $this->notes      = $user['notes'] ?? NULL;
        $this->active     = (bool) $user['active'];
    }

    public function getEmail(): string {
        return $this->email ?? 'N/D';
    }

    public function renderEmail(): string {
        return $this->email ? '<a href="mailto:'.$this->email.'">'.$this->email.'</a>' : 'N/D';
    }

    public function getNif(): string {
        return $this->nif ? sprintf('%09d', $this->nif) : 'N/D';
    }

    public function getAddress(): string {
        return $this->address ?? 'N/D';
    }

    public function renderAddress(): string {
        return $this->address ? '<a href="https://maps.google.com/?q='.$this->address.'">'.$this->address.'</a>' : 'N/D';
    }

    public function getPhoneNumber(): string {
        return $this->phone ?? 'N/D';
    }

    public function getNotes(): string {
        return $this->notes ? nl2br($this->notes) : 'Não existem notas.';
    }

    public function getActive(): string {
        return $this->active ? 'Sim' : 'Não';
    }

    public function isCustomer(): bool {
        return $this->level === UserLevel::Customer;
    }

    public function isAdmin(): bool {
        return $this->level === UserLevel::Admin;
    }

    public function getLevelTitle(): string {
        return match ($this->level) {
            UserLevel::Customer => 'Cliente',
            UserLevel::Helper   => 'Ajudante',
            UserLevel::Admin    => 'Administrador',
        };
    }

    public function __toString() {
        return "{$this->first_name} {$this->last_name}";
    }

    static function isLogged(): bool {
        return isset($_SESSION['user']);
    }

    static function getLogged(): ?User {
        return User::isLogged() ? new User($_SESSION['user']) : NULL;
    }
}