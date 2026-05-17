<?php
// session_start() DOIT être en haut, avant tout HTML
session_start();
require_once 'config.php';

// Rediriger si non connecté
if (!isset($_SESSION['utilisateur'])) {
    header('Location: connexion.php');
    exit;
}

$connexion      = getDB();
$utilisateur    = $_SESSION['utilisateur'];
$id_utilisateur = (int) $utilisateur['id_utilisateur'];

// ── Récupérer les points actuels de l'utilisateur connecté ───────────────────
$req_points = $connexion->prepare("SELECT points FROM utilisateur WHERE id_utilisateur = :uid");
$req_points->execute([':uid' => $id_utilisateur]);
$points_nav = $req_points->fetchColumn();

// ── Récupérer la liste des amis acceptés ─────────────────────────────────────
$query_amis = $connexion->prepare("
    SELECT id_utilisateur1, id_utilisateur2
    FROM amis
    WHERE (id_utilisateur1 = :uid OR id_utilisateur2 = :uid2) AND statut = 'accepte'
");
$query_amis->execute([':uid' => $id_utilisateur, ':uid2' => $id_utilisateur]);
$liste_amis = $query_amis->fetchAll();

// Construire la liste des IDs (l'utilisateur + ses amis)
$liste_amis_ids = [$id_utilisateur];
foreach ($liste_amis as $ami) {
    if ((int) $ami['id_utilisateur1'] === $id_utilisateur) {
        $liste_amis_ids[] = (int) $ami['id_utilisateur2'];
    } else {
        $liste_amis_ids[] = (int) $ami['id_utilisateur1'];
    }
}

// ── Calculer les points de chaque ami ─────────────────────────────────────────
$liste_amis_points = [];
foreach ($liste_amis_ids as $ami_id) {
    $query_points = $connexion->prepare("
        SELECT COALESCE(SUM(d.points), 0)
        FROM defis d
        INNER JOIN realisation r ON r.id_defi = d.id_defi
        WHERE r.id_utilisateur = :ami_id AND r.etat = 1
    ");
    $query_points->execute([':ami_id' => $ami_id]);
    $liste_amis_points[$ami_id] = (int) $query_points->fetchColumn();
}

// Trier par points décroissants
arsort($liste_amis_points);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classement - BeGreen</title>
    <link rel="stylesheet" href="CSS/classement.css">
    <style>
        .user { font-weight: bold; }
    </style>
</head>
<body>
    <nav>
        <ul class="menu">
            <li><a href="accueil.php">Accueil</a></li>
            <li><a href="#classement">Classement</a></li>
            <li><a href="amis.php">Amis</a></li>
            <li>
                <a href="profil.php">
                    <?php echo htmlspecialchars($utilisateur['pseudo']); ?> :
                    <span class="points"><?php echo (int) $points_nav; ?> points</span>
                </a>
            </li>
        </ul>
    </nav>
    <main>
        <section class="classement">
            <h2>Classement</h2>
            <ol>
                <?php
                $position = 1;
                foreach ($liste_amis_points as $ami_id => $points):
                    $query_pseudo = $connexion->prepare("SELECT pseudo FROM utilisateur WHERE id_utilisateur = :ami_id");
                    $query_pseudo->execute([':ami_id' => $ami_id]);
                    $pseudo_ami = $query_pseudo->fetchColumn();
                    $user_class = ($ami_id === $id_utilisateur) ? 'user' : '';
                ?>
                    <li class="<?php echo $user_class; ?>">
                        <?php echo $position; ?>. <?php echo htmlspecialchars($pseudo_ami); ?> - <?php echo $points; ?> points
                    </li>
                <?php
                    $position++;
                endforeach;
                ?>
            </ol>
        </section>
    </main>
</body>
</html>
