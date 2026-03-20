document.addEventListener('DOMContentLoaded', function() {
    const mainImage = document.getElementById('main-room-image');
    const thumbnails = document.querySelectorAll('.thumbnail');
    const prevButton = document.getElementById('prev-button');
    const nextButton = document.getElementById('next-button');

    if (!mainImage || thumbnails.length === 0) {
        console.error("Chyba: Prvky galerie (main-room-image nebo thumbnail) nebyly nalezeny.");
        return; 
    }

const imageSources = Array.from(thumbnails).map(thumb => thumb.getAttribute('data-full-image'));
    let currentIndex = 0;

    function updateGallery(index) {
        if (index < 0) {
            currentIndex = imageSources.length - 1; 
        } else if (index >= imageSources.length) {
            currentIndex = 0; 
        } else {
            currentIndex = index;
        }

        mainImage.src = imageSources[currentIndex];
        
        thumbnails.forEach(t => t.classList.remove('active'));
        thumbnails[currentIndex].classList.add('active');

        thumbnails[currentIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }

    thumbnails.forEach((thumbnail, index) => {
        thumbnail.addEventListener('click', function() {
            updateGallery(index);
        });
    });

    prevButton.addEventListener('click', function() {
        updateGallery(currentIndex - 1);
    });

    nextButton.addEventListener('click', function() {
        updateGallery(currentIndex + 1);
    });
    
    const initialActive = Array.from(thumbnails).findIndex(t => t.classList.contains('active'));
    if (initialActive !== -1) {
        currentIndex = initialActive;
    } else {
        updateGallery(0); 
    }


    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            const newImageSrc = this.getAttribute('data-full-image');
            
            if (newImageSrc) {
                mainImage.src = newImageSrc;
                thumbnails.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            } else {
                console.warn("Upozornění: Náhled nemá definovaný atribut data-full-image.");
            }
        });
    });


    const hamburger = document.getElementById('hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (hamburger && navMenu) {
        hamburger.addEventListener('click', function() {
            hamburger.classList.toggle('active');
            navMenu.classList.toggle('active');
            document.body.style.overflow = navMenu.classList.contains('active') ? 'hidden' : 'auto';
        });
    }
});
