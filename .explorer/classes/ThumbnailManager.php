<?php

class ThumbnailManager {
    private $baseDir;
    private $thumbnailDir;
    private $thumbnailSize = 150;
    private $supportedFormats = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
    private $quality = 85;

    public function __construct($baseDir = '.') {
        $this->baseDir = realpath($baseDir);
        $this->thumbnailDir = $this->baseDir . DIRECTORY_SEPARATOR . '.explorer' . DIRECTORY_SEPARATOR . 'thumbnails';

        // Créer le dossier des thumbnails s'il n'existe pas
        if (!is_dir($this->thumbnailDir)) {
            mkdir($this->thumbnailDir, 0755, true);
        }
    }

    public function isImageFile($filePath) {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        return in_array($ext, $this->supportedFormats);
    }

    public function getThumbnailPath($originalFilePath) {
        $hash = md5($originalFilePath . filemtime($originalFilePath));
        $ext = strtolower(pathinfo($originalFilePath, PATHINFO_EXTENSION));

        // Normaliser l'extension pour le thumbnail
        $thumbExt = ($ext === 'jpeg') ? 'jpg' : $ext;
        if ($thumbExt === 'gif' || $thumbExt === 'webp') {
            $thumbExt = 'png'; // Convertir en PNG pour compatibilité
        }

        return $this->thumbnailDir . DIRECTORY_SEPARATOR . $hash . '.' . $thumbExt;
    }

    public function thumbnailExists($originalFilePath) {
        $thumbnailPath = $this->getThumbnailPath($originalFilePath);
        return file_exists($thumbnailPath);
    }

    public function generateThumbnail($originalFilePath) {
        if (!$this->isImageFile($originalFilePath) || !file_exists($originalFilePath)) {
            return false;
        }

        $thumbnailPath = $this->getThumbnailPath($originalFilePath);

        // Si le thumbnail existe déjà et est plus récent que l'original, ne pas régénérer
        if (file_exists($thumbnailPath) && filemtime($thumbnailPath) >= filemtime($originalFilePath)) {
            return $thumbnailPath;
        }

        try {
            // Détecter le vrai type MIME du fichier plutôt que se fier à l'extension
            $imageInfo = getimagesize($originalFilePath);
            if (!$imageInfo) {
                return false;
            }

            $mimeType = $imageInfo['mime'];
            $sourceImage = null;

            // Charger l'image source selon son vrai type MIME
            switch ($mimeType) {
                case 'image/jpeg':
                    $sourceImage = imagecreatefromjpeg($originalFilePath);
                    break;
                case 'image/png':
                    $sourceImage = imagecreatefrompng($originalFilePath);
                    break;
                case 'image/gif':
                    $sourceImage = imagecreatefromgif($originalFilePath);
                    break;
                case 'image/webp':
                    if (function_exists('imagecreatefromwebp')) {
                        $sourceImage = imagecreatefromwebp($originalFilePath);
                    }
                    break;
                case 'image/bmp':
                case 'image/x-ms-bmp':
                    if (function_exists('imagecreatefrombmp')) {
                        $sourceImage = imagecreatefrombmp($originalFilePath);
                    }
                    break;
            }

            if (!$sourceImage) {
                return false;
            }

            // Obtenir les dimensions de l'image source
            $sourceWidth = imagesx($sourceImage);
            $sourceHeight = imagesy($sourceImage);

            // Calculer les nouvelles dimensions en préservant le ratio
            $ratio = min($this->thumbnailSize / $sourceWidth, $this->thumbnailSize / $sourceHeight);
            $newWidth = round($sourceWidth * $ratio);
            $newHeight = round($sourceHeight * $ratio);

            // Créer la nouvelle image
            $thumbnail = imagecreatetruecolor($newWidth, $newHeight);

            // Préserver la transparence pour PNG et GIF
            if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                imagealphablending($thumbnail, false);
                imagesavealpha($thumbnail, true);
                $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
                imagefill($thumbnail, 0, 0, $transparent);
            }

            // Redimensionner l'image
            imagecopyresampled(
                $thumbnail, $sourceImage,
                0, 0, 0, 0,
                $newWidth, $newHeight,
                $sourceWidth, $sourceHeight
            );

            // Sauvegarder le thumbnail
            $success = false;
            $thumbExt = pathinfo($thumbnailPath, PATHINFO_EXTENSION);

            switch ($thumbExt) {
                case 'jpg':
                    $success = imagejpeg($thumbnail, $thumbnailPath, $this->quality);
                    break;
                case 'png':
                    $success = imagepng($thumbnail, $thumbnailPath, 9);
                    break;
            }

            // Nettoyer la mémoire
            imagedestroy($sourceImage);
            imagedestroy($thumbnail);

            return $success ? $thumbnailPath : false;

        } catch (Exception $e) {
            error_log("Erreur génération thumbnail: " . $e->getMessage());
            return false;
        }
    }

    public function getThumbnailUrl($originalFilePath) {
        if (!$this->isImageFile($originalFilePath)) {
            return null;
        }

        $relativePath = str_replace($this->baseDir, '', $originalFilePath);
        $relativePath = trim($relativePath, DIRECTORY_SEPARATOR);

        return '?action=thumbnail&path=' . urlencode($relativePath);
    }

    public function cleanupOldThumbnails($maxAge = 2592000) { // 30 jours par défaut
        if (!is_dir($this->thumbnailDir)) {
            return;
        }

        $files = glob($this->thumbnailDir . DIRECTORY_SEPARATOR . '*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > $maxAge) {
                unlink($file);
            }
        }
    }

    public function getCacheSize() {
        $size = 0;
        $files = glob($this->thumbnailDir . DIRECTORY_SEPARATOR . '*');

        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            }
        }

        return $size;
    }

    public function getThumbnailDir() {
        return $this->thumbnailDir;
    }
}