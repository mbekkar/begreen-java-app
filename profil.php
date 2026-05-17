<?php
session_start();
require_once 'config.php';

// Rediriger si non connecté
if (!isset($_SESSION['utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

// Générer le token CSRF si absent
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$connexion      = getDB();
$id_utilisateur = (int) $_SESSION['utilisateur']['id_utilisateur'];

// ── Déconnexion via POST (plus sûr que GET) ───────────────────────────────────
if (isset($_POST['deconnexion'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Erreur de sécurité.');
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    header('Location: connexion.php');
    exit;
}

// ── Changement de ville ────────────────────────────────────────────────────────
if (isset($_POST['changer_ville'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Erreur de sécurité.');
    }

    $villes_autorisees = ['Lyon', 'Paris', 'Bordeaux', 'Marseille'];
    $nouvelle_ville    = $_POST['nouvelle_ville'] ?? '';

    if (in_array($nouvelle_ville, $villes_autorisees, true)) {
        $update_ville = $connexion->prepare("UPDATE utilisateur SET ville = :ville WHERE id_utilisateur = :uid");
        $update_ville->execute([':ville' => $nouvelle_ville, ':uid' => $id_utilisateur]);
        $_SESSION['utilisateur']['ville'] = $nouvelle_ville;

        // Remettre les points à 0 lors du changement de ville
        $reset_points = $connexion->prepare("UPDATE utilisateur SET points = 0 WHERE id_utilisateur = :uid");
        $reset_points->execute([':uid' => $id_utilisateur]);
    }

    header('Location: accueil.php');
    exit;
}

// ── Récupérer les infos de l'utilisateur ──────────────────────────────────────
$req = $connexion->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = :uid");
$req->execute([':uid' => $id_utilisateur]);
$utilisateur = $req->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - BeGreen</title>
    <link rel="stylesheet" href="CSS/profil.css">
</head>
<body>

<nav>
    <ul class="menu">
        <li><a href="accueil.php">Accueil</a></li>
        <li><a href="classement.php">Classement</a></li>
    </ul>
</nav>

<div class="profil">
    <h2>Profil</h2>
    <p>Bienvenue, <?php echo htmlspecialchars($utilisateur['pseudo']); ?> !</p>
    <p>Votre email est : <?php echo htmlspecialchars($utilisateur['email']); ?></p>
    <p>Votre ville : <?php echo htmlspecialchars($utilisateur['ville']); ?></p>
    <p>Vos points : <strong><?php echo (int) $utilisateur['points']; ?></strong></p>

    <!-- Formulaire pour changer de ville -->
    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <label for="nouvelle_ville">Changer de ville :</label>
        <select name="nouvelle_ville" id="nouvelle_ville">
            <option value="Lyon"      <?php echo $utilisateur['ville'] === 'Lyon'      ? 'selected' : ''; ?>>Lyon</option>
            <option value="Paris"     <?php echo $utilisateur['ville'] === 'Paris'     ? 'selected' : ''; ?>>Paris</option>
            <option value="Bordeaux"  <?php echo $utilisateur['ville'] === 'Bordeaux'  ? 'selected' : ''; ?>>Bordeaux</option>
            <option value="Marseille" <?php echo $utilisateur['ville'] === 'Marseille' ? 'selected' : ''; ?>>Marseille</option>
        </select>
        <button type="submit" name="changer_ville">Changer</button>
    </form>

    <!-- Déconnexion via POST (sécurisé) -->
    <form method="POST" action="" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
        <button type="submit" name="deconnexion">Se déconnecter</button>
    </form>
</div>

</body>
</html>
