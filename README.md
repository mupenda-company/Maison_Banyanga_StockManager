# Bralima Logistique - Application de Gestion Logistique

Application web complète pour la gestion logistique d'un dépôt de boissons Bralima.

## Fonctionnalités

### Modules principaux

- **Approvisionnements** : Réception des produits avec logique d'emballages (déduction automatique des caisses vides, gestion des dettes)
- **Stocks Multi-sites** : Gestion des stocks fixes (entrepôts) et mobiles (véhicules), inventaire, mouvements
- **Ventes** : Enregistrement des ventes avec calcul automatique de la TVA, facturation
- **Clients** : Gestion des clients par zones géographiques, suivi du CA
- **Missions** : Chargement des véhicules, suivi des ventes mobiles, retours de produits
- **Pertes** : Enregistrement des pertes (casse, vol, péremption) avec mise à jour du stock
- **Ristournes** : Calcul automatique des ristournes par paliers de CA
- **Alertes** : Notifications automatiques pour les stocks bas

### Administration

- Gestion des utilisateurs avec rôles (admin, magasinier, vendeur)
- Personnalisation de l'interface (logo, couleurs, paramètres)
- Gestion des zones géographiques

## Technologies

- **Backend** : PHP 8+ avec PDO (MySQL)
- **Frontend** : Tailwind CSS, Alpine.js
- **Architecture** : MVC personnalisé

## Installation

### Prérequis

- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur
- Node.js et npm (pour Tailwind CSS)
- Serveur web (Apache/Nginx)

### Étapes d'installation

1. **Cloner le projet**
```bash
cd c:\Users\james-mat\CascadeProjects\2048
```

2. **Installer les dépendances frontend**
```bash
npm install
```

3. **Compiler les CSS**
```bash
npm run build:css
# ou pour le développement
npm run watch:css
```

4. **Créer la base de données**
- Créer une base de données MySQL nommée `bralima_logistique`
- Importer le fichier `database/schema.sql`

5. **Configurer l'application**
- Vérifier les paramètres dans `config/config.php` :
  - DB_NAME, DB_USER, DB_PASS, DB_HOST
  - APP_NAME, APP_URL

6. **Configurer le serveur web**
- Pointer le document root vers le dossier `public/`
- Activer le module de réécriture d'URL (mod_rewrite pour Apache)

### Configuration Apache (.htaccess)

Le fichier `.htaccess` est déjà configuré dans le dossier `public/`.

### Configuration Nginx

```nginx
server {
    listen 80;
    server_name localhost;
    root /path/to/bralima-logistique/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## Utilisation

### Premier accès

1. Accéder à l'application via l'URL configurée
2. Se connecter avec les identifiants par défaut :
   - **Admin** : `admin` / `admin123`
   - **Magasinier** : `magasinier` / `mag123`
   - **Vendeur** : `vendeur` / `vend123`

⚠️ **Important** : Changer les mots de passe par défaut après la première connexion.

### Rôles et permissions

| Rôle | Permissions |
|------|-------------|
| **Admin** | Accès complet, gestion des utilisateurs et paramètres |
| **Magasinier** | Approvisionnements, stocks, missions, pertes |
| **Vendeur** | Ventes, consultation des stocks et clients |

## Structure du projet

```
bralima-logistique/
├── app/
│   ├── Controllers/     # Contrôleurs
│   ├── Core/           # Classes de base (Database, Model, Controller)
│   ├── Models/         # Modèles métier
│   └── Views/          # Vues (templates PHP)
├── config/
│   └── config.php      # Configuration de l'application
├── database/
│   └── schema.sql      # Schéma de la base de données
├── public/
│   ├── css/            # Fichiers CSS
│   ├── js/             # Fichiers JavaScript
│   ├── uploads/        # Fichiers uploadés (logos, etc.)
│   ├── index.php       # Point d'entrée
│   └── .htaccess       # Configuration Apache
├── tailwind.config.js  # Configuration Tailwind CSS
├── package.json        # Dépendances npm
└── README.md           # Ce fichier
```

## API REST

L'application expose une API REST pour les opérations CRUD :

### Endpoints principaux

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/produits` | Liste des produits |
| POST | `/api/produits` | Créer un produit |
| PUT | `/api/produits/{id}` | Modifier un produit |
| DELETE | `/api/produits/{id}` | Supprimer un produit |
| GET | `/api/stocks` | État des stocks |
| POST | `/api/stocks/transfert` | Transfert de stock |
| POST | `/api/approvisionnements` | Créer un approvisionnement |
| POST | `/api/ventes` | Créer une vente |
| POST | `/api/missions` | Créer une mission |
| POST | `/api/pertes` | Enregistrer une perte |

### Authentification

L'API utilise l'authentification par session. Toutes les requêtes doivent être faites après connexion.

## Logique métier

### Approvisionnements et emballages

Lors d'un approvisionnement de produits pleins :
1. Le stock de pleins est augmenté
2. Les caisses vides sont automatiquement déduites du stock
3. Si le stock de vides est insuffisant, une dette d'emballage est créée

### Missions de vente

1. Chargement du véhicule depuis l'entrepôt principal
2. Ventes pendant la mission (stock mobile)
3. Retour des invendus à la fin de mission
4. Mise à jour automatique des stocks

### Ristournes

Calcul automatique basé sur le CA mensuel des clients :
- Définition de paliers de CA
- Application d'un pourcentage de ristourne par palier
- Génération mensuelle des ristournes

## Support

Pour toute question ou problème, contacter l'administrateur système.

## Licence

Application développée pour Bralima - Tous droits réservés.
"# Stock_Manage_App" 
