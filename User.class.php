<?php
/* 
    CREATE TABLE `users` (
        `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
        `email` VARCHAR(254) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
        `first_name` TINYTEXT NOT NULL COLLATE 'utf8mb4_general_ci',
        `last_name` TINYTEXT NOT NULL COLLATE 'utf8mb4_general_ci',
        `nif` INT(9) UNSIGNED NULL DEFAULT NULL COMMENT 'Número de Identificação Fiscal',
        `address` LONGTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_bin',
        `phone` VARCHAR(50) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
        `pin` SMALLINT(4) UNSIGNED ZEROFILL NULL DEFAULT NULL,
        `level` ENUM('CUSTOMER','HELP','ADMIN') NULL DEFAULT 'CUSTOMER' COLLATE 'utf8mb4_general_ci',
        `active` TINYINT(1) UNSIGNED NULL DEFAULT NULL,
        PRIMARY KEY (`id`) USING BTREE,
        UNIQUE INDEX `email` (`email`) USING BTREE,
        UNIQUE INDEX `nif` (`nif`) USING BTREE
    )
    COLLATE='utf8mb4_general_ci'
    ENGINE=InnoDB
    AUTO_INCREMENT=3
    ;
 */
enum UserLevel: string {
    case Customer = 'CUSTOMER';
    case Helper   = 'HELPER';
    case Admin    = 'ADMIN';

    static function toArray(): array {
        return [
            UserLevel::Customer,
            UserLevel::Helper,
            UserLevel::Admin,
        ];
    }
}

class User {
    public int $id;
    public ?string $email;
    public string $first_name;
    public string $last_name;
    private ?int $nif;
    private ?string $address;
    public string $phone;
    public UserLevel $level;
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
        $this->active     = (bool) $user['active'];
    }

    public function getEmail(): string {
        return $this->email ?? 'N/D';
    }

    public function getNif(): string {
        return $this->nif ? sprintf('%09d', $this->nif) : 'N/D';
    }

    public function getAddress(): string {
        return $this->address ?? 'N/D';
    }

    public function getPhoneNumber(): string {
        return $this->phone ?? 'N/D';
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