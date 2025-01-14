<?php
declare(strict_types=1);

enum ServiceState: string {
    case PENDING           = 'PENDING';
    case AWAITING_APPROVAL = 'AWAITING_APPROVAL';
    case APPROVED          = 'APPROVED';
    case IN_PROGRESS       = 'IN_PROGRESS';
    case COMPLETED         = 'COMPLETED';
    case CANCELLED         = 'CANCELLED';

    public function label(): string {
        return match($this) {
            self::PENDING           => 'Pendente',
            self::AWAITING_APPROVAL => 'A Aguardar Aprovação',
            self::APPROVED          => 'Aprovado',
            self::IN_PROGRESS       => 'Em Progresso',
            self::COMPLETED         => 'Concluído',
            self::CANCELLED         => 'Cancelado'
        };
    }

    public function color(): string {
        return match($this) {
            self::PENDING           => 'is-light',
            self::AWAITING_APPROVAL => 'is-info',
            self::APPROVED          => 'is-success',
            self::IN_PROGRESS       => 'is-warning',
            self::COMPLETED         => 'is-success',
            self::CANCELLED         => 'is-danger'
        };
    }
}
