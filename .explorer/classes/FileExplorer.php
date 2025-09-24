<?php

require_once __DIR__ . '/HiddenManager.php';

class FileExplorer {
    private $baseDir;
    private $currentDir;
    private $hiddenManager;

    public function __construct($baseDir = '.') {
        $this->baseDir = realpath($baseDir);
        $this->currentDir = isset($_GET['dir']) ? $_GET['dir'] : $this->baseDir;
        $this->currentDir = realpath($this->currentDir) ?: $this->baseDir;

        if (strpos($this->currentDir, $this->baseDir) !== 0) {
            $this->currentDir = $this->baseDir;
        }

        $this->hiddenManager = new HiddenManager($this->baseDir);
    }

    public function getCurrentPath() {
        $relativePath = str_replace($this->baseDir, '', $this->currentDir);
        return $relativePath ? trim($relativePath, DIRECTORY_SEPARATOR) : 'Home';
    }

    public function getBreadcrumbs() {
        $relativePath = str_replace($this->baseDir, '', $this->currentDir);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $relativePath));

        $breadcrumbs = [['name' => 'Home', 'path' => $this->baseDir]];
        $currentPath = $this->baseDir;

        foreach ($parts as $part) {
            $currentPath .= DIRECTORY_SEPARATOR . $part;
            $breadcrumbs[] = ['name' => $part, 'path' => $currentPath];
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
                        'modified' => filemtime($fullPath)
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
            'jpg' => '🖼️', 'jpeg' => '🖼️', 'png' => '🖼️', 'gif' => '🖼️', 'bmp' => '🖼️',
            'mp3' => '🎵', 'wav' => '🎵', 'mp4' => '🎬', 'avi' => '🎬', 'mkv' => '🎬',
            'zip' => '📦', 'rar' => '📦', '7z' => '📦',
            'php' => '💻', 'html' => '🌐', 'css' => '🎨', 'js' => '⚡', 'json' => '📋',
            'exe' => '⚙️', 'msi' => '⚙️'
        ];

        return $icons[$ext] ?? '📄';
    }

    public function getHiddenManager() {
        return $this->hiddenManager;
    }
}