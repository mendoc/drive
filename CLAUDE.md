# Explorateur de fichiers PHP - Documentation

## État actuel du projet

### Fonctionnalités implémentées ✅

#### Interface utilisateur
- **Design Windows moderne** : Interface avec fenêtre flottante, barre de titre, contrôles
- **Barre d'outils** : Navigation (précédent/suivant/actualiser), bouton "Nouveau dossier", vue grille/liste, recherche
- **Barre latérale** : Favoris (Desktop, Downloads, etc.) et informations disque
- **Vue responsive** : S'adapte aux écrans mobiles
- **Raccourcissement des noms** : Noms longs tronqués intelligemment avec tooltip ✨

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
- **Renommage de fichiers/dossiers** : Menu contextuel avec modale de saisie et validation ✨
- **Distinction dossiers vides** : Indication visuelle des dossiers vides en vue grille et liste ✨

#### Interface moderne
- **Animations** : Animate.css avec durée 0.3s pour toutes les modales
- **Modale de confirmation** : Interface élégante pour confirmer le masquage
- **Modale de création** : Interface intuitive pour créer des dossiers ✨
- **Modale d'upload** : Zone drag & drop interactive avec prévisualisation ✨
- **Modale de corbeille** : Confirmation de suppression avec informations détaillées ✨
- **Modale de renommage** : Interface intuitive avec pré-remplissage et sélection intelligente ✨
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
- **HiddenManager** : Gestion du fichier .hidden et filtrage, protection automatique des fichiers système
- **FileExplorer** : Navigation, lecture dossiers, formatage, icônes, chemins relatifs, détection dossiers vides
- **UploadManager** : Upload sécurisé, validation MIME, gestion doublons, noms sécurisés
- **TrashManager** : Corbeille avec métadonnées, déplacement sécurisé, renommage automatique ✨
- **FeedbackManager** : Gestion des feedbacks utilisateurs avec statut et réorganisation ✨
- **Handlers** : Actions AJAX (création dossiers, masquage, upload, corbeille, renommage, feedbacks), validation sécurisée

### Sécurité
- **Protection traversal** : Sécurisation contre l'accès aux dossiers parents
- **Validation des chemins** : Vérification des accès autorisés
- **Échappement HTML** : Protection XSS sur tous les affichages
- **Upload sécurisé** : Validation MIME, filtrage extensions, taille limitée (50MB)
- **Types interdits** : Blocage des fichiers exécutables (.php, .exe, .bat, etc.)
- **Protection système** : Impossible de supprimer les fichiers du framework et système
- **Corbeille sécurisée** : Métadonnées de restauration, validation des chemins
- **Renommage sécurisé** : Protection fichiers système, validation noms, gestion conflits
- **Fichiers système protégés** : Masquage automatique de index.php, CLAUDE.md, OngouaSync.php ✨

### Système de Feedbacks ✨

#### Fonctionnalités implémentées (06/10/2025)
- **Bouton Feedbacks dans la sidebar** : Accès rapide au système de gestion des feedbacks
- **Liste des feedbacks** : Affichage en tableau avec date, message et actions
- **Ajout de feedbacks** : Modale intuitive avec compteur de caractères (max 500)
- **Marquage comme traité** : Toggle avec effet visuel barré pour feedbacks complétés
- **Suppression de feedbacks** : Modale de confirmation avant suppression définitive
- **Réorganisation par drag & drop** : Glisser-déposer avec SortableJS pour réordonner
- **Persistance** : Stockage dans `.explorer/feedbacks.json` avec sauvegarde automatique
- **Interface moderne** : Animations, icônes (✓ vert / ⭕ gris / 🗑️ rouge / ⋮⋮ drag)

### Fonctionnalités à développer

#### Prochaines étapes suggérées
1. **Créer un raccourci** : Système de liens symboliques pour fichiers/dossiers (suggestion feedback)
2. **Restaurer depuis la corbeille** : Fonctionnalité pour remettre les éléments à leur place
3. **Vider la corbeille** : Suppression définitive avec confirmation
4. **Propriétés** : Modale d'informations détaillées (taille, date, permissions)
5. **Aperçu fichiers** : Preview pour images, PDF, texte
6. **Recherche avancée** : Filtres par type, taille, date

### Notes de développement

#### Technologies utilisées
- **Backend** : PHP 8+ avec classes orientées objet
- **Frontend** : HTML5, CSS3 avec variables, JavaScript ES6+
- **Animations** : Animate.css 4.1.1
- **Icônes** : Font Awesome 6.0.0
- **Drag & Drop** : SortableJS 1.15.0

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
    'modified' => 1640995200, // timestamp
    'isEmpty' => true // pour directories, indique si le dossier est vide
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

#### Renommage de fichiers/dossiers (04/10/2025) ✨
- **Menu contextuel étendu** : Option "Renommer" avec icône edit positionnée entre masquer/corbeille
- **Modale intelligente** : Pré-remplissage du nom actuel avec sélection automatique (sans extension)
- **Validation complète** : Côté client et serveur avec messages d'erreur détaillés
- **Sécurité renforcée** : Protection fichiers système, validation caractères interdits
- **Gestion des conflits** : Vérification noms existants, empêche les doublons
- **Interface intuitive** : Raccourcis clavier, animations fluides, feedback utilisateur
- **Handler AJAX dédié** : Action 'rename' avec validation et retour JSON structuré

#### Thumbnails d'images (04/10/2025) ✨
- **Prévisualisation intelligente** : Affichage de miniatures pour toutes les images (JPG, PNG, GIF, BMP, WebP)
- **Cache performant** : Stockage persistant dans `.explorer/thumbnails/` avec hash unique par fichier
- **Génération automatique** : Thumbnails créés pendant l'upload et à la demande lors de l'affichage
- **Optimisation mémoire** : Taille standardisée 150x150px avec préservation du ratio d'aspect
- **Interface moderne** : Thumbnails pleine largeur (120px hauteur) dans vue grille, 32x32px en liste
- **Design épuré** : Suppression du padding pour maximiser l'espace d'aperçu des images
- **Navigation préservée** : Rechargement intelligent conservant le dossier actuel après upload
- **Chargement progressif** : Animation de chargement et fallback vers icônes en cas d'erreur
- **API REST sécurisée** : Endpoint `?action=thumbnail&path=filename` avec validation et headers cache
- **Performance** : Headers HTTP optimisés (Cache-Control, Expires) pour éviter les rechargements
- **Détection MIME** : Utilisation du type réel du fichier plutôt que l'extension pour plus de robustesse

#### Distinction visuelle des dossiers vides (06/10/2025) ✨
- **Détection automatique** : Méthode `isDirectoryEmpty()` qui vérifie si un dossier contient des éléments visibles
- **Vue grille** : Label "vide" en petit texte gris italique sous le nom du dossier
- **Icônes grisées** : Opacité réduite (40%) et filtre grayscale pour les dossiers vides
- **Vue liste** : Nom et icône grisés pour une distinction subtile mais claire
- **Effet hover** : Augmentation légère de l'opacité (60%) au survol pour garder l'interactivité
- **Comptage intelligent** : Ne compte que les fichiers/dossiers non masqués pour déterminer si vide
- **Performance** : Vérification effectuée côté serveur lors du chargement du répertoire

#### Protection automatique des fichiers système (06/10/2025) ✨
- **Masquage automatique** : Liste de fichiers système cachés dès l'installation sans intervention utilisateur
- **Fichiers protégés** : index.php, CLAUDE.md, OngouaSync.php masqués automatiquement à la racine
- **Protection permanente** : Impossible de révéler ces fichiers via l'interface utilisateur
- **Indépendant du fichier .hidden** : Gestion par constante PHP dans HiddenManager
- **Sécurité renforcée** : Empêche la suppression accidentelle des fichiers critiques du système
- **Vérification de localisation** : Seuls les fichiers à la racine du projet sont masqués automatiquement
- **Extensibilité** : Ajout facile de nouveaux fichiers système via la constante SYSTEM_FILES

#### Raccourcissement intelligent des noms (06/10/2025) ✨
- **Troncature automatique** : Noms de fichiers/dossiers trop longs raccourcis automatiquement
- **Vue grille** : Limite de 30 caractères avec format "début...fin.extension"
- **Vue liste** : Limite de 40 caractères avec même format intelligent
- **Préservation extension** : L'extension du fichier est toujours visible
- **Tooltip complet** : Attribut `title` affiche le nom complet au survol
- **Algorithme équilibré** : Découpage équitable entre début et fin du nom (sans l'extension)
- **Fonction JavaScript** : `truncateFileName()` réutilisable et modulaire

#### Système de gestion des feedbacks (06/10/2025) ✨
- **Architecture complète** : Classe FeedbackManager avec stockage JSON persistant
- **Ajout de feedbacks** : Modale avec textarea, validation (1-500 caractères), compteur temps réel
- **Liste interactive** : Tableau avec colonnes Drag | Date | Message | Actions
- **Marquage comme traité** : Toggle avec icône check (✓ vert si traité, ⭕ gris si non traité)
- **Effet visuel barré** : Texte avec line-through et opacité réduite pour feedbacks complétés
- **Suppression sécurisée** : Modale de confirmation avec aperçu du message avant suppression
- **Réorganisation drag & drop** : SortableJS avec poignée ⋮⋮, animation fluide, sauvegarde automatique
- **Mise à jour optimiste** : Changements visuels instantanés avec revert automatique en cas d'erreur
- **Handlers AJAX** : get_feedbacks, add_feedback, delete_feedback, toggle_feedback_status, reorder_feedbacks
- **Rétrocompatibilité** : Support des feedbacks sans champ `completed`
- **Interface responsive** : Adaptation mobile avec layout flexible
- **Feedback visuel** : Messages de succès/erreur, animations Animate.css, curseurs grab/grabbing

### Problèmes connus
- Aucun problème critique identifié
- Toutes les fonctionnalités principales opérationnelles
- Upload de fichiers testé et fonctionnel ✅
- Corbeille fonctionnelle testée et opérationnelle ✅
- Renommage testé et fonctionnel ✅
- Thumbnails d'images testés et fonctionnels ✅
- Navigation intelligente après actions testée et fonctionnelle ✅
- Distinction visuelle dossiers vides testée et fonctionnelle ✅
- Protection automatique fichiers système testée et fonctionnelle ✅
- Raccourcissement intelligent des noms testé et fonctionnel ✅
- Système de feedbacks testé et fonctionnel ✅

---
*Dernière mise à jour : 2025-10-06*
*État : Stable et fonctionnel - Système complet de gestion des feedbacks implémenté*