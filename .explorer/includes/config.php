<?php

// Configuration générale de l'explorateur de fichiers
define('EXPLORER_VERSION', '1.0.0');
define('EXPLORER_TITLE', 'Explorateur de fichiers');

// Chemins de base
define('EXPLORER_DIR', __DIR__ . '/..');
define('ASSETS_DIR', EXPLORER_DIR . '/assets');
define('CLASSES_DIR', EXPLORER_DIR . '/classes');

// Configuration de sécurité
define('MAX_FILENAME_LENGTH', 255);
define('FORBIDDEN_CHARS', ['/', '\\', ':', '*', '?', '"', '<', '>', '|']);

// Configuration d'affichage
define('DEFAULT_VIEW_MODE', 'grid');
define('ITEMS_PER_PAGE', 100);

// Types de fichiers supportés pour les icônes
$SUPPORTED_EXTENSIONS = [
    'txt', 'doc', 'docx', 'pdf', 'rtf',
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
    'mp3', 'wav', 'flac', 'aac', 'm4a',
    'mp4', 'avi', 'mkv', 'mov', 'wmv', 'flv',
    'zip', 'rar', '7z', 'tar', 'gz',
    'php', 'html', 'htm', 'css', 'js', 'json', 'xml',
    'exe', 'msi', 'deb', 'rpm'
];

// Chemins d'accès rapide
$QUICK_ACCESS_PATHS = [
    'Desktop' => ['icon' => '🖥️', 'path' => 'Desktop'],
    'Downloads' => ['icon' => '⬇️', 'path' => 'Downloads'],
    'Documents' => ['icon' => '📄', 'path' => 'Documents'],
    'Pictures' => ['icon' => '🖼️', 'path' => 'Pictures'],
    'Music' => ['icon' => '🎵', 'path' => 'Music'],
    'Videos' => ['icon' => '🎬', 'path' => 'Videos']
];