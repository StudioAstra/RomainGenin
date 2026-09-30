import './styles/app.css';

// Menu mobile : tiroir basé sur <dialog> (Échap, focus piégé et fond gérés par le navigateur)
const menu = document.getElementById('mobile-menu');

if (menu) {
    const openers = document.querySelectorAll('[data-menu-open]');

    openers.forEach((button) => button.addEventListener('click', () => {
        menu.showModal();
        button.setAttribute('aria-expanded', 'true');
    }));

    menu.addEventListener('close', () => openers.forEach((button) => button.setAttribute('aria-expanded', 'false')));

    menu.addEventListener('click', (event) => {
        // Clic sur le fond, sur « Fermer » ou sur une ancre de la page
        if (event.target === menu || event.target.closest('[data-menu-close], a[href^="#"]')) {
            menu.close();
        }
    });

    // Retour en affichage desktop : on referme le tiroir
    window.matchMedia('(min-width: 48rem)').addEventListener('change', (event) => event.matches && menu.close());
}
