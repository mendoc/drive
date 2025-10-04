<?php

require_once __DIR__ . '/ThumbnailManager.php';

class UploadManager {
    private $uploadDir;
    private $errors = [];
    private $thumbnailManager;

    public function __construct($uploadDirectory = '.') {
        $this->uploadDir = realpath($uploadDirectory);

        if (!$this->uploadDir || !is_dir($this->uploadDir)) {
            throw new Exception("Répertoire d'upload invalide");
        }

        if (!is_writable($this->uploadDir)) {
            throw new Exception("Répertoire d'upload non accessible en écriture");
        }

        // Initialiser le gestionnaire de thumbnails
        $this->thumbnailManager = new ThumbnailManager($this->uploadDir);
    }

    public function upload($files, $targetDir = null) {
        $this->errors = [];
        $uploadedFiles = [];
        $targetDirectory = $targetDir ? $targetDir : $this->uploadDir;

        // Vérifier que le répertoire cible existe et est valide
        if (!$this->isValidDirectory($targetDirectory)) {
            throw new Exception("Répertoire de destination invalide");
        }

        // Traiter chaque fichier
        if (is_array($files['tmp_name'])) {
            // Upload multiple
            for ($i = 0; $i < count($files['tmp_name']); $i++) {
                $file = [
                    'name' => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'size' => $files['size'][$i],
                    'error' => $files['error'][$i],
                    'type' => $files['type'][$i]
                ];

                $result = $this->uploadSingleFile($file, $targetDirectory);
                if ($result['success']) {
                    $uploadedFiles[] = $result['filename'];
                }
            }
        } else {
            // Upload simple
            $result = $this->uploadSingleFile($files, $targetDirectory);
            if ($result['success']) {
                $uploadedFiles[] = $result['filename'];
            }
        }

        return [
            'success' => count($uploadedFiles) > 0,
            'uploaded_files' => $uploadedFiles,
            'errors' => $this->errors,
            'count' => count($uploadedFiles)
        ];
    }

    private function uploadSingleFile($file, $targetDirectory) {
        // Vérification des erreurs PHP
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error = $this->getUploadErrorMessage($file['error']);
            $this->errors[] = "Erreur pour " . $file['name'] . ": " . $error;
            return ['success' => false];
        }

        // Validation du fichier
        if (!$this->validateFile($file)) {
            return ['success' => false];
        }

        // Générer un nom de fichier sécurisé
        $filename = $this->generateSafeFilename($file['name'], $targetDirectory);
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;

        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Définir les permissions
            chmod($targetPath, 0644);

            // Générer automatiquement le thumbnail si c'est une image
            if ($this->thumbnailManager->isImageFile($targetPath)) {
                try {
                    $this->thumbnailManager->generateThumbnail($targetPath);
                } catch (Exception $e) {
                    // En cas d'erreur, on log mais on ne fait pas échouer l'upload
                    error_log("Erreur génération thumbnail pour {$targetPath}: " . $e->getMessage());
                }
            }

            return ['success' => true, 'filename' => $filename, 'path' => $targetPath];
        } else {
            $this->errors[] = "Impossible de déplacer le fichier " . $file['name'];
            return ['success' => false];
        }
    }

    private function validateFile($file) {
        $filename = $file['name'];
        $fileSize = $file['size'];
        $tmpName = $file['tmp_name'];

        // Vérifier la taille
        if ($fileSize > MAX_FILE_SIZE) {
            $maxSizeMB = round(MAX_FILE_SIZE / 1024 / 1024, 1);
            $this->errors[] = "Le fichier $filename dépasse la taille maximale de {$maxSizeMB} MB";
            return false;
        }

        if ($fileSize === 0) {
            $this->errors[] = "Le fichier $filename est vide";
            return false;
        }

        // Vérifier l'extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (in_array($extension, FORBIDDEN_FILE_TYPES)) {
            $this->errors[] = "Type de fichier interdit: $filename (.$extension)";
            return false;
        }

        if (!empty(ALLOWED_FILE_TYPES) && !in_array($extension, ALLOWED_FILE_TYPES)) {
            $this->errors[] = "Type de fichier non autorisé: $filename (.$extension)";
            return false;
        }

        // Vérification MIME basique
        if (!$this->validateMimeType($tmpName, $extension)) {
            $this->errors[] = "Le fichier $filename ne correspond pas à son extension";
            return false;
        }

        // Validation du nom de fichier
        $nameValidation = $this->validateFilename(pathinfo($filename, PATHINFO_FILENAME));
        if (!$nameValidation['valid']) {
            $this->errors[] = "Nom invalide pour $filename: " . $nameValidation['error'];
            return false;
        }

        return true;
    }

    private function validateMimeType($tmpName, $extension) {
        if (!function_exists('finfo_open')) {
            return true; // Skip si finfo n'est pas disponible
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        $allowedMimes = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'jpeg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png'],
            'gif' => ['image/gif'],
            'pdf' => ['application/pdf'],
            'txt' => ['text/plain'],
            'csv' => ['text/csv', 'text/plain'],
            'zip' => ['application/zip', 'application/x-zip-compressed'],
            'mp3' => ['audio/mpeg'],
            'mp4' => ['video/mp4']
        ];

        if (isset($allowedMimes[$extension])) {
            return in_array($mimeType, $allowedMimes[$extension]);
        }

        return true; // Autoriser les types non spécifiés
    }

    private function generateSafeFilename($originalName, $targetDirectory) {
        $name = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

        // Nettoyer le nom
        $name = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', $name);
        $name = preg_replace('/_{2,}/', '_', $name);
        $name = trim($name, '_');

        if (empty($name)) {
            $name = 'fichier_' . time();
        }

        $filename = $name . ($extension ? '.' . $extension : '');
        $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;

        // Gestion des doublons
        $counter = 1;
        while (file_exists($targetPath)) {
            $filename = $name . '_' . $counter . ($extension ? '.' . $extension : '');
            $targetPath = $targetDirectory . DIRECTORY_SEPARATOR . $filename;
            $counter++;
        }

        return $filename;
    }

    private function validateFilename($filename) {
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

    private function isValidDirectory($directory) {
        $realPath = realpath($directory);
        return $realPath && is_dir($realPath) && is_writable($realPath);
    }

    private function getUploadErrorMessage($errorCode) {
        $messages = [
            UPLOAD_ERR_OK => 'Aucune erreur',
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par le serveur',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale du formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
            UPLOAD_ERR_NO_TMP_DIR => 'Répertoire temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture sur le disque',
            UPLOAD_ERR_EXTENSION => 'Upload arrêté par une extension PHP'
        ];

        return isset($messages[$errorCode]) ? $messages[$errorCode] : 'Erreur inconnue';
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getMaxFileSize() {
        return MAX_FILE_SIZE;
    }

    public function getAllowedTypes() {
        return ALLOWED_FILE_TYPES;
    }

    public function getFormattedMaxSize() {
        $size = MAX_FILE_SIZE;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;

        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }

        return round($size, 1) . ' ' . $units[$unit];
    }
}