// Explorateur de fichiers PHP - JavaScript principal

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

        if (confirmModal && confirmModal.style.display !== 'none') {
            closeModal();
        } else if (createFolderModal && createFolderModal.style.display !== 'none') {
            closeCreateFolderModal();
        } else if (uploadModal && uploadModal.style.display !== 'none') {
            closeUploadModal();
        } else if (trashModal && trashModal.style.display !== 'none') {
            closeTrashModal();
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
                location.reload();
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
            location.reload();
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
                    location.reload();
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