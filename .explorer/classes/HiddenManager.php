<?php

class HiddenManager {
    private $hiddenFile;
    private $baseDir;

    // Fichiers système protégés automatiquement
    private const SYSTEM_FILES = [
        'index.php',
        'CLAUDE.md',
        'OngouaSync.php'
    ];

    public function __construct($baseDir) {
        $this->baseDir = realpath($baseDir);
        $this->hiddenFile = $baseDir . DIRECTORY_SEPARATOR . '.explorer' . DIRECTORY_SEPARATOR . '.hidden';
    }

    public function getHiddenPaths() {
        if (!file_exists($this->hiddenFile)) {
            return [];
        }

        $content = file_get_contents($this->hiddenFile);
        return array_filter(array_map('trim', explode("\n", $content)));
    }

    public function addHiddenPath($path) {
        $hiddenPaths = $this->getHiddenPaths();
        if (!in_array($path, $hiddenPaths)) {
            $hiddenPaths[] = $path;
            file_put_contents($this->hiddenFile, implode("\n", $hiddenPaths));
        }
    }

    public function isHidden($path) {
        $filename = basename($path);

        // Vérifier si c'est un fichier système protégé (à la racine uniquement)
        if ($this->isSystemFile($path)) {
            return true;
        }

        // Vérifier si le fichier commence par un point
        if (strpos($filename, '.') === 0 && $filename !== '.' && $filename !== '..') {
            return true;
        }

        // Vérifier si le chemin est dans .hidden
        $hiddenPaths = $this->getHiddenPaths();
        return in_array($path, $hiddenPaths);
    }

    private function isSystemFile($path) {
        // Vérifier si le fichier est à la racine du projet
        $realPath = realpath($path);
        if (!$realPath) {
            return false;
        }

        $pathDir = dirname($realPath);
        if ($pathDir !== $this->baseDir) {
            return false;
        }

        // Vérifier si le nom du fichier est dans la liste des fichiers système
        $filename = basename($realPath);
        return in_array($filename, self::SYSTEM_FILES);
    }

    public function getSystemFiles() {
        return self::SYSTEM_FILES;
    }
}