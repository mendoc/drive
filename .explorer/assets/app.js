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

        if (confirmModal && confirmModal.style.display !== 'none') {
            closeModal();
        } else if (createFolderModal && createFolderModal.style.display !== 'none') {
            closeCreateFolderModal();
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