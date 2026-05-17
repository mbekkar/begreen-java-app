<?php
// ── Configuration base de données ────────────────────────────────────────────
// Modifiez ces valeurs selon votre environnement local.
// Ce fichier est dans .gitignore — ne jamais commiter vos vrais identifiants.

define('DB_HOST', 'localhost');
define('DB_NAME', 'siteweb');
define('DB_USER', 'root');
define('DB_PASS', '');

// ── Connexion PDO (singleton) ─────────────────────────────────────────────────
function getDB(): PDO {
    static $connexion = null;
    if ($connexion === null) {
        try {
            $connexion = new PDO(
                'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8',
                DB_USER,
                DB_PASS
            );
            $connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $connexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Ne jamais afficher les détails de l'erreur en production
            error_log('Erreur DB : ' . $e->getMessage());
            die('Erreur de connexion à la base de données. Veuillez réessayer plus tard.');
        }
    }
    return $connexion;
}
