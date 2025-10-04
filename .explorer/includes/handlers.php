<?php

require_once __DIR__ . '/config.php';
require_once CLASSES_DIR . '/FileExplorer.php';
require_once CLASSES_DIR . '/HiddenManager.php';
require_once CLASSES_DIR . '/UploadManager.php';
require_once CLASSES_DIR . '/TrashManager.php';
require_once CLASSES_DIR . '/ThumbnailManager.php';

// Gestion des actions AJAX
function handleAjaxRequest() {
    if (!isset($_POST['action'])) {
        return false;
    }

    switch ($_POST['action']) {
        case 'hide':
            return handleHideAction();
        case 'create_folder':
            return handleCreateFolderAction();
        case 'upload':
            return handleUploadAction();
        case 'move_to_trash':
            return handleMoveToTrashAction();
        case 'rename':
            return handleRenameAction();
        default:
            return false;
    }
}

// Gestion du masquage d'éléments
function handleHideAction() {
    $pathToHide = $_POST['path'] ?? '';
    if (empty($pathToHide)) {
        echo json_encode(['success' => false, 'error' => 'Chemin manquant']);
        return true;
    }

    try {
        $hiddenManager = new HiddenManager('.');
        $hiddenManager->addHiddenPath($pathToHide);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    return true;
}

// Gestion de la création de dossier
function handleCreateFolderAction() {
    $folderName = trim($_POST['name'] ?? '');
    $currentDir = $_POST['current_dir'] ?? '.';

    // Debug
    error_log("Creating folder: $folderName in directory: $currentDir");

    if (empty($folderName)) {
        echo json_encode(['success' => false, 'error' => 'Le nom du dossier ne peut pas être vide']);
        return true;
    }

    // Validation du nom de fichier
    $validation = validateFilename($folderName);
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        return true;
    }

    // Sécurité : vérifier que le répertoire courant est valide
    $realCurrentDir = realpath($currentDir);
    if (!$realCurrentDir || !is_dir($realCurrentDir)) {
        echo json_encode(['success' => false, 'error' => 'Répertoire invalide']);
        return true;
    }

    $folderPath = $realCurrentDir . DIRECTORY_SEPARATOR . $folderName;

    // Vérifier si le dossier existe déjà
    if (file_exists($folderPath)) {
        echo json_encode(['success' => false, 'error' => 'Un fichier ou dossier avec ce nom existe déjà']);
        return true;
    }

    // Créer le dossier
    try {
        if (mkdir($folderPath, 0755)) {
            echo json_encode(['success' => true, 'message' => 'Dossier créé avec succès']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Impossible de créer le dossier']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur lors de la création : ' . $e->getMessage()]);
    }

    return true;
}

// Validation des noms de fichiers/dossiers
function validateFilename($filename) {
    if (empty($filename)) {
        return ['valid' => false, 'error' => 'Le nom ne peut pas être vide'];
    }

    if (strlen($filename) > MAX_FILENAME_LENGTH) {
        return ['valid' => false, 'error' => 'Le nom est trop long (max ' . MAX_FILENAME_LENGTH . ' caractères)'];
    }

    foreach (FORBIDDEN_CHARS as $char) {
        if (strpos($filename, $char) !== false) {
            return ['valid' => false, 'error' => 'Caractères interdits : ' . implode(' ', FORBIDDEN_CHARS)];
        }
    }

    if (trim($filename) !== $filename) {
        return ['valid' => false, 'error' => 'Le nom ne peut pas commencer ou finir par un espace'];
    }

    return ['valid' => true];
}

// Gestion de l'upload de fichiers
function handleUploadAction() {
    // Vérifier qu'il y a bien des fichiers uploadés
    if (!isset($_FILES['files']) || empty($_FILES['files']['tmp_name'])) {
        echo json_encode(['success' => false, 'error' => 'Aucun fichier n\'a été sélectionné']);
        return true;
    }

    $currentDir = $_POST['current_dir'] ?? '.';

    // Validation du répertoire de destination
    $realCurrentDir = realpath($currentDir);
    if (!$realCurrentDir || !is_dir($realCurrentDir)) {
        echo json_encode(['success' => false, 'error' => 'Répertoire de destination invalide']);
        return true;
    }

    try {
        $uploadManager = new UploadManager($realCurrentDir);
        $result = $uploadManager->upload($_FILES['files'], $realCurrentDir);

        if ($result['success']) {
            $message = $result['count'] === 1
                ? 'Fichier importé avec succès'
                : $result['count'] . ' fichiers importés avec succès';

            echo json_encode([
                'success' => true,
                'message' => $message,
                'uploaded_files' => $result['uploaded_files'],
                'count' => $result['count'],
                'errors' => $result['errors']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Échec de l\'import',
                'details' => $result['errors']
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Erreur du serveur : ' . $e->getMessage()
        ]);
    }

    return true;
}

// Gestion du déplacement vers la corbeille
function handleMoveToTrashAction() {
    $pathToDelete = $_POST['path'] ?? '';
    if (empty($pathToDelete)) {
        echo json_encode(['success' => false, 'error' => 'Chemin manquant']);
        return true;
    }

    // Sécurité : vérifier que le chemin est valide
    $realPath = realpath($pathToDelete);
    if (!$realPath || !file_exists($realPath)) {
        echo json_encode(['success' => false, 'error' => 'Fichier ou dossier introuvable']);
        return true;
    }

    // Vérifier qu'on ne supprime pas le framework ou des fichiers système
    if (strpos($realPath, '.explorer') !== false ||
        strpos($realPath, 'CLAUDE.md') !== false ||
        strpos($realPath, 'index.php') !== false) {
        echo json_encode(['success' => false, 'error' => 'Impossible de supprimer les fichiers système']);
        return true;
    }

    try {
        $trashManager = new TrashManager('.');
        $trashManager->moveToTrash($realPath);

        $itemName = basename($realPath);
        $itemType = is_dir($realPath) ? 'dossier' : 'fichier';
        $message = ucfirst($itemType) . ' "' . $itemName . '" déplacé vers la corbeille';

        echo json_encode(['success' => true, 'message' => $message]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    return true;
}

// Gestion du renommage de fichiers/dossiers
function handleRenameAction() {
    $oldPath = $_POST['old_path'] ?? '';
    $newName = trim($_POST['new_name'] ?? '');

    if (empty($oldPath)) {
        echo json_encode(['success' => false, 'error' => 'Chemin du fichier manquant']);
        return true;
    }

    if (empty($newName)) {
        echo json_encode(['success' => false, 'error' => 'Le nouveau nom ne peut pas être vide']);
        return true;
    }

    // Validation du nouveau nom
    $validation = validateFilename($newName);
    if (!$validation['valid']) {
        echo json_encode(['success' => false, 'error' => $validation['error']]);
        return true;
    }

    // Vérifier que le fichier source existe
    $realOldPath = realpath($oldPath);
    if (!$realOldPath || !file_exists($realOldPath)) {
        echo json_encode(['success' => false, 'error' => 'Fichier ou dossier introuvable']);
        return true;
    }

    // Vérifier qu'on ne renomme pas des fichiers système
    if (strpos($realOldPath, '.explorer') !== false ||
        strpos($realOldPath, 'CLAUDE.md') !== false ||
        strpos($realOldPath, 'index.php') !== false) {
        echo json_encode(['success' => false, 'error' => 'Impossible de renommer les fichiers système']);
        return true;
    }

    // Construire le nouveau chemin
    $directory = dirname($realOldPath);
    $newPath = $directory . DIRECTORY_SEPARATOR . $newName;

    // Vérifier si un fichier avec le nouveau nom existe déjà
    if (file_exists($newPath)) {
        echo json_encode(['success' => false, 'error' => 'Un fichier ou dossier avec ce nom existe déjà']);
        return true;
    }

    // Effectuer le renommage
    try {
        if (rename($realOldPath, $newPath)) {
            $itemType = is_dir($newPath) ? 'dossier' : 'fichier';
            $message = ucfirst($itemType) . ' renommé avec succès';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Impossible de renommer le fichier']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Erreur lors du renommage : ' . $e->getMessage()]);
    }

    return true;
}

// Gestion des requêtes de thumbnail
function handleThumbnailRequest() {
    $imagePath = $_GET['path'] ?? '';

    if (empty($imagePath)) {
        http_response_code(400);
        echo 'Chemin d\'image manquant';
        return;
    }

    try {
        // Construire le chemin complet sécurisé
        $baseDir = realpath('.');

        // Normaliser les séparateurs de chemin pour Windows
        $normalizedPath = str_replace('/', DIRECTORY_SEPARATOR, $imagePath);
        $fullPath = $baseDir . DIRECTORY_SEPARATOR . $normalizedPath;
        $realPath = realpath($fullPath);

        // Vérifications de sécurité
        if (!$realPath || strpos($realPath, $baseDir) !== 0) {
            http_response_code(403);
            echo 'Accès interdit';
            return;
        }

        if (!file_exists($realPath)) {
            http_response_code(404);
            echo 'Fichier non trouvé';
            return;
        }

        $thumbnailManager = new ThumbnailManager($baseDir);

        if (!$thumbnailManager->isImageFile($realPath)) {
            http_response_code(400);
            echo 'Le fichier n\'est pas une image supportée';
            return;
        }

        // Générer ou récupérer le thumbnail
        $thumbnailPath = $thumbnailManager->generateThumbnail($realPath);

        if (!$thumbnailPath || !file_exists($thumbnailPath)) {
            http_response_code(500);
            echo 'Erreur lors de la génération du thumbnail';
            return;
        }

        // Définir les headers appropriés
        $mimeType = 'image/' . pathinfo($thumbnailPath, PATHINFO_EXTENSION);
        if (pathinfo($thumbnailPath, PATHINFO_EXTENSION) === 'jpg') {
            $mimeType = 'image/jpeg';
        }

        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . filesize($thumbnailPath));
        header('Cache-Control: public, max-age=2592000'); // Cache 30 jours
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($thumbnailPath)) . ' GMT');

        // Envoyer le fichier
        readfile($thumbnailPath);

    } catch (Exception $e) {
        error_log("Erreur thumbnail: " . $e->getMessage());
        http_response_code(500);
        echo 'Erreur serveur';
    }
}