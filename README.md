# 🌿 BeGreen — Application Web PHP

> Application web de gamification éco-responsable.  
> Les utilisateurs réalisent des défis écologiques, gagnent des points et se classent avec leurs amis.

**Auteurs :** Mounir Bekkar & équipe  
**Université :** Université Lumière Lyon 2 — Licence Informatique

---

## ✨ Fonctionnalités

| Page | Fonctionnalité |
|------|---------------|
| `index.php` | Inscription avec vérification pseudo/email |
| `connexion.php` | Connexion sécurisée (bcrypt + session) |
| `accueil.php` | Défis écologiques filtrés par ville, système de points |
| `classement.php` | Classement entre amis |
| `amis.php` | Ajouter, accepter, refuser des amis |
| `profil.php` | Changer de ville, se déconnecter |

---

## 🔐 Sécurité

- Mots de passe hashés avec `password_hash()` (bcrypt)
- Requêtes SQL avec PDO et paramètres liés (anti-injection)
- Protection CSRF sur tous les formulaires POST
- `session_regenerate_id()` à la connexion
- Message d'erreur générique à la connexion (ne révèle pas si c'est le pseudo ou le mot de passe qui est faux)
- Déconnexion via POST (et non GET)
- `htmlspecialchars()` sur toutes les sorties utilisateur

---

## 🚀 Installation

### Prérequis
- PHP 8.0+
- MySQL 8.0+ ou MariaDB
- Apache avec `mod_rewrite` (ou XAMPP / WAMP en local)

### Étapes

**1. Cloner le dépôt**
```bash
git clone https://github.com/mbekkar/begreen.git
cd begreen
```

**2. Créer la base de données**
```bash
mysql -u root -p < database/schema.sql
```

**3. Configurer la connexion**

Copiez et éditez le fichier de config :
```bash
cp www/config.example.php www/config.php
```
Puis modifiez `www/config.php` avec vos identifiants MySQL.

**4. Lancer en local**

Placez le dossier `www/` dans votre répertoire Apache (`htdocs/` ou `www/`), ou utilisez PHP built-in :
```bash
cd www
php -S localhost:8000
```

**5. Ouvrir dans le navigateur**
```
http://localhost:8000
```

---

## 🗂️ Structure

```
begreen/
├── www/
│   ├── config.php          ← Connexion DB (dans .gitignore)
│   ├── index.php           ← Inscription
│   ├── connexion.php       ← Connexion
│   ├── accueil.php         ← Défis + points
│   ├── classement.php      ← Classement amis
│   ├── amis.php            ← Gestion amis
│   ├── profil.php          ← Profil + déconnexion
│   ├── BeGreen_logo.png
│   └── CSS/
│       ├── index.css
│       ├── accueil.css
│       ├── classement.css
│       ├── amis.css
│       └── profil.css
└── database/
    └── schema.sql          ← Schéma complet + données de test
```

---

## 🗄️ Base de données

```
utilisateur  → id, pseudo, email, password (bcrypt), ville, points
defis        → id, defi, description, points, ville
realisation  → id_utilisateur, id_defi, etat (0/1)
amis         → id_utilisateur1, id_utilisateur2, statut (en attente / accepte)
```
