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

    if (!empty($_POST['pseudo']) && !empty($_POST['email']) && !empty($_POST['mdp']) && !empty($_POST['ville'])) {
        $connexion = getDB();

        $pseudo = htmlspecialchars(trim($_POST['pseudo']));
        $email  = htmlspecialchars(trim($_POST['email']));
        $mdp    = $_POST['mdp'];
        $ville  = $_POST['ville'];

        // Vérifier si le pseudo est déjà pris
        $verif_pseudo = $connexion->prepare("SELECT COUNT(*) FROM utilisateur WHERE pseudo = :pseudo");
        $verif_pseudo->execute(['pseudo' => $pseudo]);
        $pseudo_exists = $verif_pseudo->fetchColumn();

        // Vérifier si l'e-mail est déjà pris
        $verif_email = $connexion->prepare("SELECT COUNT(*) FROM utilisateur WHERE email = :email");
        $verif_email->execute(['email' => $email]);
        $email_exists = $verif_email->fetchColumn();

        if ($pseudo_exists) {
            $message_pseudo = "Ce pseudo est déjà pris. Veuillez en choisir un autre.";
        }

        if ($email_exists) {
            $message_email = "Cet email est déjà associé à un compte. Veuillez en choisir un autre.";
        }

        if (strlen($mdp) < 6) {
            $message_mdp = "Le mot de passe est trop court (minimum 6 caractères).";
        }

        if (!$pseudo_exists && !$email_exists && strlen($mdp) >= 6) {
            $mdp_hash = password_hash($mdp, PASSWORD_DEFAULT);
            $req = $connexion->prepare("INSERT INTO utilisateur (pseudo, email, password, ville) VALUES (:pseudo, :email, :mdp, :ville)");
            $req->execute([
                'pseudo' => $pseudo,
                'email'  => $email,
                'mdp'    => $mdp_hash,
                'ville'  => $ville,
            ]);
            header('Location: connexion.php');
            exit;
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
    <title>Inscription - BeGreen</title>
    <link rel="stylesheet" href="CSS/index.css">
    <style>
        .error-message { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h1>BeGreen</h1>
        <div class="logo">
            <img src="BeGreen_logo.png" alt="Logo BeGreen">
        </div>
    </div>
    <div class="container">
        <h2>Inscription</h2>
        <form method="POST" action="">
            <!-- Token CSRF (sécurité) -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="input-group">
                <label for="pseudo">Pseudo :</label>
                <input type="text" id="pseudo" name="pseudo" required
                       value="<?php echo isset($_POST['pseudo']) ? htmlspecialchars($_POST['pseudo']) : ''; ?>">
                <?php if (isset($message_pseudo)) { echo "<p class='error-message'>$message_pseudo</p>"; } ?>
            </div>
            <div class="input-group">
                <label for="email">Email :</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <?php if (isset($message_email)) { echo "<p class='error-message'>$message_email</p>"; } ?>
            </div>
            <div class="input-group">
                <label for="mdp">Mot de passe :</label>
                <input type="password" id="mdp" name="mdp" required>
                <?php if (isset($message_mdp)) { echo "<p class='error-message'>$message_mdp</p>"; } ?>
            </div>
            <div class="input-group">
                <label for="ville">Ville :</label>
                <select id="ville" name="ville" required>
                    <option value="Lyon">Lyon</option>
                    <option value="Paris">Paris</option>
                    <option value="Bordeaux">Bordeaux</option>
                    <option value="Marseille">Marseille</option>
                </select>
            </div>
            <button type="submit" name="valider">S'inscrire</button>
            <?php if (isset($message)) { echo "<p class='error-message'>$message</p>"; } ?>
        </form>
        <div class="connexion-button">
            <br>
            <a href="connexion.php">
                <button type="button">Se connecter</button>
            </a>
        </div>
    </div>
</body>
</html>
