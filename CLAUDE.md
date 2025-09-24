# Explorateur de fichiers PHP - Documentation

## État actuel du projet

### Fonctionnalités implémentées ✅

#### Interface utilisateur
- **Design Windows moderne** : Interface avec fenêtre flottante, barre de titre, contrôles
- **Barre d'outils** : Navigation (précédent/suivant/actualiser), bouton "Nouveau dossier", vue grille/liste, recherche
- **Barre latérale** : Favoris (Desktop, Downloads, etc.) et informations disque
- **Vue responsive** : S'adapte aux écrans mobiles

#### Navigation
- **Breadcrumb cliquable** : Navigation par chemin relatif dans la barre d'adresse
- **Double vue** : Grille (cartes) et Liste (tableau avec colonnes)
- **Préférence sauvegardée** : localStorage maintient le choix grille/liste
- **Tri intelligent** : Dossiers avant fichiers, tri alphabétique
- **Navigation bidirectionnelle** : Entrée et sortie des dossiers avec chemins relatifs

#### Gestion des fichiers
- **Création de dossiers** : Bouton dans la barre d'outils avec modale de saisie ✨
- **Import de fichiers** : Drag & drop + sélection manuelle avec validation complète ✨
- **Upload multiple** : Sélection et import de plusieurs fichiers simultanément
- **Barre de progression** : Suivi temps réel des uploads avec animations
- **Validation sécurisée** : Contrôles côté client et serveur (taille, type MIME, extensions)
- **Gestion des doublons** : Renommage automatique si fichier existe
- **Filtrage automatique** : Masque les fichiers commençant par un point (`.git`, `.htaccess`, etc.)
- **Menu contextuel** : Trois points horizontaux sur chaque élément (grille et liste)
- **Système de masquage** : Option "Masquer" dans le menu contextuel
- **Fichier .hidden** : Stockage persistant des éléments masqués
- **Corbeille fonctionnelle** : Suppression réversible avec déplacement vers corbeille ✨
- **Suppression sécurisée** : Protection des fichiers système, gestion des conflits

#### Interface moderne
- **Animations** : Animate.css avec durée 0.3s pour toutes les modales
- **Modale de confirmation** : Interface élégante pour confirmer le masquage
- **Modale de création** : Interface intuitive pour créer des dossiers ✨
- **Modale d'upload** : Zone drag & drop interactive avec prévisualisation ✨
- **Modale de corbeille** : Confirmation de suppression avec informations détaillées ✨
- **Icônes** : Font Awesome + emojis pour les types de fichiers
- **Effets hover** : Animations et transitions fluides
- **Raccourcis clavier** : Entrée pour confirmer, Échap pour annuler

### Architecture technique

#### Structure modulaire
```
drive/
├── index.php                    # Point d'entrée HTML
├── .explorer/                   # Framework caché (non visible utilisateur)
│   ├── .hidden                  # Configuration éléments masqués
│   ├── trash/                   # Corbeille pour éléments supprimés ✨
│   ├── assets/
│   │   ├── style.css           # Styles CSS séparés
│   │   ├── app.js              # JavaScript modulaire
│   │   └── icons/              # Icônes du projet
│   ├── classes/
│   │   ├── FileExplorer.php    # Navigation et gestion fichiers
│   │   ├── HiddenManager.php   # Système de masquage
│   │   ├── UploadManager.php   # Gestion des uploads de fichiers
│   │   └── TrashManager.php    # Gestion de la corbeille ✨
│   └── includes/
│       ├── config.php          # Configuration centralisée
│       └── handlers.php        # Gestionnaires AJAX et validation
```

#### Classes PHP
- **HiddenManager** : Gestion du fichier .hidden et filtrage
- **FileExplorer** : Navigation, lecture dossiers, formatage, icônes, chemins relatifs
- **UploadManager** : Upload sécurisé, validation MIME, gestion doublons, noms sécurisés
- **TrashManager** : Corbeille avec métadonnées, déplacement sécurisé, renommage automatique ✨
- **Handlers** : Actions AJAX (création dossiers, masquage, upload, corbeille), validation sécurisée

### Sécurité
- **Protection traversal** : Sécurisation contre l'accès aux dossiers parents
- **Validation des chemins** : Vérification des accès autorisés
- **Échappement HTML** : Protection XSS sur tous les affichages
- **Upload sécurisé** : Validation MIME, filtrage extensions, taille limitée (50MB)
- **Types interdits** : Blocage des fichiers exécutables (.php, .exe, .bat, etc.)
- **Protection système** : Impossible de supprimer les fichiers du framework et système
- **Corbeille sécurisée** : Métadonnées de restauration, validation des chemins

### Fonctionnalités à développer

#### Prochaines étapes suggérées
1. **Restaurer depuis la corbeille** : Fonctionnalité pour remettre les éléments à leur place
2. **Vider la corbeille** : Suppression définitive avec confirmation
3. **Renommer** : Menu contextuel étendu pour fichiers et dossiers
4. **Propriétés** : Modale d'informations détaillées (taille, date, permissions)
5. **Aperçu fichiers** : Preview pour images, PDF, texte
6. **Recherche avancée** : Filtres par type, taille, date

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

### Nouvelles fonctionnalités récentes ✨

#### Import de fichiers (24/09/2025)
- **Interface complète** : Modale avec drag & drop et sélection manuelle
- **Upload multiple** : Traitement simultané de plusieurs fichiers
- **Validation avancée** : Côté client et serveur avec messages détaillés
- **Barre de progression** : Suivi en temps réel avec animations
- **Types supportés** : Images, documents, archives, multimédia, code
- **Sécurité renforcée** : Blocage des exécutables, validation MIME
- **Gestion des doublons** : Renommage automatique intelligent
- **Interface responsive** : Adaptation mobile et desktop

#### Corbeille fonctionnelle (24/09/2025) ✨
- **Suppression réversible** : Déplacement vers `.explorer/trash/` au lieu de suppression définitive
- **Métadonnées complètes** : Stockage du chemin d'origine, date de suppression, nom original
- **Interface utilisateur** : Modale de confirmation élégante avec animations
- **Messages de feedback** : Notifications de succès/erreur avec animations
- **Navigation intégrée** : Corbeille accessible via l'accès rapide
- **Sécurité avancée** : Protection des fichiers système, gestion des conflits de noms
- **Architecture modulaire** : Classe TrashManager dédiée et handler AJAX spécialisé

### Problèmes connus
- Aucun problème critique identifié
- Toutes les fonctionnalités principales opérationnelles
- Upload de fichiers testé et fonctionnel ✅
- Corbeille fonctionnelle testée et opérationnelle ✅

---
*Dernière mise à jour : 2025-09-24*
*État : Stable et fonctionnel - Corbeille fonctionnelle ajoutée*