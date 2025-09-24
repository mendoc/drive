<?php
session_start();

// Inclusion des fichiers du framework
require_once '.explorer/includes/config.php';
require_once '.explorer/includes/handlers.php';
require_once '.explorer/classes/FileExplorer.php';

// Gestion des requêtes AJAX
if (handleAjaxRequest()) {
    exit;
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
    <link href=".explorer/assets/style.css" rel="stylesheet">
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

            <div class="action-buttons">
                <button class="action-btn" onclick="showCreateFolderModal()" title="Nouveau dossier">
                    <i class="fas fa-folder-plus"></i>
                    <span>Nouveau dossier</span>
                </button>
                <button class="action-btn" onclick="showUploadModal()" title="Importer fichier">
                    <i class="fas fa-upload"></i>
                    <span>Importer fichier</span>
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
                            <a href="?dir=<?php echo urlencode('.explorer/trash'); ?>" class="quick-item" style="border: 2px dashed #ddd; background: transparent;">
                                <div class="quick-icon" style="color: #999;">♻️</div>
                                <div class="quick-name" style="color: #999;">Corbeille</div>
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="files-section">
                    <?php if (!empty($items)): ?>
                        <!-- Vue en grille -->
                        <div class="files-grid" id="grid-container">
                            <?php foreach ($items as $item): ?>
                                <?php if ($item['type'] === 'directory'): ?>
                                    <a href="?dir=<?php echo urlencode($explorer->getRelativePath($item['path'])); ?>" class="file-item <?php echo $item['type']; ?>" data-path="<?php echo htmlspecialchars($item['path']); ?>">
                                        <div class="file-options" onclick="event.preventDefault(); event.stopPropagation(); toggleMenu(this);">
                                            <i class="fas fa-ellipsis-h"></i>
                                            <div class="options-menu">
                                                <div class="option-item" onclick="hideItem('<?php echo addslashes($item['path']); ?>')">
                                                    <i class="fas fa-eye-slash"></i> Masquer
                                                </div>
                                                <div class="option-item" onclick="moveToTrash('<?php echo addslashes($item['path']); ?>')">
                                                    <i class="fas fa-trash"></i> Mettre à la corbeille
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
                                                <div class="option-item" onclick="moveToTrash('<?php echo addslashes($item['path']); ?>')">
                                                    <i class="fas fa-trash"></i> Mettre à la corbeille
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
                                    <a href="?dir=<?php echo urlencode($explorer->getRelativePath($item['path'])); ?>" class="list-item <?php echo $item['type']; ?>" data-path="<?php echo htmlspecialchars($item['path']); ?>">
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
                                                <div class="option-item" onclick="moveToTrash('<?php echo addslashes($item['path']); ?>')">
                                                    <i class="fas fa-trash"></i> Mettre à la corbeille
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
                                                <div class="option-item" onclick="moveToTrash('<?php echo addslashes($item['path']); ?>')">
                                                    <i class="fas fa-trash"></i> Mettre à la corbeille
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

    <!-- Modale de création de dossier -->
    <div class="modal-overlay" id="createFolderModal" style="display: none;">
        <div class="modal animate__animated">
            <div class="modal-header">
                <h3><i class="fas fa-folder-plus"></i> Créer un nouveau dossier</h3>
            </div>
            <div class="modal-body">
                <p>Entrez le nom du nouveau dossier :</p>
                <input type="text" id="folderNameInput" class="modal-input" placeholder="Nom du dossier" maxlength="255">
                <div id="folderError" class="modal-error" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeCreateFolderModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmCreateFolder()">
                    <i class="fas fa-folder-plus"></i> Créer
                </button>
            </div>
        </div>
    </div>

    <!-- Modale de confirmation de suppression -->
    <div class="modal-overlay" id="trashModal" style="display: none;">
        <div class="modal animate__animated">
            <div class="modal-header">
                <h3><i class="fas fa-trash"></i> Confirmer la suppression</h3>
            </div>
            <div class="modal-body">
                <p>Voulez-vous vraiment déplacer cet élément vers la corbeille ?</p>
                <p class="modal-filename-trash"></p>
                <p style="font-size: 12px; color: #666; margin-top: 10px;">
                    <i class="fas fa-info-circle"></i> L'élément sera déplacé dans la corbeille et pourra être restauré.
                </p>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeTrashModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmMoveToTrash()">
                    <i class="fas fa-trash"></i> Mettre à la corbeille
                </button>
            </div>
        </div>
    </div>

    <!-- Modale d'upload -->
    <div class="modal-overlay" id="uploadModal" style="display: none;">
        <div class="modal animate__animated">
            <div class="modal-header">
                <h3><i class="fas fa-upload"></i> Importer des fichiers</h3>
            </div>
            <div class="modal-body">
                <div class="upload-section">
                    <div class="drop-zone" id="dropZone">
                        <div class="drop-zone-content">
                            <i class="fas fa-cloud-upload-alt drop-icon"></i>
                            <h4>Glissez vos fichiers ici</h4>
                            <p>ou</p>
                            <button class="upload-browse-btn" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-folder-open"></i> Parcourir les fichiers
                            </button>
                            <input type="file" id="fileInput" multiple style="display: none;" accept="<?php
                                echo implode(',', array_map(function($ext) { return '.' . $ext; }, ALLOWED_FILE_TYPES));
                            ?>">
                        </div>
                        <div class="drop-zone-dragover" style="display: none;">
                            <i class="fas fa-download drop-icon-large"></i>
                            <h3>Relâchez pour importer</h3>
                        </div>
                    </div>

                    <div class="file-info">
                        <p class="file-limits">
                            <i class="fas fa-info-circle"></i>
                            Taille maximale : <?php
                                $maxSize = MAX_FILE_SIZE / 1024 / 1024;
                                echo round($maxSize, 1) . ' MB';
                            ?> par fichier
                        </p>
                        <p class="file-types">
                            Types autorisés : <?php echo implode(', ', array_slice(ALLOWED_FILE_TYPES, 0, 8)); ?>...
                        </p>
                    </div>

                    <div class="upload-progress" id="uploadProgress" style="display: none;">
                        <div class="upload-progress-bar">
                            <div class="upload-progress-fill" id="progressFill"></div>
                        </div>
                        <div class="upload-status" id="uploadStatus">Préparation...</div>
                    </div>

                    <div class="selected-files" id="selectedFiles" style="display: none;">
                        <h4>Fichiers sélectionnés :</h4>
                        <div class="selected-files-list" id="selectedFilesList"></div>
                    </div>

                    <div class="upload-error" id="uploadError" style="display: none;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeUploadModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button class="modal-btn modal-btn-confirm" id="uploadBtn" onclick="startUpload()" disabled>
                    <i class="fas fa-upload"></i> Importer
                </button>
            </div>
        </div>
    </div>

    <script src=".explorer/assets/app.js"></script>
</body>
</html>