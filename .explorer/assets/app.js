// Explorateur de fichiers PHP - JavaScript principal

// Fonction pour raccourcir les noms de fichiers trop longs
function truncateFileName(filename, maxLength) {
    if (filename.length <= maxLength) {
        return filename;
    }

    // Séparer le nom et l'extension
    const lastDotIndex = filename.lastIndexOf('.');
    let name = filename;
    let extension = '';

    if (lastDotIndex > 0) {
        name = filename.substring(0, lastDotIndex);
        extension = filename.substring(lastDotIndex);
    }

    // Calculer combien de caractères on peut garder
    const availableLength = maxLength - 3 - extension.length; // 3 pour "..."

    if (availableLength <= 0) {
        // Si même avec "..." ça ne rentre pas, couper brutalement
        return filename.substring(0, maxLength - 3) + '...';
    }

    // Diviser équitablement entre le début et la fin
    const startLength = Math.ceil(availableLength / 2);
    const endLength = Math.floor(availableLength / 2);

    const start = name.substring(0, startLength);
    const end = name.substring(name.length - endLength);

    return start + '...' + end + extension;
}

document.addEventListener('DOMContentLoaded', function() {
    const gridBtn = document.getElementById('grid-view');
    const listBtn = document.getElementById('list-view');
    const gridContainer = document.getElementById('grid-container');
    const listContainer = document.getElementById('list-container');

    function setGridView() {
        if (gridBtn) gridBtn.classList.add('active');
        if (listBtn) listBtn.classList.remove('active');
        if (gridContainer) gridContainer.style.display = 'grid';
        if (listContainer) listContainer.style.display = 'none';
        localStorage.setItem('fileExplorerView', 'grid');
    }

    function setListView() {
        if (listBtn) listBtn.classList.add('active');
        if (gridBtn) gridBtn.classList.remove('active');
        if (gridContainer) gridContainer.style.display = 'none';
        if (listContainer) listContainer.style.display = 'block';
        localStorage.setItem('fileExplorerView', 'list');
    }

    // Seulement configurer les vues si les conteneurs existent (pas dans les dossiers vides)
    if (gridContainer && listContainer) {
        // Charger la préférence sauvegardée
        const savedView = localStorage.getItem('fileExplorerView');
        if (savedView === 'list') {
            setListView();
        } else {
            setGridView(); // Par défaut
        }

        if (gridBtn) gridBtn.addEventListener('click', setGridView);
        if (listBtn) listBtn.addEventListener('click', setListView);
    } else {
        // Dans les dossiers vides, s'assurer que les boutons de vue restent dans un état cohérent
        if (gridBtn) gridBtn.classList.add('active');
        if (listBtn) listBtn.classList.remove('active');
    }
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
                    reloadCurrentDirectory();
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
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('confirmModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
});

// Fermer la modale avec Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const confirmModal = document.getElementById('confirmModal');
        const createFolderModal = document.getElementById('createFolderModal');
        const uploadModal = document.getElementById('uploadModal');
        const trashModal = document.getElementById('trashModal');
        const renameModal = document.getElementById('renameModal');

        if (confirmModal && confirmModal.style.display !== 'none') {
            closeModal();
        } else if (createFolderModal && createFolderModal.style.display !== 'none') {
            closeCreateFolderModal();
        } else if (uploadModal && uploadModal.style.display !== 'none') {
            closeUploadModal();
        } else if (trashModal && trashModal.style.display !== 'none') {
            closeTrashModal();
        } else if (renameModal && renameModal.style.display !== 'none') {
            closeRenameModal();
        }
    }
});

// === FONCTIONNALITÉ CRÉATION DE DOSSIER ===

// Variables globales pour la création de dossier
let currentDirectory = '';

// Initialiser le répertoire courant
document.addEventListener('DOMContentLoaded', function() {
    // Récupérer le répertoire courant depuis l'URL ou utiliser le répertoire par défaut
    const urlParams = new URLSearchParams(window.location.search);
    currentDirectory = urlParams.get('dir');

    // Si pas de répertoire spécifié ou si c'est un répertoire système, utiliser le répertoire de travail
    if (!currentDirectory || currentDirectory === 'C:' || currentDirectory.startsWith('C:\\')) {
        currentDirectory = '.';
    }

    console.log('Current directory set to:', currentDirectory);
});

// Afficher la modale de création de dossier
function showCreateFolderModal() {
    const modal = document.getElementById('createFolderModal');
    const modalContent = modal.querySelector('.modal');
    const input = document.getElementById('folderNameInput');
    const errorDiv = document.getElementById('folderError');

    // Réinitialiser le formulaire
    input.value = '';
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';

    // Afficher la modale
    modal.style.display = 'flex';
    modalContent.classList.remove('animate__fadeOut', 'animate__zoomOut');
    modalContent.classList.add('animate__fadeIn', 'animate__zoomIn');

    // Focus sur l'input
    setTimeout(() => input.focus(), 100);
}

// Fermer la modale de création de dossier
function closeCreateFolderModal() {
    const modal = document.getElementById('createFolderModal');
    const modalContent = modal.querySelector('.modal');

    modalContent.classList.remove('animate__fadeIn', 'animate__zoomIn');
    modalContent.classList.add('animate__fadeOut', 'animate__zoomOut');

    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Confirmer la création du dossier
function confirmCreateFolder() {
    const input = document.getElementById('folderNameInput');
    const errorDiv = document.getElementById('folderError');
    const folderName = input.value.trim();

    // Validation côté client
    if (!folderName) {
        showError('Le nom du dossier ne peut pas être vide');
        return;
    }

    if (folderName.length > 255) {
        showError('Le nom est trop long (maximum 255 caractères)');
        return;
    }

    // Caractères interdits
    const forbiddenChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|'];
    for (let char of forbiddenChars) {
        if (folderName.includes(char)) {
            showError('Caractères interdits : / \\ : * ? " < > |');
            return;
        }
    }

    console.log('Creating folder:', folderName, 'in directory:', currentDirectory);

    // Envoyer la requête de création
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=create_folder&name=' + encodeURIComponent(folderName) + '&current_dir=' + encodeURIComponent(currentDirectory)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeCreateFolderModal();
            // Recharger la page pour voir le nouveau dossier
            setTimeout(() => {
                reloadCurrentDirectory();
            }, 300);
        } else {
            showError(data.error || 'Erreur lors de la création du dossier');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showError('Erreur de communication avec le serveur');
    });
}

// Afficher une erreur dans la modale
function showError(message) {
    const errorDiv = document.getElementById('folderError');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
}

// Gestion de la touche Entrée dans l'input
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('folderNameInput');
    if (input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmCreateFolder();
            }
        });
    }
});

// Fermer la modale de création en cliquant sur l'overlay
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('createFolderModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeCreateFolderModal();
            }
        });
    }
});

// === FONCTIONNALITÉ UPLOAD DE FICHIERS ===

// Variables globales pour l'upload
let selectedFilesForUpload = [];
let isUploading = false;

// Afficher la modale d'upload
function showUploadModal() {
    const modal = document.getElementById('uploadModal');
    const modalContent = modal.querySelector('.modal');

    // Réinitialiser l'état
    resetUploadModal();

    // Afficher la modale
    modal.style.display = 'flex';
    modalContent.classList.remove('animate__fadeOut', 'animate__zoomOut');
    modalContent.classList.add('animate__fadeIn', 'animate__zoomIn');

    // Configurer les event listeners
    setupUploadEventListeners();
}

// Fermer la modale d'upload
function closeUploadModal() {
    if (isUploading) {
        if (!confirm('Un upload est en cours. Voulez-vous vraiment annuler ?')) {
            return;
        }
    }

    const modal = document.getElementById('uploadModal');
    const modalContent = modal.querySelector('.modal');

    modalContent.classList.remove('animate__fadeIn', 'animate__zoomIn');
    modalContent.classList.add('animate__fadeOut', 'animate__zoomOut');

    setTimeout(() => {
        modal.style.display = 'none';
        resetUploadModal();
    }, 300);
}

// Réinitialiser la modale d'upload
function resetUploadModal() {
    selectedFilesForUpload = [];
    isUploading = false;

    // Réinitialiser les éléments UI
    const elements = {
        uploadProgress: document.getElementById('uploadProgress'),
        selectedFiles: document.getElementById('selectedFiles'),
        selectedFilesList: document.getElementById('selectedFilesList'),
        uploadError: document.getElementById('uploadError'),
        uploadBtn: document.getElementById('uploadBtn'),
        progressFill: document.getElementById('progressFill'),
        uploadStatus: document.getElementById('uploadStatus')
    };

    if (elements.uploadProgress) elements.uploadProgress.style.display = 'none';
    if (elements.selectedFiles) elements.selectedFiles.style.display = 'none';
    if (elements.selectedFilesList) elements.selectedFilesList.innerHTML = '';
    if (elements.uploadError) {
        elements.uploadError.style.display = 'none';
        elements.uploadError.innerHTML = '';
    }
    if (elements.uploadBtn) {
        elements.uploadBtn.disabled = true;
        elements.uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Importer';
    }
    if (elements.progressFill) elements.progressFill.style.width = '0%';
    if (elements.uploadStatus) elements.uploadStatus.textContent = 'Préparation...';
}

// Configurer les event listeners pour l'upload
function setupUploadEventListeners() {
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');

    if (!dropZone || !fileInput) return;

    // Drag & Drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, highlight, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, unhighlight, false);
    });

    function highlight() {
        dropZone.querySelector('.drop-zone-content').style.display = 'none';
        dropZone.querySelector('.drop-zone-dragover').style.display = 'flex';
    }

    function unhighlight() {
        dropZone.querySelector('.drop-zone-content').style.display = 'flex';
        dropZone.querySelector('.drop-zone-dragover').style.display = 'none';
    }

    dropZone.addEventListener('drop', handleDrop, false);
    fileInput.addEventListener('change', handleFileSelect, false);
}

// Gestion du drop de fichiers
function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    handleFiles(files);
}

// Gestion de la sélection de fichiers
function handleFileSelect(e) {
    const files = e.target.files;
    handleFiles(files);
}

// Traiter les fichiers sélectionnés
function handleFiles(files) {
    selectedFilesForUpload = Array.from(files);

    if (selectedFilesForUpload.length === 0) {
        return;
    }

    // Validation côté client
    const validFiles = [];
    const errors = [];

    selectedFilesForUpload.forEach(file => {
        const validation = validateFileClient(file);
        if (validation.valid) {
            validFiles.push(file);
        } else {
            errors.push(`${file.name}: ${validation.error}`);
        }
    });

    selectedFilesForUpload = validFiles;

    // Afficher les erreurs s'il y en a
    const errorElement = document.getElementById('uploadError');
    if (errors.length > 0) {
        errorElement.innerHTML = '<h4>Fichiers non valides :</h4><ul>' +
            errors.map(error => `<li>${error}</li>`).join('') + '</ul>';
        errorElement.style.display = 'block';
    } else {
        errorElement.style.display = 'none';
    }

    // Afficher les fichiers valides
    displaySelectedFiles();

    // Activer/désactiver le bouton d'upload
    const uploadBtn = document.getElementById('uploadBtn');
    uploadBtn.disabled = selectedFilesForUpload.length === 0;
}

// Validation côté client
function validateFileClient(file) {
    // Taille maximale (50 MB)
    if (file.size > 50 * 1024 * 1024) {
        return { valid: false, error: 'Fichier trop volumineux (max 50 MB)' };
    }

    if (file.size === 0) {
        return { valid: false, error: 'Fichier vide' };
    }

    // Extension
    const extension = file.name.split('.').pop().toLowerCase();
    const allowedTypes = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg',
        'txt', 'doc', 'docx', 'pdf', 'rtf', 'odt', 'xls', 'xlsx', 'ppt', 'pptx',
        'zip', 'rar', '7z', 'tar', 'gz',
        'mp3', 'wav', 'flac', 'aac', 'm4a', 'mp4', 'avi', 'mkv', 'mov', 'wmv',
        'css', 'js', 'json', 'xml', 'csv'
    ];

    if (!allowedTypes.includes(extension)) {
        return { valid: false, error: 'Type de fichier non autorisé' };
    }

    return { valid: true };
}

// Afficher les fichiers sélectionnés
function displaySelectedFiles() {
    const selectedFilesElement = document.getElementById('selectedFiles');
    const selectedFilesList = document.getElementById('selectedFilesList');

    if (selectedFilesForUpload.length === 0) {
        selectedFilesElement.style.display = 'none';
        return;
    }

    selectedFilesList.innerHTML = '';
    selectedFilesForUpload.forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'selected-file-item';
        fileItem.innerHTML = `
            <div class="file-info">
                <i class="fas fa-file"></i>
                <span class="file-name">${file.name}</span>
                <span class="file-size">(${formatFileSize(file.size)})</span>
            </div>
            <button class="remove-file-btn" onclick="removeFile(${index})" title="Supprimer">
                <i class="fas fa-times"></i>
            </button>
        `;
        selectedFilesList.appendChild(fileItem);
    });

    selectedFilesElement.style.display = 'block';
}

// Supprimer un fichier de la sélection
function removeFile(index) {
    selectedFilesForUpload.splice(index, 1);
    displaySelectedFiles();

    const uploadBtn = document.getElementById('uploadBtn');
    uploadBtn.disabled = selectedFilesForUpload.length === 0;
}

// Formater la taille de fichier
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// Commencer l'upload
function startUpload() {
    if (selectedFilesForUpload.length === 0 || isUploading) return;

    isUploading = true;

    // Préparer le formulaire
    const formData = new FormData();
    formData.append('action', 'upload');
    formData.append('current_dir', currentDirectory);

    selectedFilesForUpload.forEach(file => {
        formData.append('files[]', file);
    });

    // Afficher la barre de progression
    const uploadProgress = document.getElementById('uploadProgress');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadStatus = document.getElementById('uploadStatus');

    uploadProgress.style.display = 'block';
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Upload en cours...';

    // Créer la requête XMLHttpRequest pour le suivi de progression
    const xhr = new XMLHttpRequest();

    // Suivi de progression
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            updateProgress(percentComplete);
        }
    });

    // Réponse de la requête
    xhr.addEventListener('load', function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                handleUploadResponse(response);
            } catch (e) {
                handleUploadError('Réponse serveur invalide');
            }
        } else {
            handleUploadError('Erreur serveur (Code: ' + xhr.status + ')');
        }
    });

    xhr.addEventListener('error', function() {
        handleUploadError('Erreur de connexion');
    });

    xhr.addEventListener('abort', function() {
        handleUploadError('Upload annulé');
    });

    // Envoyer la requête
    xhr.open('POST', '', true);
    xhr.send(formData);
}

// Mettre à jour la barre de progression
function updateProgress(percent) {
    const progressFill = document.getElementById('progressFill');
    const uploadStatus = document.getElementById('uploadStatus');

    if (progressFill) progressFill.style.width = percent + '%';
    if (uploadStatus) uploadStatus.textContent = `Upload en cours... ${Math.round(percent)}%`;
}

// Gérer la réponse d'upload
function handleUploadResponse(response) {
    const uploadStatus = document.getElementById('uploadStatus');
    const uploadBtn = document.getElementById('uploadBtn');

    if (response.success) {
        updateProgress(100);
        uploadStatus.textContent = response.message;
        uploadBtn.innerHTML = '<i class="fas fa-check"></i> Terminé';

        // Fermer et recharger après 2 secondes
        setTimeout(() => {
            closeUploadModal();
            // Préserver le dossier actuel lors du rechargement
            reloadCurrentDirectory();
        }, 2000);
    } else {
        handleUploadError(response.error, response.details);
    }

    isUploading = false;
}

// Gérer les erreurs d'upload
function handleUploadError(error, details = null) {
    const uploadError = document.getElementById('uploadError');
    const uploadBtn = document.getElementById('uploadBtn');
    const uploadProgress = document.getElementById('uploadProgress');

    let errorMessage = '<h4>Erreur d\'upload :</h4><p>' + error + '</p>';

    if (details && details.length > 0) {
        errorMessage += '<ul>' + details.map(detail => `<li>${detail}</li>`).join('') + '</ul>';
    }

    uploadError.innerHTML = errorMessage;
    uploadError.style.display = 'block';

    uploadProgress.style.display = 'none';
    uploadBtn.disabled = false;
    uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Réessayer';

    isUploading = false;
}

// Fermer la modale d'upload en cliquant sur l'overlay
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('uploadModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeUploadModal();
            }
        });
    }
});

// === FONCTIONNALITÉ CORBEILLE ===

// Variables globales pour la corbeille
let currentTrashPath = '';

// Afficher la modale de confirmation de suppression
function moveToTrash(path) {
    currentTrashPath = path;
    const filename = path.split(/[\\\/]/).pop();
    document.querySelector('.modal-filename-trash').textContent = filename;

    const modal = document.getElementById('trashModal');
    const modalContent = modal.querySelector('.modal');

    modal.style.display = 'flex';
    modalContent.classList.remove('animate__fadeOut', 'animate__zoomOut');
    modalContent.classList.add('animate__fadeIn', 'animate__zoomIn');
}

// Fermer la modale de corbeille
function closeTrashModal() {
    const modal = document.getElementById('trashModal');
    const modalContent = modal.querySelector('.modal');

    modalContent.classList.remove('animate__fadeIn', 'animate__zoomIn');
    modalContent.classList.add('animate__fadeOut', 'animate__zoomOut');

    setTimeout(() => {
        modal.style.display = 'none';
        currentTrashPath = '';
    }, 300);
}

// Confirmer le déplacement vers la corbeille
function confirmMoveToTrash() {
    if (currentTrashPath) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=move_to_trash&path=' + encodeURIComponent(currentTrashPath)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeTrashModal();
                // Afficher un message de succès temporaire
                if (data.message) {
                    showSuccessMessage(data.message);
                }
                setTimeout(() => {
                    reloadCurrentDirectory();
                }, 1000);
            } else {
                closeTrashModal();
                showErrorMessage(data.error || 'Erreur lors de la suppression');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            closeTrashModal();
            showErrorMessage('Erreur de communication avec le serveur');
        });
    }
}

// Afficher un message de succès temporaire
function showSuccessMessage(message) {
    const successDiv = document.createElement('div');
    successDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #4CAF50;
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        z-index: 10000;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-size: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease-out;
    `;
    successDiv.innerHTML = `<i class="fas fa-check-circle" style="margin-right: 8px;"></i>${message}`;

    // Ajouter l'animation CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    `;
    document.head.appendChild(style);

    document.body.appendChild(successDiv);

    setTimeout(() => {
        successDiv.style.animation = 'slideIn 0.3s ease-out reverse';
        setTimeout(() => {
            document.body.removeChild(successDiv);
            document.head.removeChild(style);
        }, 300);
    }, 3000);
}

// Afficher un message d'erreur temporaire
function showErrorMessage(message) {
    const errorDiv = document.createElement('div');
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #f44336;
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        z-index: 10000;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-size: 14px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        animation: slideIn 0.3s ease-out;
    `;
    errorDiv.innerHTML = `<i class="fas fa-exclamation-triangle" style="margin-right: 8px;"></i>${message}`;

    document.body.appendChild(errorDiv);

    setTimeout(() => {
        errorDiv.style.animation = 'slideIn 0.3s ease-out reverse';
        setTimeout(() => {
            document.body.removeChild(errorDiv);
        }, 300);
    }, 4000);
}

// Fermer la modale de corbeille en cliquant sur l'overlay
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('trashModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeTrashModal();
            }
        });
    }
});

// === FONCTIONNALITÉ RENOMMAGE ===

// Variables globales pour le renommage
let currentRenamePath = '';

// Afficher la modale de renommage
function showRenameModal(path) {
    currentRenamePath = path;
    const filename = path.split(/[\\\/]/).pop();
    const input = document.getElementById('renameInput');
    const errorDiv = document.getElementById('renameError');

    // Pré-remplir avec le nom actuel
    input.value = filename;
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';

    const modal = document.getElementById('renameModal');
    const modalContent = modal.querySelector('.modal');

    modal.style.display = 'flex';
    modalContent.classList.remove('animate__fadeOut', 'animate__zoomOut');
    modalContent.classList.add('animate__fadeIn', 'animate__zoomIn');

    // Focus et sélection du texte (sans l'extension pour les fichiers)
    setTimeout(() => {
        input.focus();
        const dotIndex = filename.lastIndexOf('.');
        if (dotIndex > 0) {
            input.setSelectionRange(0, dotIndex);
        } else {
            input.select();
        }
    }, 100);
}

// Fermer la modale de renommage
function closeRenameModal() {
    const modal = document.getElementById('renameModal');
    const modalContent = modal.querySelector('.modal');

    modalContent.classList.remove('animate__fadeIn', 'animate__zoomIn');
    modalContent.classList.add('animate__fadeOut', 'animate__zoomOut');

    setTimeout(() => {
        modal.style.display = 'none';
        currentRenamePath = '';
    }, 300);
}

// Confirmer le renommage
function confirmRename() {
    const input = document.getElementById('renameInput');
    const errorDiv = document.getElementById('renameError');
    const newName = input.value.trim();

    // Validation côté client
    if (!newName) {
        showRenameError('Le nom ne peut pas être vide');
        return;
    }

    if (newName.length > 255) {
        showRenameError('Le nom est trop long (maximum 255 caractères)');
        return;
    }

    // Caractères interdits
    const forbiddenChars = ['/', '\\', ':', '*', '?', '"', '<', '>', '|'];
    for (let char of forbiddenChars) {
        if (newName.includes(char)) {
            showRenameError('Caractères interdits : / \\ : * ? " < > |');
            return;
        }
    }

    // Vérifier si le nom a changé
    const currentName = currentRenamePath.split(/[\\\/]/).pop();
    if (newName === currentName) {
        showRenameError('Le nouveau nom doit être différent');
        return;
    }

    // Envoyer la requête de renommage
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=rename&old_path=' + encodeURIComponent(currentRenamePath) + '&new_name=' + encodeURIComponent(newName)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            closeRenameModal();
            // Afficher un message de succès
            if (data.message) {
                showSuccessMessage(data.message);
            }
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showRenameError(data.error || 'Erreur lors du renommage');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showRenameError('Erreur de communication avec le serveur');
    });
}

// Afficher une erreur dans la modale de renommage
function showRenameError(message) {
    const errorDiv = document.getElementById('renameError');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
}

// Gestion de la touche Entrée dans l'input de renommage
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('renameInput');
    if (input) {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmRename();
            }
        });
    }
});

// Fermer la modale de renommage en cliquant sur l'overlay
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('renameModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeRenameModal();
            }
        });
    }
});

// Gestion des thumbnails
document.addEventListener('DOMContentLoaded', function() {
    // Délai court pour s'assurer que tous les éléments DOM sont prêts
    setTimeout(() => {
        initThumbnails();
    }, 100);
});

function initThumbnails() {
    // Charger les thumbnails pour les images dans la vue grille
    const gridImages = document.querySelectorAll('.files-grid .file-item[data-path]');
    gridImages.forEach(item => {
        const path = item.getAttribute('data-path');
        if (path && isImageFile(path)) {
            loadThumbnailForGridItem(item, path);
        }
    });

    // Charger les thumbnails pour les images dans la vue liste
    const listImages = document.querySelectorAll('.files-list .list-item[data-path]');
    listImages.forEach(item => {
        const path = item.getAttribute('data-path');
        if (path && isImageFile(path)) {
            loadThumbnailForListItem(item, path);
        }
    });
}

function isImageFile(filePath) {
    const ext = filePath.toLowerCase().split('.').pop();
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'];
    return imageExtensions.includes(ext);
}

function loadThumbnailForGridItem(item, filePath) {
    const iconElement = item.querySelector('.file-icon');
    if (!iconElement) return;

    // Ajouter la classe pour indiquer que l'élément a un thumbnail
    item.classList.add('has-thumbnail');

    // Créer le conteneur thumbnail
    const thumbnailContainer = document.createElement('div');
    thumbnailContainer.className = 'file-thumbnail loading';

    // Remplacer l'icône par le conteneur thumbnail
    iconElement.parentNode.replaceChild(thumbnailContainer, iconElement);

    // Charger l'image
    const img = new Image();
    img.onload = function() {
        thumbnailContainer.classList.remove('loading');
        thumbnailContainer.innerHTML = '';
        thumbnailContainer.appendChild(img);
    };

    img.onerror = function() {
        thumbnailContainer.classList.remove('loading');
        thumbnailContainer.classList.add('error');
        thumbnailContainer.innerHTML = '🖼️';
        // Retirer la classe si le thumbnail échoue
        item.classList.remove('has-thumbnail');
    };

    // Construire l'URL du thumbnail en utilisant le chemin relatif
    const relativePath = getRelativePath(filePath);
    img.src = '?action=thumbnail&path=' + encodeURIComponent(relativePath);
}

function loadThumbnailForListItem(item, filePath) {
    const iconElement = item.querySelector('.list-icon');
    if (!iconElement) return;

    // Créer le conteneur thumbnail
    const thumbnailContainer = document.createElement('div');
    thumbnailContainer.className = 'list-thumbnail loading';

    // Remplacer l'icône par le conteneur thumbnail
    iconElement.parentNode.replaceChild(thumbnailContainer, iconElement);

    // Charger l'image
    const img = new Image();
    img.onload = function() {
        thumbnailContainer.classList.remove('loading');
        thumbnailContainer.innerHTML = '';
        thumbnailContainer.appendChild(img);
    };

    img.onerror = function() {
        thumbnailContainer.classList.remove('loading');
        thumbnailContainer.classList.add('error');
        thumbnailContainer.innerHTML = '🖼️';
    };

    // Construire l'URL du thumbnail en utilisant le chemin relatif
    const relativePath = getRelativePath(filePath);
    img.src = '?action=thumbnail&path=' + encodeURIComponent(relativePath);
}

function getRelativePath(relativePath) {
    // Le chemin est déjà relatif grâce à la correction dans index.php
    // Pas besoin de transformation supplémentaire
    return relativePath;
}

function reloadCurrentDirectory() {
    // Récupérer le répertoire actuel depuis l'URL
    const urlParams = new URLSearchParams(window.location.search);
    const currentDir = urlParams.get('dir');

    // Construire l'URL de rechargement avec le dossier actuel
    let reloadUrl = window.location.pathname;

    if (currentDir) {
        reloadUrl += '?dir=' + encodeURIComponent(currentDir);
    }

    // Ajouter un timestamp pour éviter le cache
    const separator = currentDir ? '&' : '?';
    reloadUrl += separator + 't=' + Date.now();

    // Rediriger vers l'URL construite
    window.location.href = reloadUrl;
}

// Appliquer le raccourcissement des noms aux éléments affichés
document.addEventListener('DOMContentLoaded', function() {
    // Vue grille - 30 caractères max
    document.querySelectorAll('.files-grid .file-name').forEach(element => {
        const fullName = element.textContent.trim();
        if (fullName.length > 30) {
            element.textContent = truncateFileName(fullName, 30);
            element.setAttribute('title', fullName);
        }
    });

    // Vue liste - 40 caractères max
    document.querySelectorAll('.files-list .list-name').forEach(element => {
        const fullName = element.textContent.trim();
        if (fullName.length > 40) {
            element.textContent = truncateFileName(fullName, 40);
            element.setAttribute('title', fullName);
        }
    });
});

// === FONCTIONNALITÉ FEEDBACKS ===

// Afficher la modale de liste des feedbacks
function showFeedbacksModal() {
    const modal = document.getElementById('feedbacksModal');
    const modalContent = modal.querySelector('.modal');

    modal.style.display = 'flex';
    modalContent.classList.remove('animate__fadeOut', 'animate__zoomOut');
    modalContent.classList.add('animate__fadeIn', 'animate__zoomIn');

    // Charger les feedbacks
    loadFeedbacks();
}

// Fermer la modale de liste des feedbacks
function closeFeedbacksModal() {
    const modal = document.getElementById('feedbacksModal');
    const modalContent = modal.querySelector('.modal');

    modalContent.classList.remove('animate__fadeIn', 'animate__zoomIn');
    modalContent.classList.add('animate__fadeOut', 'animate__zoomOut');

    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

// Charger les feedbacks depuis le serveur
function loadFeedbacks() {
    const contentDiv = document.getElementById('feedbacksContent');

    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=get_feedbacks'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            displayFeedbacks(data.feedbacks);
        } else {
            contentDiv.innerHTML = `
                <div style="text-align: center; padding: 30px; color: #e53e3e;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 32px; margin-bottom: 15px;"></i>
                    <p>Erreur lors du chargement des feedbacks</p>
                </div>
            `;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        contentDiv.innerHTML = `
            <div style="text-align: center; padding: 30px; color: #e53e3e;">
                <i class="fas fa-exclamation-triangle" style="font-size: 32px; margin-bottom: 15px;"></i>
                <p>Erreur de communication avec le serveur</p>
            </div>
        `;
    });
}

// Afficher les feedbacks dans la modale
function displayFeedbacks(feedbacks) {
    const contentDiv = document.getElementById('feedbacksContent');

    if (feedbacks.length === 0) {
        contentDiv.innerHTML = `
            <div style="text-align: center; padding: 50px; color: #999;">
                <i class="fas fa-comments" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                <p style="font-size: 16px; margin-bottom: 10px;">Aucun feedback pour le moment</p>
                <p style="font-size: 14px;">Soyez le premier à partager vos remarques !</p>
            </div>
        `;
        return;
    }

    let tableHTML = `
        <div class="feedbacks-table">
            <div class="feedbacks-header">
                <div class="feedback-col-drag"></div>
                <div class="feedback-col-date">Date</div>
                <div class="feedback-col-message">Message</div>
                <div class="feedback-col-actions">Actions</div>
            </div>
            <div class="feedbacks-body" id="feedbacks-body-sortable">
    `;

    feedbacks.forEach(feedback => {
        const date = formatFeedbackDate(feedback.created_at);
        const message = feedback.message || '';
        const feedbackId = feedback.id || '';
        const messagePreview = message.length > 60 ? message.substring(0, 60) + '...' : message;
        const isCompleted = feedback.completed || false;
        const completedClass = isCompleted ? 'completed' : '';
        const checkIcon = isCompleted ? 'fa-check-circle' : 'fa-circle';
        const checkClass = isCompleted ? 'completed' : '';

        tableHTML += `
            <div class="feedback-row ${completedClass}" data-feedback-id="${feedbackId}">
                <div class="feedback-col-drag">
                    <div class="feedback-drag-handle" title="Glisser pour réorganiser">
                        <i class="fas fa-grip-vertical"></i>
                    </div>
                </div>
                <div class="feedback-col-date ${completedClass}">${date}</div>
                <div class="feedback-col-message ${completedClass}">${message}</div>
                <div class="feedback-col-actions">
                    <button class="feedback-toggle-btn ${checkClass}" onclick="toggleFeedbackStatus('${feedbackId}');" title="${isCompleted ? 'Marquer comme non traité' : 'Marquer comme traité'}">
                        <i class="fas ${checkIcon}"></i>
                    </button>
                    <button class="feedback-delete-btn" onclick="showDeleteFeedbackModal('${feedbackId}', '${messagePreview.replace(/'/g, "\\'")}');" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
    });

    tableHTML += `
            </div>
        </div>
    `;

    contentDiv.innerHTML = tableHTML;

    // Initialiser SortableJS après l'affichage du tableau
    initializeFeedbackSortable();
}

// Formater la date du feedback
function formatFeedbackDate(dateString) {
    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${day}/${month}/${year} à ${hours}:${minutes}`;
}

// Afficher la modale d'ajout de feedback
function showAddFeedbackModal() {
    // Fermer la modale de liste
    const feedbacksModal = document.getElementById('feedbacksModal');
    feedbacksModal.style.display = 'none';

    // Ouvrir la modale d'ajout
    const modal = document.getElementById('addFeedbackModal');
    const modalContent = modal.querySelector('.modal');
    const textarea = document.getElementById('feedbackMessage');
    const errorDiv = document.getElementById('feedbackError');
    const charCount = document.getElementById('charCount');

    // Réinitialiser le formulaire
    textarea.value = '';
    charCount.textContent = '0';
    errorDiv.style.display = 'none';
    errorDiv.textContent = '';

    // Réinitialiser le bouton d'envoi
    const submitBtn = modal.querySelector('.modal-btn-confirm');
    if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer';
    }

    modal.style.display = 'flex';
    modalContent.classList.remove('animate__fadeOut', 'animate__zoomOut');
    modalContent.classList.add('animate__fadeIn', 'animate__zoomIn');

    // Focus sur le textarea
    setTimeout(() => textarea.focus(), 100);
}

// Fermer la modale d'ajout de feedback
function closeAddFeedbackModal() {
    const modal = document.getElementById('addFeedbackModal');
    const modalContent = modal.querySelector('.modal');

    modalContent.classList.remove('animate__fadeIn', 'animate__zoomIn');
    modalContent.classList.add('animate__fadeOut', 'animate__zoomOut');

    setTimeout(() => {
        modal.style.display = 'none';
        // Réouvrir la modale de liste
        showFeedbacksModal();
    }, 300);
}

// Soumettre le feedback
function submitFeedback() {
    const textarea = document.getElementById('feedbackMessage');
    const errorDiv = document.getElementById('feedbackError');
    const message = textarea.value.trim();

    // Validation côté client
    if (!message) {
        showFeedbackError('Le message ne peut pas être vide');
        return;
    }

    if (message.length > 500) {
        showFeedbackError('Le message est trop long (maximum 500 caractères)');
        return;
    }

    // Désactiver le bouton pendant l'envoi
    const submitBtn = event.target;
    const originalHTML = submitBtn.innerHTML;
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi...';

    // Envoyer le feedback
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=add_feedback&message=' + encodeURIComponent(message)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Réinitialiser le bouton avant de fermer
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHTML;

            // Fermer la modale d'ajout
            const modal = document.getElementById('addFeedbackModal');
            modal.style.display = 'none';

            // Afficher un message de succès
            showSuccessMessage(data.message || 'Feedback enregistré avec succès');

            // Recharger et afficher la liste des feedbacks
            setTimeout(() => {
                showFeedbacksModal();
            }, 500);
        } else {
            showFeedbackError(data.error || 'Erreur lors de l\'envoi du feedback');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        showFeedbackError('Erreur de communication avec le serveur');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalHTML;
    });
}

// Afficher une erreur dans la modale d'ajout
function showFeedbackError(message) {
    const errorDiv = document.getElementById('feedbackError');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
}

// Compteur de caractères pour le textarea
document.addEventListener('DOMContentLoaded', function() {
    const textarea = document.getElementById('feedbackMessage');
    const charCount = document.getElementById('charCount');

    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            charCount.textContent = this.value.length;
        });
    }
});

// Fermer les modales de feedback en cliquant sur l'overlay
document.addEventListener('DOMContentLoaded', function() {
    const feedbacksModal = document.getElementById('feedbacksModal');
    const addFeedbackModal = document.getElementById('addFeedbackModal');

    if (feedbacksModal) {
        feedbacksModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeFeedbacksModal();
            }
        });
    }

    if (addFeedbackModal) {
        addFeedbackModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeAddFeedbackModal();
            }
        });
    }
});

// Gérer la touche Échap pour les modales de feedback
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const feedbacksModal = document.getElementById('feedbacksModal');
        const addFeedbackModal = document.getElementById('addFeedbackModal');
        const deleteFeedbackModal = document.getElementById('deleteFeedbackModal');

        if (feedbacksModal && feedbacksModal.style.display !== 'none') {
            closeFeedbacksModal();
        } else if (addFeedbackModal && addFeedbackModal.style.display !== 'none') {
            closeAddFeedbackModal();
        } else if (deleteFeedbackModal && deleteFeedbackModal.style.display !== 'none') {
            closeDeleteFeedbackModal();
        }
    }
});

// === SUPPRESSION DE FEEDBACKS ===

// Variable globale pour stocker l'ID du feedback à supprimer
let currentDeleteFeedbackId = '';

// Afficher la modale de confirmation de suppression
function showDeleteFeedbackModal(feedbackId, messagePreview) {
    currentDeleteFeedbackId = feedbackId;

    // Afficher un aperçu du message
    const previewElement = document.getElementById('feedbackPreview');
    previewElement.textContent = messagePreview;

    const modal = document.getElementById('deleteFeedbackModal');
    const modalContent = modal.querySelector('.modal');

    modal.style.display = 'flex';
    modalContent.classList.remove('animate__fadeOut', 'animate__zoomOut');
    modalContent.classList.add('animate__fadeIn', 'animate__zoomIn');
}

// Fermer la modale de confirmation de suppression
function closeDeleteFeedbackModal() {
    const modal = document.getElementById('deleteFeedbackModal');
    const modalContent = modal.querySelector('.modal');

    modalContent.classList.remove('animate__fadeIn', 'animate__zoomIn');
    modalContent.classList.add('animate__fadeOut', 'animate__zoomOut');

    setTimeout(() => {
        modal.style.display = 'none';
        currentDeleteFeedbackId = '';
    }, 300);
}

// Confirmer la suppression du feedback
function confirmDeleteFeedback() {
    if (!currentDeleteFeedbackId) {
        return;
    }

    // Désactiver le bouton pendant la suppression
    const confirmBtn = event.target;
    const originalHTML = confirmBtn.innerHTML;
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression...';

    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete_feedback&feedback_id=' + encodeURIComponent(currentDeleteFeedbackId)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Réinitialiser le bouton
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalHTML;

            // Fermer la modale
            closeDeleteFeedbackModal();

            // Afficher un message de succès
            showSuccessMessage(data.message || 'Feedback supprimé avec succès');

            // Recharger la liste des feedbacks après un court délai
            setTimeout(() => {
                loadFeedbacks();
            }, 500);
        } else {
            // Réinitialiser le bouton
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalHTML;

            // Afficher l'erreur
            closeDeleteFeedbackModal();
            showErrorMessage(data.error || 'Erreur lors de la suppression');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = originalHTML;
        closeDeleteFeedbackModal();
        showErrorMessage('Erreur de communication avec le serveur');
    });
}

// Fermer la modale de suppression en cliquant sur l'overlay
document.addEventListener('DOMContentLoaded', function() {
    const deleteFeedbackModal = document.getElementById('deleteFeedbackModal');

    if (deleteFeedbackModal) {
        deleteFeedbackModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteFeedbackModal();
            }
        });
    }
});

// === RÉORGANISATION DES FEEDBACKS (DRAG & DROP) ===

// Variable globale pour stocker l'instance Sortable
let feedbackSortableInstance = null;

// Initialiser SortableJS sur la liste des feedbacks
function initializeFeedbackSortable() {
    const feedbacksBody = document.getElementById('feedbacks-body-sortable');

    if (!feedbacksBody || !window.Sortable) {
        return;
    }

    // Détruire l'instance précédente si elle existe
    if (feedbackSortableInstance) {
        feedbackSortableInstance.destroy();
    }

    // Créer une nouvelle instance SortableJS
    feedbackSortableInstance = Sortable.create(feedbacksBody, {
        animation: 150,
        handle: '.feedback-drag-handle',
        ghostClass: 'feedback-ghost',
        chosenClass: 'feedback-chosen',
        dragClass: 'feedback-drag',
        onEnd: function(evt) {
            // Sauvegarder le nouvel ordre après le drop
            saveFeedbackOrder();
        }
    });
}

// Sauvegarder l'ordre des feedbacks après réorganisation
function saveFeedbackOrder() {
    const feedbackRows = document.querySelectorAll('.feedback-row');
    const orderedIds = [];

    feedbackRows.forEach(row => {
        const feedbackId = row.getAttribute('data-feedback-id');
        if (feedbackId) {
            orderedIds.push(feedbackId);
        }
    });

    if (orderedIds.length === 0) {
        return;
    }

    // Envoyer l'ordre au serveur
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=reorder_feedbacks&ordered_ids=' + encodeURIComponent(JSON.stringify(orderedIds))
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Erreur lors de la sauvegarde de l\'ordre:', data.error);
            // En cas d'erreur, on pourrait recharger la liste pour restaurer l'ordre original
            // mais on laisse l'utilisateur voir son changement même s'il n'est pas sauvegardé
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
    });
}

// === TOGGLE STATUT FEEDBACK ===

// Toggle le statut d'un feedback (traité/non traité)
function toggleFeedbackStatus(feedbackId) {
    if (!feedbackId) {
        return;
    }

    // Trouver la ligne du feedback
    const feedbackRow = document.querySelector(`.feedback-row[data-feedback-id="${feedbackId}"]`);
    if (!feedbackRow) {
        return;
    }

    // Trouver le bouton toggle
    const toggleBtn = feedbackRow.querySelector('.feedback-toggle-btn');
    const toggleIcon = toggleBtn ? toggleBtn.querySelector('i') : null;

    // Sauvegarder l'état actuel pour pouvoir revert en cas d'erreur
    const wasCompleted = feedbackRow.classList.contains('completed');

    // Mise à jour visuelle optimiste
    feedbackRow.classList.toggle('completed');
    const dateCol = feedbackRow.querySelector('.feedback-col-date');
    const messageCol = feedbackRow.querySelector('.feedback-col-message');

    if (dateCol) dateCol.classList.toggle('completed');
    if (messageCol) messageCol.classList.toggle('completed');

    if (toggleBtn) {
        toggleBtn.classList.toggle('completed');
        if (toggleIcon) {
            if (feedbackRow.classList.contains('completed')) {
                toggleIcon.classList.remove('fa-circle');
                toggleIcon.classList.add('fa-check-circle');
                toggleBtn.setAttribute('title', 'Marquer comme non traité');
            } else {
                toggleIcon.classList.remove('fa-check-circle');
                toggleIcon.classList.add('fa-circle');
                toggleBtn.setAttribute('title', 'Marquer comme traité');
            }
        }
    }

    // Envoyer la requête AJAX
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle_feedback_status&feedback_id=' + encodeURIComponent(feedbackId)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // En cas d'erreur, revert l'état visuel
            feedbackRow.classList.toggle('completed');
            if (dateCol) dateCol.classList.toggle('completed');
            if (messageCol) messageCol.classList.toggle('completed');

            if (toggleBtn) {
                toggleBtn.classList.toggle('completed');
                if (toggleIcon) {
                    if (wasCompleted) {
                        toggleIcon.classList.remove('fa-circle');
                        toggleIcon.classList.add('fa-check-circle');
                        toggleBtn.setAttribute('title', 'Marquer comme non traité');
                    } else {
                        toggleIcon.classList.remove('fa-check-circle');
                        toggleIcon.classList.add('fa-circle');
                        toggleBtn.setAttribute('title', 'Marquer comme traité');
                    }
                }
            }

            showErrorMessage(data.error || 'Erreur lors du changement de statut');
        }
    })
    .catch(error => {
        console.error('Erreur:', error);

        // Revert l'état visuel
        feedbackRow.classList.toggle('completed');
        if (dateCol) dateCol.classList.toggle('completed');
        if (messageCol) messageCol.classList.toggle('completed');

        if (toggleBtn) {
            toggleBtn.classList.toggle('completed');
            if (toggleIcon) {
                if (wasCompleted) {
                    toggleIcon.classList.remove('fa-circle');
                    toggleIcon.classList.add('fa-check-circle');
                    toggleBtn.setAttribute('title', 'Marquer comme non traité');
                } else {
                    toggleIcon.classList.remove('fa-check-circle');
                    toggleIcon.classList.add('fa-circle');
                    toggleBtn.setAttribute('title', 'Marquer comme traité');
                }
            }
        }

        showErrorMessage('Erreur de communication avec le serveur');
    });
}