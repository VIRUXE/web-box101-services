<?php
include '../User.class.php';

/*
    This page will display data for a specific car, by license plate (matricula)

    CREATE TABLE `vehicles` (
        `matricula` VARCHAR(9) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
        `year` YEAR NULL DEFAULT NULL,
        `month` TINYINT(3) UNSIGNED NULL DEFAULT NULL,
        `brand` VARCHAR(50) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
        `model` VARCHAR(50) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
        `colour` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
        `trim` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
        PRIMARY KEY (`matricula`) USING BTREE
    )
    COLLATE='utf8mb4_general_ci'
    ENGINE=InnoDB
    ;
*/
session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

include '../database.php';
include 'header.php';

$matricula = isset($_GET['matricula']) ? $db->real_escape_string($_GET['matricula']) : NULL;

echo <<<HTML
    <section class="section">
        <div class="container">
HTML;
            
if (!$matricula) {
    echo '<div class="notification is-danger">Matrícula não foi fornecida.</div>';
} else {
    include '../Car.class.php';
    $query = $db->query("SELECT * FROM cars WHERE matricula = '$matricula';");
    
    if (!$query->num_rows) {
        echo '<div class="notification is-danger">Matrícula não encontrada.</div>';
    } else {
        $car = new Car($query->fetch_assoc());

        echo <<<HTML
            <h1 class="title">{$car->brand} {$car->model} ({$car->getManufactureDate()}, {$car->colour}, {$car->getTrim()})</h1>
            <div class="subtitle">$matricula</div>
            <hr>
            <a href="iniciar_servico.php?matricula=$matricula" class="button is-success">Iniciar Serviço</a>
            <a href="editar_carro.php?matricula=$matricula" class="button is-info">Editar Carro</a>
            <a href="eliminar_carro.php?matricula=$matricula" class="button is-danger">Eliminar Carro</a>
            <a href="carros.php" class="button is-link">Voltar</a>
            <hr>
            <h2 class="h2">Serviços</h2>
            <table class="table is-fullwidth">
                <thead>
                    <tr>
                        <th style="width: 10%;">Data</th>
                        <th style="width: 50%;">Descrição</th>
                        <th style="width: 10%;">Preço</th>
                    </tr>
                </thead>
                <tbody>
        HTML;

        /* 
        CREATE TABLE `car_services` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `matricula` VARCHAR(9) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
            `date_created` DATETIME NOT NULL DEFAULT current_timestamp(),
            `created_by` INT(10) UNSIGNED NOT NULL,
            `description` TEXT NULL DEFAULT NULL COMMENT 'General description of the service' COLLATE 'utf8mb4_general_ci',
            `start_date` DATETIME NOT NULL DEFAULT current_timestamp(),
            `end_date` DATETIME NULL DEFAULT NULL,
            `active` TINYINT(1) UNSIGNED NOT NULL DEFAULT '1' COMMENT 'Deleted?',
            `state` ENUM('STALE','CANCELLED','PENDING INTERNAL APPROVAL','PENDING APPROVAL','ACCEPTED') NOT NULL DEFAULT 'STALE' COLLATE 'utf8mb4_general_ci',
            PRIMARY KEY (`id`) USING BTREE,
            INDEX `matricula` (`matricula`) USING BTREE
        )
        COLLATE='utf8mb4_general_ci'
        ENGINE=InnoDB
        ;

        CREATE TABLE `car_service_items` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `service_id` INT(11) UNSIGNED NOT NULL,
            `service_item_type_id` INT(11) UNSIGNED NOT NULL,
            `added_by` INT(11) UNSIGNED NOT NULL,
            `status` ENUM('NOT STARTED','STARTED','PAUSED','FAILED','SUCCESS') NOT NULL DEFAULT 'NOT STARTED' COLLATE 'utf8mb4_general_ci',
            `start_date` DATETIME NOT NULL DEFAULT current_timestamp(),
            `start_notes` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            `end_date` DATETIME NULL DEFAULT NULL,
            `end_notes` TEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            PRIMARY KEY (`id`) USING BTREE,
            INDEX `service_id` (`service_id`) USING BTREE,
            CONSTRAINT `service_id` FOREIGN KEY (`service_id`) REFERENCES `car_services` (`id`) ON UPDATE NO ACTION ON DELETE NO ACTION
        )
        COLLATE='utf8mb4_general_ci'
        ENGINE=InnoDB
        ;

        */

        $services = $db->query("SELECT * FROM car_services WHERE matricula = '$matricula';");

        while ($service = $services->fetch_object()) {
            echo <<<HTML
                <tr>
                    <td>{$service->start_date}</td>
                    <td>{$service->description}</td>
                    <td>{$service->price}</td>
                </tr>
            HTML;

            $service_id = $service->id;
            $items = $db->query("SELECT * FROM car_service_items WHERE service_id = $service_id;");

            while ($item = $items->fetch_object()) {
                echo <<<HTML
                    <tr>
                        <td>{$item->start_date}</td>
                        <td>{$item->status}</td>
                        <td>{$item->start_notes}</td>
                    </tr>
                HTML;
            }
        }

        echo <<<HTML
                </tbody>
            </table>
        HTML;
    }
}

echo <<<HTML
        </div>
    </section>
HTML;

include 'footer.php';
?>