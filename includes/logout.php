<?php
session_start();

// Destruction de toutes les variables de session
$_SESSION = array();

// Destruction du cookie de session
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destruction de la session
session_destroy();

// Redirection vers la page d'accueil
header('Location: ../index.php');
exit();
?> 