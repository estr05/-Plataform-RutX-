import './feedback';
import './modules/chart';
import './modules/map';

// Lógica de toggle interactivo para la barra lateral (sidebar)
document.addEventListener('DOMContentLoaded', () => {
    const sidebar = document.querySelector('[data-sidebar]');
    const toggle = document.querySelector('[data-sidebar-toggle]');

    if (sidebar && toggle) {
        toggle.addEventListener('click', () => {
            const nextCollapsed = sidebar.dataset.collapsed !== 'true';

            sidebar.dataset.collapsed = String(nextCollapsed);
            toggle.setAttribute('aria-expanded', String(!nextCollapsed));
            toggle.setAttribute(
                'aria-label',
                nextCollapsed ? 'Expandir barra lateral' : 'Colapsar barra lateral',
            );
        });
    }
});
