// Script pour gérer l'upload et la prévisualisation des images lors de la publication d'une propriété
// Les commentaires expliquent chaque partie pour bien comprendre le rôle de chaque bloc

document.addEventListener('DOMContentLoaded', function() {
    // On récupère les éléments du DOM nécessaires
    const imageInput = document.getElementById('property_images');
    const imagePreview = document.getElementById('image_preview');
    const imageUploadContainer = document.querySelector('.image-upload-container');
    
    // --- Drag & Drop d'images ---
    // Permet de surligner la zone quand on glisse un fichier dessus
    function handleDragOver(e) {
        e.preventDefault();
        e.stopPropagation();
        imageUploadContainer.classList.add('drag-over');
    }
    
    // Retire le surlignage quand on quitte la zone
    function handleDragLeave(e) {
        e.preventDefault();
        e.stopPropagation();
        imageUploadContainer.classList.remove('drag-over');
    }
    
    // Gère le dépôt de fichiers par glisser-déposer
    function handleDrop(e) {
        e.preventDefault();
        e.stopPropagation();
        imageUploadContainer.classList.remove('drag-over');
        
        if (e.dataTransfer.files.length) {
            imageInput.files = e.dataTransfer.files;
            handleFileSelect();
        }
    }
    // --- Prévisualisation des images sélectionnées ---
    function handleFileSelect() {
        imagePreview.innerHTML = '';
        
        if (!imageInput.files || imageInput.files.length === 0) {
            return;
        }
        
        // Affiche un indicateur de chargement pendant la lecture des images
        const loadingElement = document.createElement('div');
        loadingElement.id = 'loading-indicator';
        loadingElement.className = 'text-center my-3';
        loadingElement.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Chargement...</span></div><p class="mt-2">Préparation des images...</p>';
        imagePreview.appendChild(loadingElement);
        
        // Pour chaque image sélectionnée, on crée un aperçu
        Array.from(imageInput.files).forEach((file, index) => {
            if (!file.type.match('image.*')) {
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const loadingIndicator = document.getElementById('loading-indicator');
                if (loadingIndicator && index === 0) {
                    loadingIndicator.remove();
                }
                
                const col = document.createElement('div');
                col.className = 'col-6 col-md-4 col-lg-3';
                
                const previewCard = document.createElement('div');
                previewCard.className = 'preview-card';
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.className = 'preview-image';
                img.alt = `Image preview ${index + 1}`;
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'btn btn-sm btn-danger remove-image';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.dataset.index = index;
                removeBtn.addEventListener('click', function() {
                    removeImage(this.dataset.index);
                });
                
                const setMainBtn = document.createElement('button');
                setMainBtn.type = 'button';
                setMainBtn.className = 'btn btn-sm btn-outline-primary set-main-image';
                setMainBtn.innerHTML = '<i class="fas fa-star"></i>';
                setMainBtn.title = 'Définir comme image principale';
                setMainBtn.dataset.index = index;
                setMainBtn.addEventListener('click', function() {
                    setMainImage(this.dataset.index);
                });
                
                if (index !== 0) {
                    previewCard.appendChild(setMainBtn);
                }
                
                if (index === 0) {
                    const mainBadge = document.createElement('span');
                    mainBadge.className = 'badge bg-primary main-badge';
                    mainBadge.textContent = 'Principale';
                    previewCard.appendChild(mainBadge);
                }
                
                previewCard.appendChild(img);
                previewCard.appendChild(removeBtn);
                col.appendChild(previewCard);
                imagePreview.appendChild(col);
                
                setTimeout(() => {
                    previewCard.classList.add('show');
                }, 50);
            };
            
            reader.readAsDataURL(file);
        });
    }
    // --- Suppression d'une image de la sélection ---
    function removeImage(index) {
        try {
            const dt = new DataTransfer();
            const files = Array.from(imageInput.files);
            
            files.forEach((file, i) => {
                if (i != index) {
                    dt.items.add(file);
                }
            });
            
            imageInput.files = dt.files;
            handleFileSelect();
        } catch (error) {
            const previewElement = document.querySelector(`[data-index="${index}"]`).closest('.col-6');
            if (previewElement) {
                previewElement.remove();
            }
            
            alert("L'image a été marquée pour suppression. Elle sera retirée lors de la soumission du formulaire.");
        }
    }
    // --- Définir une image comme principale ---
    function setMainImage(index) {
        try {
            const dt = new DataTransfer();
            const files = Array.from(imageInput.files);
            
            const selectedFile = files[index];
            const reorderedFiles = [selectedFile, ...files.filter((_, i) => i != index)];
            
            reorderedFiles.forEach(file => {
                dt.items.add(file);
            });
            
            imageInput.files = dt.files;
            handleFileSelect();
        } catch (error) {
            console.error("DataTransfer API not supported for reordering.");
            alert("La réorganisation des images n'est pas prise en charge par votre navigateur. La première image téléchargée sera utilisée comme principale.");
        }
    }
    // --- Ajout des écouteurs d'événements ---
    if (imageInput) {
        imageInput.addEventListener('change', handleFileSelect);
    }
    
    if (imageUploadContainer) {
        imageUploadContainer.addEventListener('dragover', handleDragOver);
        imageUploadContainer.addEventListener('dragleave', handleDragLeave);
        imageUploadContainer.addEventListener('drop', handleDrop);
        
        imageUploadContainer.addEventListener('click', function(e) {
            if (!e.target.closest('label')) {
                imageInput.click();
            }
        });
    }
});
