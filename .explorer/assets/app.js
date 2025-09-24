// Explorateur de fichiers PHP - JavaScript principal

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
        const modal = document.getElementById('confirmModal');
        if (modal.style.display !== 'none') {
            closeModal();
        }
    }
});