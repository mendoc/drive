# Explorateur de fichiers PHP - Documentation

## État actuel du projet

### Fonctionnalités implémentées ✅

#### Interface utilisateur
- **Design Windows moderne** : Interface avec fenêtre flottante, barre de titre, contrôles
- **Barre d'outils** : Navigation (précédent/suivant/actualiser), vue grille/liste, recherche
- **Barre latérale** : Favoris (Desktop, Downloads, etc.) et informations disque
- **Vue responsive** : S'adapte aux écrans mobiles

#### Navigation
- **Breadcrumb cliquable** : Navigation par chemin dans la barre d'adresse  
- **Double vue** : Grille (cartes) et Liste (tableau avec colonnes)
- **Préférence sauvegardée** : localStorage maintient le choix grille/liste
- **Tri intelligent** : Dossiers avant fichiers, tri alphabétique

#### Gestion des fichiers
- **Filtrage automatique** : Masque les fichiers commençant par un point (`.git`, `.htaccess`, etc.)
- **Menu contextuel** : Trois points horizontaux sur chaque élément (grille et liste)
- **Système de masquage** : Option "Masquer" dans le menu contextuel
- **Fichier .hidden** : Stockage persistant des éléments masqués

#### Interface moderne
- **Animations** : Animate.css avec durée 0.3s pour les modales
- **Modale de confirmation** : Interface élégante pour confirmer le masquage
- **Icônes** : Font Awesome + emojis pour les types de fichiers
- **Effets hover** : Animations et transitions fluides

### Architecture technique

#### Classes PHP
- **HiddenManager** : Gestion du fichier .hidden et filtrage
- **FileExplorer** : Navigation, lecture dossiers, formatage, icônes

#### Fichiers
- `index.php` : Application principale (HTML + PHP + JS)
- `style.css` : Styles séparés pour maintenance
- `.hidden` : Fichier de configuration des éléments masqués

### Sécurité
- **Protection traversal** : Sécurisation contre l'accès aux dossiers parents
- **Validation des chemins** : Vérification des accès autorisés
- **Échappement HTML** : Protection XSS sur tous les affichages

### Fonctionnalités à développer

#### Prochaines étapes suggérées
1. **Créer un dossier** : Bouton dans l'accès rapide + modale de saisie
2. **Upload de fichiers** : Drag & drop ou bouton upload
3. **Renommer** : Menu contextuel étendu
4. **Supprimer** : Avec corbeille fonctionnelle
5. **Propriétés** : Modale d'informations détaillées

### Notes de développement

#### Technologies utilisées
- **Backend** : PHP 8+ avec classes orientées objet
- **Frontend** : HTML5, CSS3 avec variables, JavaScript ES6+
- **Animations** : Animate.css 4.1.1
- **Icônes** : Font Awesome 6.0.0

#### Serveur de développement
```bash
php -S localhost:8000
```

#### Structure des données
```php
// Format des éléments de fichiers/dossiers
[
    'name' => 'nom_fichier',
    'type' => 'file|directory', 
    'path' => '/chemin/complet',
    'size' => 123456, // bytes pour files, '' pour directories
    'modified' => 1640995200 // timestamp
]
```

### Problèmes connus
- Aucun problème critique identifié
- Toutes les fonctionnalités principales opérationnelles

---
*Dernière mise à jour : 2025-09-24*
*État : Stable et fonctionnel*