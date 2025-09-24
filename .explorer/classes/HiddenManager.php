<?php

class HiddenManager {
    private $hiddenFile;

    public function __construct($baseDir) {
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
        // Vérifier si le fichier commence par un point
        $filename = basename($path);
        if (strpos($filename, '.') === 0 && $filename !== '.' && $filename !== '..') {
            return true;
        }

        // Vérifier si le chemin est dans .hidden
        $hiddenPaths = $this->getHiddenPaths();
        return in_array($path, $hiddenPaths);
    }
}