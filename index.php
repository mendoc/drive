<?php
session_start();

class HiddenManager {
    private $hiddenFile;
    
    public function __construct($baseDir) {
        $this->hiddenFile = $baseDir . DIRECTORY_SEPARATOR . '.hidden';
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
}

// Gestion des actions AJAX
if (isset($_POST['action']) && $_POST['action'] === 'hide') {
    $pathToHide = $_POST['path'] ?? '';
    if ($pathToHide) {
        $explorer = new FileExplorer('.');
        $hiddenManager = new HiddenManager('.');
        $hiddenManager->addHiddenPath($pathToHide);
        echo json_encode(['success' => true]);
        exit;
    }
}

$explorer = new FileExplorer('.');
$items = $explorer->getDirectoryContents();
$quickAccess = $explorer->getQuickAccessItems();
$breadcrumbs = $explorer->getBreadcrumbs();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorateur de fichiers</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="window">
        <div class="title-bar">
            <i class="fas fa-folder icon"></i>
            <span class="title"><?php echo htmlspecialchars($explorer->getCurrentPath()); ?></span>
            <div class="window-controls">
                <button class="control-btn minimize"></button>
                <button class="control-btn maximize"></button>
                <button class="control-btn close"></button>
            </div>
        </div>
        
        <div class="toolbar">
            <div class="nav-buttons">
                <button class="nav-btn" onclick="history.back()" title="Précédent">
                    <i class="fas fa-arrow-left"></i>
                </button>
                <button class="nav-btn" onclick="history.forward()" title="Suivant">
                    <i class="fas fa-arrow-right"></i>
                </button>
                <button class="nav-btn" onclick="location.reload()" title="Actualiser">
                    <i class="fas fa-redo"></i>
                </button>
            </div>
            
            <div class="address-bar">
                <?php foreach ($breadcrumbs as $index => $breadcrumb): ?>
                    <?php if ($index > 0): ?><i class="fas fa-chevron-right" style="margin: 0 8px; color: #ccc; font-size: 10px;"></i><?php endif; ?>
                    <a href="?dir=<?php echo urlencode($breadcrumb['path']); ?>" style="color: #0078d4; text-decoration: none;">
                        <?php echo htmlspecialchars($breadcrumb['name']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <div class="view-controls">
                <button class="view-btn active" id="grid-view" title="Vue en grille">
                    <i class="fas fa-th"></i>
                </button>
                <button class="view-btn" id="list-view" title="Vue en liste">
                    <i class="fas fa-list"></i>
                </button>
            </div>
            
            <div style="position: relative;">
                <input type="text" class="search-box" placeholder="Rechercher">
                <i class="fas fa-search search-icon"></i>
            </div>
        </div>
        
        <div class="main-content">
            <div class="sidebar">
                <div class="sidebar-section">
                    <div class="sidebar-title">Favoris</div>
                    <?php foreach ($quickAccess as $item): ?>
                        <a href="?dir=<?php echo urlencode($item['path']); ?>" class="sidebar-item">
                            <span class="sidebar-icon"><?php echo $item['icon']; ?></span>
                            <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
                
                <div class="sidebar-section">
                    <div class="sidebar-title">Ce PC</div>
                    <a href="?dir=C:" class="sidebar-item">
                        <span class="sidebar-icon">💾</span>
                        Disque local (C:)
                    </a>
                    <div class="sidebar-item" style="color: #999; font-size: 12px; padding-left: 40px;">
                        <?php
                        $totalSpace = disk_total_space('C:');
                        $freeSpace = disk_free_space('C:');
                        if ($totalSpace && $freeSpace) {
                            $usedSpace = $totalSpace - $freeSpace;
                            echo number_format($usedSpace / 1024 / 1024 / 1024, 1) . ' GB libre sur ' . 
                                 number_format($totalSpace / 1024 / 1024 / 1024, 1) . ' GB';
                        }
                        ?>
                    </div>
                </div>
            </div>
            
            <div class="content-area">
                <div class="content-header">
                    <div class="quick-access">
                        <h2 class="section-title">Accès rapide</h2>
                        <div class="quick-grid">
                            <?php foreach ($quickAccess as $item): ?>
                                <a href="?dir=<?php echo urlencode($item['path']); ?>" class="quick-item">
                                    <div class="quick-icon"><?php echo $item['icon']; ?></div>
                                    <div class="quick-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                </a>
                            <?php endforeach; ?>
                            <div class="quick-item" style="border: 2px dashed #ddd; background: transparent;">
                                <div class="quick-icon" style="color: #999;">♻️</div>
                                <div class="quick-name" style="color: #999;">Corbeille</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="files-section">
                    <?php if (!empty($items)): ?>
                        <!-- Vue en grille -->
                        <div class="files-grid" id="grid-container">
                            <?php foreach ($items as $item): ?>
                                <?php if ($item['type'] === 'directory'): ?>
                                    <a href="?dir=<?php echo urlencode($item['path']); ?>" class="file-item <?php echo $item['type']; ?>" data-path="<?php echo htmlspecialchars($item['path']); ?>">
                                        <div class="file-options" onclick="event.preventDefault(); event.stopPropagation(); toggleMenu(this);">
                                            <i class="fas fa-ellipsis-h"></i>
                                            <div class="options-menu">
                                                <div class="option-item" onclick="hideItem('<?php echo addslashes($item['path']); ?>')">
                                                    <i class="fas fa-eye-slash"></i> Masquer
                                                </div>
                                            </div>
                                        </div>
                                        <div class="file-icon">
                                            <?php echo $explorer->getFileIcon($item['name'], $item['type']); ?>
                                        </div>
                                        <div class="file-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                    </a>
                                <?php else: ?>
                                    <div class="file-item <?php echo $item['type']; ?>" data-path="<?php echo htmlspecialchars($item['path']); ?>">
                                        <div class="file-options" onclick="event.stopPropagation(); toggleMenu(this);">
                                            <i class="fas fa-ellipsis-h"></i>
                                            <div class="options-menu">
                                                <div class="option-item" onclick="hideItem('<?php echo addslashes($item['path']); ?>')">
                                                    <i class="fas fa-eye-slash"></i> Masquer
                                                </div>
                                            </div>
                                        </div>
                                        <div class="file-icon">
                                            <?php echo $explorer->getFileIcon($item['name'], $item['type']); ?>
                                        </div>
                                        <div class="file-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <?php if ($item['size']): ?>
                                            <div class="file-size"><?php echo $explorer->formatFileSize($item['size']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Vue en liste -->
                        <div class="files-list" id="list-container" style="display: none;">
                            <div class="list-header">
                                <div class="list-col list-col-name">Nom</div>
                                <div class="list-col list-col-date">Date de modification</div>
                                <div class="list-col list-col-type">Type</div>
                                <div class="list-col list-col-size">Taille</div>
                            </div>
                            <?php foreach ($items as $item): ?>
                                <?php if ($item['type'] === 'directory'): ?>
                                    <a href="?dir=<?php echo urlencode($item['path']); ?>" class="list-item <?php echo $item['type']; ?>" data-path="<?php echo htmlspecialchars($item['path']); ?>">
                                        <div class="list-col list-col-name">
                                            <span class="list-icon"><?php echo $explorer->getFileIcon($item['name'], $item['type']); ?></span>
                                            <span class="list-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        </div>
                                        <div class="list-col list-col-date">
                                            <?php echo date('d/m/Y H:i', $item['modified']); ?>
                                        </div>
                                        <div class="list-col list-col-type">Dossier de fichiers</div>
                                        <div class="list-col list-col-size">--</div>
                                        <div class="list-options" onclick="event.preventDefault(); event.stopPropagation(); toggleMenu(this);">
                                            <i class="fas fa-ellipsis-h"></i>
                                            <div class="options-menu">
                                                <div class="option-item" onclick="hideItem('<?php echo addslashes($item['path']); ?>')">
                                                    <i class="fas fa-eye-slash"></i> Masquer
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                <?php else: ?>
                                    <div class="list-item <?php echo $item['type']; ?>" data-path="<?php echo htmlspecialchars($item['path']); ?>">
                                        <div class="list-col list-col-name">
                                            <span class="list-icon"><?php echo $explorer->getFileIcon($item['name'], $item['type']); ?></span>
                                            <span class="list-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        </div>
                                        <div class="list-col list-col-date">
                                            <?php echo date('d/m/Y H:i', $item['modified']); ?>
                                        </div>
                                        <div class="list-col list-col-type">
                                            <?php 
                                            $ext = strtoupper(pathinfo($item['name'], PATHINFO_EXTENSION));
                                            echo $ext ? "Fichier $ext" : "Fichier";
                                            ?>
                                        </div>
                                        <div class="list-col list-col-size">
                                            <?php echo $item['size'] ? $explorer->formatFileSize($item['size']) : '--'; ?>
                                        </div>
                                        <div class="list-options" onclick="event.stopPropagation(); toggleMenu(this);">
                                            <i class="fas fa-ellipsis-h"></i>
                                            <div class="options-menu">
                                                <div class="option-item" onclick="hideItem('<?php echo addslashes($item['path']); ?>')">
                                                    <i class="fas fa-eye-slash"></i> Masquer
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 50px; color: #666;">
                            <i class="fas fa-folder-open" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                            <p>Ce dossier est vide</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modale de confirmation -->
    <div class="modal-overlay" id="confirmModal" style="display: none;">
        <div class="modal animate__animated">
            <div class="modal-header">
                <h3><i class="fas fa-eye-slash"></i> Confirmer le masquage</h3>
            </div>
            <div class="modal-body">
                <p>Voulez-vous vraiment masquer cet élément ?</p>
                <p class="modal-filename"></p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmHide()">
                    <i class="fas fa-eye-slash"></i> Masquer
                </button>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const gridBtn = document.getElementById('grid-view');
            const listBtn = document.getElementById('list-view');
            const gridContainer = document.getElementById('grid-container');
            const listContainer = document.getElementById('list-container');
            
            function setGridView() {
                gridBtn.classList.add('active');
                listBtn.classList.remove('active');
                gridContainer.style.display = 'grid';
                listContainer.style.display = 'none';
                localStorage.setItem('fileExplorerView', 'grid');
            }
            
            function setListView() {
                listBtn.classList.add('active');
                gridBtn.classList.remove('active');
                gridContainer.style.display = 'none';
                listContainer.style.display = 'block';
                localStorage.setItem('fileExplorerView', 'list');
            }
            
            // Charger la préférence sauvegardée
            const savedView = localStorage.getItem('fileExplorerView');
            if (savedView === 'list') {
                setListView();
            } else {
                setGridView(); // Par défaut
            }
            
            gridBtn.addEventListener('click', setGridView);
            listBtn.addEventListener('click', setListView);
        });
        
        // Gestion du menu contextuel
        function toggleMenu(element) {
            // Fermer tous les autres menus
            document.querySelectorAll('.options-menu').forEach(menu => {
                if (menu !== element.querySelector('.options-menu')) {
                    menu.style.display = 'none';
                }
            });
            
            const menu = element.querySelector('.options-menu');
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
        }
        
        // Variables globales pour la modale
        let currentHidePath = '';
        
        // Afficher la modale de confirmation
        function hideItem(path) {
            currentHidePath = path;
            const filename = path.split(/[\\\/]/).pop();
            document.querySelector('.modal-filename').textContent = filename;
            
            const modal = document.getElementById('confirmModal');
            const modalContent = modal.querySelector('.modal');
            
            modal.style.display = 'flex';
            modalContent.classList.remove('animate__fadeOut', 'animate__zoomOut');
            modalContent.classList.add('animate__fadeIn', 'animate__zoomIn');
        }
        
        // Fermer la modale
        function closeModal() {
            const modal = document.getElementById('confirmModal');
            const modalContent = modal.querySelector('.modal');
            
            modalContent.classList.remove('animate__fadeIn', 'animate__zoomIn');
            modalContent.classList.add('animate__fadeOut', 'animate__zoomOut');
            
            setTimeout(() => {
                modal.style.display = 'none';
                currentHidePath = '';
            }, 300);
        }
        
        // Confirmer le masquage
        function confirmHide() {
            if (currentHidePath) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=hide&path=' + encodeURIComponent(currentHidePath)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeModal();
                        setTimeout(() => {
                            location.reload();
                        }, 300);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    closeModal();
                });
            }
        }
        
        // Fermer les menus en cliquant ailleurs
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.file-options') && !e.target.closest('.list-options')) {
                document.querySelectorAll('.options-menu').forEach(menu => {
                    menu.style.display = 'none';
                });
            }
        });
        
        // Fermer la modale en cliquant sur l'overlay
        document.getElementById('confirmModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Fermer la modale avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('confirmModal');
                if (modal.style.display !== 'none') {
                    closeModal();
                }
            }
        });
    </script>
</body>
</html>