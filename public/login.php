<?php
session_start();

if (isset($_SESSION['user'])) header('Location: index.php');

include '../database.php';
include 'header.php';

if (isset($_POST['submit'])) {
    $email = $db->real_escape_string($_POST['email']);
    $pin   = $db->real_escape_string($_POST['pin']);

    $result = $db->query("SELECT * FROM users WHERE email = '$email' AND pin = '$pin';");

    if ($result->num_rows > 0) {
        $_SESSION['user'] = $result->fetch_assoc();
        header('Location: index.php');
    } else {
        echo '<div class="notification is-danger">E-mail ou PIN inválidos.</div>';
    }
} else {
echo <<<HTML
<section class="section">
    <div class="container is-max-desktop">
        <h1 class="title">Login</h1>
        <form method="post">
            <div class="field">
                <div class="control">
                    <input class="input" type="email" name="email" maxlength="50" placeholder="Email" value="flavioaspereira@gmail.com" required>
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <input class="input" type="password" name="pin" maxlength="4" placeholder="PIN de 4 Dígitos" value="0000" required>
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <button class="button" name="submit">Entrar</button>
                </div>
            </div>
        </form>
    </div>
</section>
HTML;
}
include 'footer.php';
?>