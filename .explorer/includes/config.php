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

// Configuration d'upload
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50 MB en bytes
define('ALLOWED_FILE_TYPES', [
    // Images
    'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
    // Documents
    'txt', 'doc', 'docx', 'pdf', 'rtf', 'odt', 'xls', 'xlsx', 'ppt', 'pptx',
    // Archives
    'zip', 'rar', '7z', 'tar', 'gz',
    // Multimédia
    'mp3', 'wav', 'flac', 'aac', 'm4a', 'mp4', 'avi', 'mkv', 'mov', 'wmv',
    // Code
    'css', 'js', 'json', 'xml', 'csv'
]);
define('FORBIDDEN_FILE_TYPES', ['php', 'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'ps1']);
define('UPLOAD_DIR_PERMISSIONS', 0755);

// Configuration des thumbnails
define('THUMBNAIL_SIZE', 150); // Taille maximale en pixels
define('THUMBNAIL_QUALITY', 85); // Qualité JPEG (0-100)
define('THUMBNAIL_CACHE_DURATION', 2592000); // 30 jours en secondes
define('THUMBNAIL_MAX_CACHE_SIZE', 100 * 1024 * 1024); // 100 MB
define('SUPPORTED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']);
define('THUMBNAIL_DIR', EXPLORER_DIR . '/thumbnails');

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