<?php
declare(strict_types=1);

enum ServiceItemState: string {
    case NOT_STARTED = 'NOT_STARTED';
    case STARTED     = 'STARTED';
    case PAUSED      = 'PAUSED';
    case SUCCESS     = 'SUCCESS';
    case FAILED      = 'FAILED';

    public function label(): string {
        return match($this) {
            self::NOT_STARTED => 'Por Iniciar',
            self::STARTED     => 'Em Curso',
            self::PAUSED      => 'Em Pausa',
            self::SUCCESS     => 'Concluído',
            self::FAILED      => 'Falhou'
        };
    }

    public function color(): string {
        return match($this) {
            self::NOT_STARTED => 'is-white',
            self::STARTED     => 'is-info',
            self::PAUSED      => 'is-warning',
            self::SUCCESS     => 'is-success',
            self::FAILED      => 'is-danger'
        };
    }

    public function canTransitionTo(self $newState): bool {
        return match($this) {
            self::NOT_STARTED => $newState === self::STARTED,
            self::STARTED     => in_array($newState, [self::PAUSED, self::SUCCESS, self::FAILED]),
            self::PAUSED      => in_array($newState, [self::STARTED, self::SUCCESS, self::FAILED]),
            self::SUCCESS, self::FAILED => false
        };
    }
}
