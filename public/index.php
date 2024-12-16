<?php
include_once '../User.class.php';

session_start();

if (!User::isLogged()) header('Location: login.php');

$logged_user = User::getLogged();

// include '../database.php';
include 'header.php';

echo <<<HTML
    <section class="section">
        <div class="container">
            <h1 class="title">Olá $logged_user!</h1>
            <div class="subtitle">{$logged_user->getLevelTitle()}</div>
            <hr>
            <div class="buttons is-grouped is-centered are-medium">
                <a href="veiculos.php" class="button is-dark">Veículos</a>
                <a href="clientes.php" class="button is-dark">Clientes</a>
                <a href="criar_veiculo.php" class="button is-dark">Criar Veículo</a>
                <a href="criar_cliente.php" class="button is-dark">Criar Cliente</a>
            </div>
            <hr>
            <h1 class="title">Serviços</h1>
            <div class="subtitle">Serviços em Curso</div>
            <div class="block">Sem serviços em curso.</div>
        </div>
    </section>
HTML;

include 'footer.php';