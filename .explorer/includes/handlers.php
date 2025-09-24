<?php

require_once __DIR__ . '/config.php';
require_once CLASSES_DIR . '/FileExplorer.php';
require_once CLASSES_DIR . '/HiddenManager.php';

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