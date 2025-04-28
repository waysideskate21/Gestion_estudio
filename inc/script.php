<script>
    document.addEventListener('DOMContentLoaded', () => {

        // Get all "navbar-burger" elements
        const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);

        // Add a click event on each of them
        $navbarBurgers.forEach(el => {
            el.addEventListener('click', () => {

                const target = el.dataset.target;
                const $target = document.getElementById(target);

                el.classList.toggle('is-active');
                $target.classList.toggle('is-active');

            });
        });
        const $dropdownToggles = Array.prototype.slice.call(document.querySelectorAll('.navbar-link'), 0);
        $dropdownToggles.forEach(el => {
            el.addEventListener('click', (e) => {
                if (window.innerWidth <= 1023) { 
                    e.preventDefault(); 
                    const dropdown = el.closest('.navbar-item').querySelector('.navbar-dropdown');
                    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
                }
            });
        });

    });
</script>
<script src="./js/ajax.js"></script>