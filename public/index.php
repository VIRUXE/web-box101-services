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
        <h1 class="title">Olá $logged_user!</h1><div class="subtitle">{$logged_user->getLevelTitle()}</div>
        </div>
    </section>
HTML;

include 'footer.php';