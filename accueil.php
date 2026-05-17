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

$connexion    = getDB();
$utilisateur  = $_SESSION['utilisateur'];
$utilisateur_id = (int) $utilisateur['id_utilisateur'];

// ── Traitement des défis cochés ───────────────────────────────────────────────
if (isset($_POST['valider'])) {

    // Vérification CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        die('Erreur de sécurité. Veuillez recharger la page.');
    }

    // Récupérer et nettoyer les défis cochés (forcer entier pour éviter l'injection)
    $defis_coches = [];
    if (!empty($_POST['defi_id']) && is_array($_POST['defi_id'])) {
        foreach ($_POST['defi_id'] as $id) {
            $defis_coches[] = (int) $id; // intval() protège contre l'injection SQL
        }
    }

    // Insérer les nouveaux défis cochés
    foreach ($defis_coches as $defi_id) {
        $check = $connexion->prepare("SELECT COUNT(*) FROM realisation WHERE id_utilisateur = :uid AND id_defi = :did");
        $check->execute([':uid' => $utilisateur_id, ':did' => $defi_id]);
        if (!$check->fetchColumn()) {
            $insert = $connexion->prepare("INSERT INTO realisation (id_utilisateur, id_defi, etat) VALUES (:uid, :did, 1)");
            $insert->execute([':uid' => $utilisateur_id, ':did' => $defi_id]);
        }
    }

    // Mettre à jour l'état de TOUS les défis de l'utilisateur
    // Les défis cochés → etat=1, les décochés → etat=0
    if (!empty($defis_coches)) {
        // Maintenant que les IDs sont des entiers, le implode est sûr
        $ids_str = implode(',', $defis_coches);
        $update = $connexion->prepare("UPDATE realisation SET etat = CASE WHEN id_defi IN ($ids_str) THEN 1 ELSE 0 END WHERE id_utilisateur = :uid");
        $update->execute([':uid' => $utilisateur_id]);
    } else {
        // Aucun défi coché → tout remettre à 0
        $reset = $connexion->prepare("UPDATE realisation SET etat = 0 WHERE id_utilisateur = :uid");
        $reset->execute([':uid' => $utilisateur_id]);
    }

    // Recalculer les points de l'utilisateur
    $update_points = $connexion->prepare("
        UPDATE utilisateur
        SET points = (
            SELECT COALESCE(SUM(d.points), 0)
            FROM defis d
            INNER JOIN realisation r ON r.id_defi = d.id_defi
            WHERE r.id_utilisateur = :uid AND r.etat = 1
        )
        WHERE id_utilisateur = :uid2
    ");
    $update_points->execute([':uid' => $utilisateur_id, ':uid2' => $utilisateur_id]);

    header('Location: accueil.php');
    exit;
}

// ── Récupérer les points actuels ──────────────────────────────────────────────
$req_points = $connexion->prepare("SELECT points FROM utilisateur WHERE id_utilisateur = :uid");
$req_points->execute([':uid' => $utilisateur_id]);
$points = $req_points->fetchColumn();

// ── Récupérer les défis de la ville de l'utilisateur ─────────────────────────
$req_defis = $connexion->prepare("SELECT * FROM defis WHERE ville = :ville ORDER BY points ASC");
$req_defis->execute([':ville' => $utilisateur['ville']]);
$defis = $req_defis->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BeGreen</title>
    <link rel="stylesheet" href="CSS/accueil.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }
        .close:hover, .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <nav>
        <ul class="menu">
            <li><a href="#accueil">Accueil</a></li>
            <li><a href="classement.php">Classement</a></li>
            <li><a href="amis.php">Amis</a></li>
            <li>
                <a href="profil.php">
                    <?php echo htmlspecialchars($utilisateur['pseudo']); ?> :
                    <span class="points"><?php echo (int) $points; ?> points</span>
                </a>
            </li>
        </ul>
    </nav>
    <main>
        <section class="defis">
            <h2>Défis Écologiques</h2>
            <form method="POST" action="">
                <!-- Token CSRF (sécurité) -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <ul>
                    <?php foreach ($defis as $defi): ?>
                        <?php
                        // Vérifier si le défi est déjà réalisé par l'utilisateur
                        $check = $connexion->prepare("SELECT COUNT(*) FROM realisation WHERE id_utilisateur = :uid AND id_defi = :did AND etat = 1");
                        $check->execute([':uid' => $utilisateur_id, ':did' => $defi['id_defi']]);
                        $deja_fait = $check->fetchColumn();
                        ?>
                        <li>
                            <input type="checkbox"
                                   name="defi_id[]"
                                   value="<?php echo (int) $defi['id_defi']; ?>"
                                   id="defi<?php echo (int) $defi['id_defi']; ?>"
                                   <?php echo $deja_fait ? 'checked' : ''; ?>>
                            <label for="defi<?php echo (int) $defi['id_defi']; ?>"
                                   data-description="<?php echo htmlspecialchars($defi['description']); ?>">
                                <?php echo htmlspecialchars($defi['defi']); ?> - <span><?php echo (int) $defi['points']; ?> points</span>
                            </label>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <button type="submit" name="valider" class="button">Valider</button>
            </form>
        </section>
    </main>

    <!-- Boîte de dialogue modale pour afficher la description du défi -->
    <div id="myModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p id="defi-description"></p>
        </div>
    </div>

    <script>
        // Afficher la description au clic sur le label
        var labels = document.querySelectorAll('label');
        labels.forEach(function(label) {
            label.addEventListener('click', function(e) {
                e.preventDefault(); // Empêcher de cocher/décocher en cliquant sur la description
                var description = this.dataset.description;
                document.getElementById('defi-description').innerText = description;
                document.getElementById('myModal').style.display = 'block';
            });
        });

        // Fermer la modale
        var closeButton = document.querySelector('.close');
        closeButton.addEventListener('click', function() {
            document.getElementById('myModal').style.display = 'none';
        });

        window.addEventListener('click', function(event) {
            if (event.target == document.getElementById('myModal')) {
                document.getElementById('myModal').style.display = 'none';
            }
        });
    </script>
</body>
</html>
