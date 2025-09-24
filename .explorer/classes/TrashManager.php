<?php

require_once __DIR__ . '/../includes/config.php';

class TrashManager {
    private $trashDir;
    private $metadataFile;

    public function __construct($baseDir = '.') {
        $this->trashDir = $baseDir . DIRECTORY_SEPARATOR . '.explorer' . DIRECTORY_SEPARATOR . 'trash';
        $this->metadataFile = $this->trashDir . DIRECTORY_SEPARATOR . '.trash_metadata.json';
        $this->ensureTrashDirectory();
    }

    private function ensureTrashDirectory() {
        if (!is_dir($this->trashDir)) {
            if (!mkdir($this->trashDir, 0755, true)) {
                throw new Exception('Impossible de créer le dossier corbeille');
            }
        }
    }

    public function moveToTrash($sourcePath) {
        if (!file_exists($sourcePath)) {
            throw new Exception('Le fichier ou dossier source n\'existe pas');
        }

        $realSourcePath = realpath($sourcePath);
        if (!$realSourcePath) {
            throw new Exception('Chemin source invalide');
        }

        $fileName = basename($realSourcePath);
        $targetPath = $this->getUniqueTrashPath($fileName);

        if (is_dir($realSourcePath)) {
            if (!$this->moveDirectory($realSourcePath, $targetPath)) {
                throw new Exception('Impossible de déplacer le dossier vers la corbeille');
            }
        } else {
            if (!rename($realSourcePath, $targetPath)) {
                throw new Exception('Impossible de déplacer le fichier vers la corbeille');
            }
        }

        $this->addMetadata($targetPath, $realSourcePath);
        return true;
    }

    private function moveDirectory($source, $destination) {
        if (!is_dir($source)) {
            return false;
        }

        if (!mkdir($destination, 0755, true)) {
            return false;
        }

        $files = scandir($source);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $destPath = $destination . DIRECTORY_SEPARATOR . $file;

            if (is_dir($sourcePath)) {
                if (!$this->moveDirectory($sourcePath, $destPath)) {
                    return false;
                }
            } else {
                if (!copy($sourcePath, $destPath)) {
                    return false;
                }
                unlink($sourcePath);
            }
        }

        return rmdir($source);
    }

    private function getUniqueTrashPath($fileName) {
        $basePath = $this->trashDir . DIRECTORY_SEPARATOR . $fileName;

        if (!file_exists($basePath)) {
            return $basePath;
        }

        $info = pathinfo($fileName);
        $baseName = $info['filename'];
        $extension = isset($info['extension']) ? '.' . $info['extension'] : '';

        $counter = 1;
        do {
            $newFileName = $baseName . '_' . $counter . $extension;
            $newPath = $this->trashDir . DIRECTORY_SEPARATOR . $newFileName;
            $counter++;
        } while (file_exists($newPath));

        return $newPath;
    }

    private function addMetadata($trashPath, $originalPath) {
        $metadata = $this->loadMetadata();

        $trashFileName = basename($trashPath);
        $metadata[$trashFileName] = [
            'original_path' => $originalPath,
            'deleted_at' => date('Y-m-d H:i:s'),
            'original_name' => basename($originalPath)
        ];

        $this->saveMetadata($metadata);
    }

    private function loadMetadata() {
        if (!file_exists($this->metadataFile)) {
            return [];
        }

        $content = file_get_contents($this->metadataFile);
        $metadata = json_decode($content, true);

        return $metadata ?: [];
    }

    private function saveMetadata($metadata) {
        $content = json_encode($metadata, JSON_PRETTY_PRINT);
        if (file_put_contents($this->metadataFile, $content) === false) {
            throw new Exception('Impossible de sauvegarder les métadonnées de la corbeille');
        }
    }

    public function getTrashContents() {
        if (!is_dir($this->trashDir)) {
            return [];
        }

        $metadata = $this->loadMetadata();
        $contents = [];
        $files = scandir($this->trashDir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === '.trash_metadata.json') {
                continue;
            }

            $filePath = $this->trashDir . DIRECTORY_SEPARATOR . $file;
            $fileData = [
                'name' => $file,
                'type' => is_dir($filePath) ? 'directory' : 'file',
                'path' => $filePath,
                'size' => is_file($filePath) ? filesize($filePath) : '',
                'modified' => filemtime($filePath),
                'in_trash' => true
            ];

            if (isset($metadata[$file])) {
                $fileData['original_path'] = $metadata[$file]['original_path'];
                $fileData['deleted_at'] = $metadata[$file]['deleted_at'];
                $fileData['original_name'] = $metadata[$file]['original_name'];
            }

            $contents[] = $fileData;
        }

        return $contents;
    }

    public function emptyTrash() {
        if (!is_dir($this->trashDir)) {
            return true;
        }

        $files = scandir($this->trashDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $this->trashDir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $this->removeDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        return true;
    }

    private function removeDirectory($dir) {
        if (!is_dir($dir)) {
            return false;
        }

        $files = scandir($dir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $filePath = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $this->removeDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        return rmdir($dir);
    }
}