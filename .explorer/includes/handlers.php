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