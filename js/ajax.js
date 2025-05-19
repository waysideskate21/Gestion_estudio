// js/ajax.js (Alineado con tu CSS existente para dropdowns en móvil)

document.addEventListener('DOMContentLoaded', () => {

  // 1. Toggle del navbar-burger (estándar de Bulma)
  // Esta parte es la misma y debería funcionar bien.
  const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
  if ($navbarBurgers.length > 0) {
    $navbarBurgers.forEach( el => {
      el.addEventListener('click', () => {
        const target = el.dataset.target;
        const $target = document.getElementById(target);
        el.classList.toggle('is-active');
        $target.classList.toggle('is-active');
      });
    });
  }

  // 2. Toggle de dropdowns en móvil (Ajustado para usar .is-active en .navbar-dropdown)
  //    Este código permite que los dropdowns se abran al hacer clic en el navbar-link en móvil.
  const $dropdownToggles = Array.prototype.slice.call(document.querySelectorAll('.navbar-menu .navbar-item.has-dropdown > .navbar-link'), 0);
  $dropdownToggles.forEach(el => {
    el.addEventListener('click', (e) => {
      // Solo activar en pantallas móviles (cuando el navbar-burger es visible)
      // Bulma esconde el navbar-burger en 'desktop' (1024px por defecto)
      if (window.innerWidth < 1024) { 
        e.preventDefault(); // Prevenir la navegación si es un enlace '#' o real
        
        const parentItem = el.closest('.navbar-item.has-dropdown');
        if (parentItem) {
            const dropdown = parentItem.querySelector('.navbar-dropdown');
            if (dropdown) {
                // Cerrar otros dropdowns que puedan estar abiertos
                document.querySelectorAll('.navbar-menu .navbar-item.has-dropdown .navbar-dropdown.is-active').forEach(activeDropdown => {
                    if (activeDropdown !== dropdown) {
                        activeDropdown.classList.remove('is-active');
                    }
                });
                // Alternar el estado del dropdown actual
                dropdown.classList.toggle('is-active');
            }
        }
      }
    });
  });
  // Tu CSS actual ya maneja la visibilidad con .navbar-dropdown.is-active:
  // @media screen and (max-width: 1023px) {
  //   .navbar-dropdown { display: none; }
  //   .navbar-dropdown.is-active { display: block; }
  // }


  // 3. Cerrar el menú desplegable (navbar-menu) al hacer clic en un enlace del menú en móvil
  //    Esto es para el menú hamburguesa principal.
  const $navbarMenuItems = Array.prototype.slice.call(document.querySelectorAll('#navbarMenuPrincipal .navbar-item'), 0);
  if ($navbarMenuItems.length > 0) {
    $navbarMenuItems.forEach( el => {
      // Solo añadir listener a los items que son enlaces directos o items dentro de un dropdown
      if (el.href || el.closest('.navbar-dropdown')) {
          el.addEventListener('click', (event) => {
            // No cerrar si el clic fue en un navbar-link que abre un sub-dropdown en móvil
            // La lógica del punto 2 se encarga de abrir/cerrar el sub-dropdown.
            // Si el target del evento es un navbar-link DENTRO de un has-dropdown, y estamos en móvil,
            // no cerramos el menú principal porque el usuario está interactuando con el submenú.
            if (event.target.classList.contains('navbar-link') && event.target.closest('.has-dropdown')) {
                if (window.innerWidth < 1024) { 
                    return; // Dejar que la lógica del punto 2 maneje el sub-dropdown
                }
            }

            // Si es un enlace final (o un enlace de dropdown en escritorio), cerrar el menú principal si está abierto.
            const $navbar = el.closest('.navbar');
            if (!$navbar) return;

            const $navbarBurger = $navbar.querySelector('.navbar-burger');
            const $navbarMenu = $navbar.querySelector('.navbar-menu');
            
            if ($navbarBurger && $navbarMenu && $navbarBurger.classList.contains('is-active')) {
              $navbarBurger.classList.remove('is-active');
              $navbarMenu.classList.remove('is-active');
            }
          });
      }
    });
  }

});
