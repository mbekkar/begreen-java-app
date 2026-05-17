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

// ── Ajout d'un ami ────────────────────────────────────────────────────────────
if (isset($_POST['pseudo_ami'])) {

    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Erreur de sécurité. Veuillez recharger la page.');
    }

    $pseudo_ami = htmlspecialchars(trim($_POST['pseudo_ami']));
    $query = $connexion->prepare("SELECT id_utilisateur FROM utilisateur WHERE pseudo = :pseudo");
    $query->execute(['pseudo' => $pseudo_ami]);
    $ami = $query->fetch();

    if ($ami) {
        $id_ami = (int) $ami['id_utilisateur'];

        if ($id_ami === $id_utilisateur) {
            $message_ami = "Vous ne pouvez pas vous ajouter vous-même.";
        } else {
            // Vérifier si l'amitié existe déjà
            $query = $connexion->prepare("
                SELECT COUNT(*) FROM amis
                WHERE (id_utilisateur1 = :uid1 AND id_utilisateur2 = :uid2)
                   OR (id_utilisateur1 = :uid2b AND id_utilisateur2 = :uid1b)
            ");
            $query->execute([
                ':uid1'  => $id_utilisateur, ':uid2'  => $id_ami,
                ':uid1b' => $id_ami,         ':uid2b' => $id_utilisateur,
            ]);

            if ($query->fetchColumn()) {
                $message_ami = "Cet utilisateur est déjà dans votre liste d'amis.";
            } else {
                $query = $connexion->prepare("INSERT INTO amis (id_utilisateur1, id_utilisateur2, statut) VALUES (:uid1, :uid2, 'en attente')");
                $query->execute([':uid1' => $id_utilisateur, ':uid2' => $id_ami]);
                $message_ami = "Demande d'ami envoyée avec succès.";
            }
        }
    } else {
        $message_ami = "Aucun utilisateur trouvé avec ce pseudo.";
    }
}

// ── Accepter une demande d'ami ────────────────────────────────────────────────
if (isset($_POST['accepter_demande'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Erreur de sécurité.');
    }
    $id_amitie = (int) $_POST['id_amitie'];
    $query = $connexion->prepare("UPDATE amis SET statut = 'accepte' WHERE id_amitie = :id AND id_utilisateur2 = :uid");
    $query->execute([':id' => $id_amitie, ':uid' => $id_utilisateur]);
}

// ── Refuser une demande d'ami ─────────────────────────────────────────────────
if (isset($_POST['refuser_demande'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Erreur de sécurité.');
    }
    $id_amitie = (int) $_POST['id_amitie'];
    $query = $connexion->prepare("DELETE FROM amis WHERE id_amitie = :id AND id_utilisateur2 = :uid");
    $query->execute([':id' => $id_amitie, ':uid' => $id_utilisateur]);
}

// ── Récupérer la liste d'amis ─────────────────────────────────────────────────
$query_amis = $connexion->prepare("
    SELECT utilisateur.* FROM utilisateur
    INNER JOIN amis ON (
        (utilisateur.id_utilisateur = amis.id_utilisateur1 AND amis.id_utilisateur2 = :uid  AND amis.statut = 'accepte')
     OR (utilisateur.id_utilisateur = amis.id_utilisateur2 AND amis.id_utilisateur1 = :uid2 AND amis.statut = 'accepte')
    )
");
$query_amis->execute([':uid' => $id_utilisateur, ':uid2' => $id_utilisateur]);
$liste_amis = $query_amis->fetchAll();

// ── Récupérer les demandes en attente ─────────────────────────────────────────
$query_demandes = $connexion->prepare("
    SELECT utilisateur.*, amis.id_amitie FROM utilisateur
    INNER JOIN amis ON (
        utilisateur.id_utilisateur = amis.id_utilisateur1
        AND amis.id_utilisateur2 = :uid
        AND amis.statut = 'en attente'
    )
");
$query_demandes->execute([':uid' => $id_utilisateur]);
$liste_demandes = $query_demandes->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Amis - BeGreen</title>
    <link rel="stylesheet" href="CSS/amis.css">
</head>
<body>
    <div class="container">
        <h2>Ajouter un ami</h2>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <label for="pseudo_ami">Pseudo de l'ami :</label>
            <input type="text" id="pseudo_ami" name="pseudo_ami" required>
            <button type="submit">Ajouter</button>
        </form>
        <?php if (isset($message_ami)): ?>
            <p style="color: <?php echo strpos($message_ami, 'succès') !== false ? 'green' : 'red'; ?>;">
                <?php echo htmlspecialchars($message_ami); ?>
            </p>
        <?php endif; ?>

        <h2>Liste d'amis</h2>
        <ul>
            <?php foreach ($liste_amis as $ami): ?>
                <li><?php echo htmlspecialchars($ami['pseudo']); ?></li>
            <?php endforeach; ?>
            <?php if (empty($liste_amis)): ?>
                <li>Aucun ami pour le moment.</li>
            <?php endif; ?>
        </ul>

        <h2>Demandes en attente</h2>
        <ul>
            <?php foreach ($liste_demandes as $demande): ?>
                <li>
                    <?php echo htmlspecialchars($demande['pseudo']); ?>
                    <form method="POST" action="" style="display:inline">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="id_amitie" value="<?php echo (int) $demande['id_amitie']; ?>">
                        <button type="submit" name="accepter_demande">Accepter</button>
                        <button type="submit" name="refuser_demande">Refuser</button>
                    </form>
                </li>
            <?php endforeach; ?>
            <?php if (empty($liste_demandes)): ?>
                <li>Aucune demande en attente.</li>
            <?php endif; ?>
        </ul>

        <a href="accueil.php">Retour à l'accueil</a>
    </div>
</body>
</html>
