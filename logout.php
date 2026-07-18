<?php
require_once 'config/database.php';

// Appel de la déconnexion centralisée (nettoyage session + cookie remember_me)
logout_user();

header("Location: login.php");
exit;
?>
