document.addEventListener('DOMContentLoaded', () => {
    // Toggle del navbar-burger (estándar de Bulma)
    const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
    $navbarBurgers.forEach(el => {
        el.addEventListener('click', () => {
            const target = el.dataset.target;
            const $target = document.getElementById(target);
            el.classList.toggle('is-active');
            $target.classList.toggle('is-active');
        });
    });

    // Toggle suave de dropdowns en móvil
    const $dropdownToggles = Array.prototype.slice.call(document.querySelectorAll('.navbar-link'), 0);
    $dropdownToggles.forEach(el => {
        el.addEventListener('click', (e) => {
            if (window.innerWidth <= 1023) {
                e.preventDefault();
                const parentItem = el.closest('.navbar-item');
                const dropdown = parentItem.querySelector('.navbar-dropdown');
                
                document.querySelectorAll('.navbar-dropdown').forEach(d => {
                    if (d !== dropdown) {
                        d.classList.remove('is-active');
                    }
                });

                dropdown.classList.toggle('is-active');
            }
        });
    });
});