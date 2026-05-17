<?php
session_start();
require_once 'config.php';

// Générer le token CSRF si absent
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['valider'])) {

    // Vérification CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Erreur de sécurité. Veuillez recharger la page.');
    }

    if (!empty($_POST['pseudo']) && !empty($_POST['mdp'])) {
        $connexion = getDB();

        $pseudo = htmlspecialchars(trim($_POST['pseudo']));
        $mdp    = $_POST['mdp'];

        $req = $connexion->prepare("SELECT * FROM utilisateur WHERE pseudo = :pseudo");
        $req->execute(['pseudo' => $pseudo]);
        $utilisateur = $req->fetch();

        if ($utilisateur && password_verify($mdp, $utilisateur['password'])) {
            // Régénérer l'ID de session pour éviter la fixation de session
            session_regenerate_id(true);
            $_SESSION['utilisateur'] = $utilisateur;
            header('Location: accueil.php');
            exit;
        } else {
            // Message volontairement vague : ne pas révéler si c'est le pseudo ou le mdp qui est faux
            $message = "Pseudo ou mot de passe incorrect.";
        }
    } else {
        $message = "Tous les champs sont obligatoires.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - BeGreen</title>
    <link rel="stylesheet" href="CSS/index.css">
</head>
<body>
    <div class="header">
        <h1>BeGreen</h1>
        <div class="logo">
            <img src="BeGreen_logo.png" alt="Logo BeGreen">
        </div>
    </div>
    <div class="container">
        <h2>Connexion</h2>
        <form method="POST" action="">
            <!-- Token CSRF (sécurité) -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="input-group">
                <label for="pseudo">Pseudo :</label>
                <input type="text" id="pseudo" name="pseudo" required>
            </div>
            <div class="input-group">
                <label for="mdp">Mot de passe :</label>
                <input type="password" id="mdp" name="mdp" required>
            </div>
            <button type="submit" name="valider">Se connecter</button>
            <?php if (isset($message)) { echo "<p style='color: red;'>$message</p>"; } ?>
        </form>
        <br>
        <div class="inscription-button">
            <a href="index.php">
                <button type="button">Inscription</button>
            </a>
        </div>
    </div>
</body>
</html>
