<?php

require_once __DIR__ . '/HiddenManager.php';
require_once __DIR__ . '/TrashManager.php';
require_once __DIR__ . '/ThumbnailManager.php';

class FileExplorer {
    private $baseDir;
    private $currentDir;
    private $hiddenManager;
    private $thumbnailManager;

    public function __construct($baseDir = '.') {
        $this->baseDir = realpath($baseDir);
        $requestedDir = isset($_GET['dir']) ? $_GET['dir'] : '.';

        // Si on demande un répertoire système (comme C:), utiliser le répertoire de base
        if ($requestedDir === 'C:' || strpos($requestedDir, 'C:\\') === 0) {
            $this->currentDir = $this->baseDir;
        } else if ($requestedDir === '.') {
            $this->currentDir = $this->baseDir;
        } else {
            // Pour les chemins relatifs, les construire à partir du répertoire de base
            if (!$this->isAbsolutePath($requestedDir)) {
                $fullPath = $this->baseDir . DIRECTORY_SEPARATOR . $requestedDir;
                $this->currentDir = realpath($fullPath) ?: $this->baseDir;
            } else {
                $this->currentDir = realpath($requestedDir) ?: $this->baseDir;
            }
        }

        // Vérification de sécurité : s'assurer qu'on reste dans le répertoire de base
        if (strpos($this->currentDir, $this->baseDir) !== 0) {
            $this->currentDir = $this->baseDir;
        }

        $this->hiddenManager = new HiddenManager($this->baseDir);
        $this->thumbnailManager = new ThumbnailManager($this->baseDir);
    }

    public function getCurrentPath() {
        $relativePath = str_replace($this->baseDir, '', $this->currentDir);
        return $relativePath ? trim($relativePath, DIRECTORY_SEPARATOR) : 'Home';
    }

    public function getBreadcrumbs() {
        $relativePath = str_replace($this->baseDir, '', $this->currentDir);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $relativePath));

        $breadcrumbs = [['name' => 'Home', 'path' => '.']];
        $currentRelativePath = '';

        foreach ($parts as $part) {
            $currentRelativePath .= ($currentRelativePath ? DIRECTORY_SEPARATOR : '') . $part;
            $breadcrumbs[] = ['name' => $part, 'path' => $currentRelativePath];
        }

        return $breadcrumbs;
    }

    public function getQuickAccessItems() {
        $userHome = $_SERVER['HOME'] ?? $_SERVER['USERPROFILE'] ?? '';
        $items = [];

        $quickAccess = [
            ['name' => 'Desktop', 'path' => $userHome . DIRECTORY_SEPARATOR . 'Desktop', 'icon' => '🖥️'],
            ['name' => 'Downloads', 'path' => $userHome . DIRECTORY_SEPARATOR . 'Downloads', 'icon' => '⬇️'],
            ['name' => 'Documents', 'path' => $userHome . DIRECTORY_SEPARATOR . 'Documents', 'icon' => '📄'],
            ['name' => 'Pictures', 'path' => $userHome . DIRECTORY_SEPARATOR . 'Pictures', 'icon' => '🖼️'],
            ['name' => 'Music', 'path' => $userHome . DIRECTORY_SEPARATOR . 'Music', 'icon' => '🎵'],
            ['name' => 'Videos', 'path' => $userHome . DIRECTORY_SEPARATOR . 'Videos', 'icon' => '🎬'],
        ];

        foreach ($quickAccess as $item) {
            if (is_dir($item['path'])) {
                $items[] = $item;
            }
        }

        return $items;
    }

    public function getDirectoryContents() {
        // Si on est dans la corbeille, utiliser TrashManager
        if (strpos($this->currentDir, '.explorer' . DIRECTORY_SEPARATOR . 'trash') !== false) {
            return $this->getTrashContents();
        }

        $items = [];

        if (is_readable($this->currentDir)) {
            $files = scandir($this->currentDir);

            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                $fullPath = $this->currentDir . DIRECTORY_SEPARATOR . $file;

                // Filtrer les éléments cachés
                if ($this->hiddenManager->isHidden($fullPath)) {
                    continue;
                }

                if (is_dir($fullPath)) {
                    $items[] = [
                        'name' => $file,
                        'type' => 'directory',
                        'path' => $fullPath,
                        'size' => '',
                        'modified' => filemtime($fullPath),
                        'isEmpty' => $this->isDirectoryEmpty($fullPath)
                    ];
                } else {
                    $items[] = [
                        'name' => $file,
                        'type' => 'file',
                        'path' => $fullPath,
                        'size' => filesize($fullPath),
                        'modified' => filemtime($fullPath)
                    ];
                }
            }
        }

        usort($items, function($a, $b) {
            if ($a['type'] === 'directory' && $b['type'] === 'file') return -1;
            if ($a['type'] === 'file' && $b['type'] === 'directory') return 1;
            return strcasecmp($a['name'], $b['name']);
        });

        return $items;
    }

    private function getTrashContents() {
        $trashManager = new TrashManager($this->baseDir);
        return $trashManager->getTrashContents();
    }

    public function formatFileSize($bytes) {
        if ($bytes == 0) return '0 B';
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $pow = floor(log($bytes) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, 1) . ' ' . $units[$pow];
    }

    public function getFileIcon($filename, $type) {
        if ($type === 'directory') return '📁';

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        $icons = [
            'txt' => '📄', 'doc' => '📄', 'docx' => '📄', 'pdf' => '📄',
            'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'bmp' => '🖼️', 'webp' => '🖼️', 'svg' => '🖼️',
            'mp3' => '🎵', 'wav' => '🎵', 'mp4' => '🎬', 'avi' => '🎬', 'mkv' => '🎬',
            'zip' => '📦', 'rar' => '📦', '7z' => '📦',
            'php' => '💻', 'html' => '🌐', 'css' => '🎨', 'js' => '⚡', 'json' => '📋',
            'exe' => '⚙️', 'msi' => '⚙️'
        ];

        return $icons[$ext] ?? '📄';
    }

    public function isImageFile($filePath) {
        return $this->thumbnailManager->isImageFile($filePath);
    }

    public function getThumbnailUrl($filePath) {
        return $this->thumbnailManager->getThumbnailUrl($filePath);
    }

    public function hasThumbnail($filePath) {
        return $this->thumbnailManager->isImageFile($filePath);
    }

    public function getHiddenManager() {
        return $this->hiddenManager;
    }

    public function getRelativePath($fullPath) {
        // Si c'est déjà le répertoire de base, retourner '.'
        if ($fullPath === $this->baseDir) {
            return '.';
        }

        // Convertir le chemin absolu en chemin relatif au répertoire de base
        $relativePath = str_replace($this->baseDir, '', $fullPath);
        $relativePath = trim($relativePath, DIRECTORY_SEPARATOR);

        if (empty($relativePath)) {
            return '.';
        }

        // Sur Windows, convertir les \ en / pour l'URL, mais garder le format pour le système
        return $relativePath;
    }

    private function isAbsolutePath($path) {
        // Sur Windows : C:\... ou sur Unix : /...
        return (DIRECTORY_SEPARATOR === '\\' && preg_match('/^[A-Za-z]:/', $path)) ||
               (DIRECTORY_SEPARATOR === '/' && strpos($path, '/') === 0);
    }

    private function isDirectoryEmpty($dirPath) {
        if (!is_readable($dirPath)) {
            return true;
        }

        $files = scandir($dirPath);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $fullPath = $dirPath . DIRECTORY_SEPARATOR . $file;

            // Ne compter que les éléments visibles (non cachés)
            if (!$this->hiddenManager->isHidden($fullPath)) {
                return false;
            }
        }

        return true;
    }
}