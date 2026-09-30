/*
 * Liste des projets (admin) : glisser-déposer les lignes pour changer l'ordre d'affichage sur le site.
 * Désactivé quand la liste est triée sur une autre colonne.
 */
import Sortable from '../lib/sortable.esm.js';

const config = document.getElementById('project-reorder');
const tbody = document.querySelector('table.datagrid tbody');
const sortedByColumn = new URLSearchParams(window.location.search).toString().includes('sort');

if (config && tbody && !sortedByColumn && tbody.querySelectorAll('tr[data-id]').length > 1) {
    const style = document.createElement('style');
    style.textContent = `
        .reorder-handle { width: 1%; cursor: grab; color: var(--ea-text-muted, #6b7080); font-size: 18px; line-height: 1; user-select: none; touch-action: none; }
        .reorder-handle:active { cursor: grabbing; }
        tr.reorder-ghost { opacity: .4; }
        .reorder-status { position: fixed; right: 16px; bottom: 16px; z-index: 1000; padding: 8px 14px; border-radius: 6px; background: #1f7a4a; color: #fff; font-size: 14px; }
        .reorder-status.error { background: #b42318; }
    `;
    document.head.append(style);

    document.querySelector('table.datagrid thead tr')?.prepend(document.createElement('th'));
    tbody.querySelectorAll('tr').forEach((row) => {
        const cell = document.createElement('td');
        if (row.dataset.id) {
            cell.className = 'reorder-handle';
            cell.title = 'Glisser pour réordonner';
            cell.textContent = '⠿';
            // Ne pas déclencher l'ouverture de la ligne au clic sur la poignée
            cell.addEventListener('click', (event) => event.stopPropagation());
        }
        row.prepend(cell);
    });

    let statusTimer;
    const showStatus = (message, isError = false) => {
        let status = document.querySelector('.reorder-status');
        if (!status) {
            status = document.createElement('div');
            status.className = 'reorder-status';
            status.setAttribute('role', 'status');
            document.body.append(status);
        }
        status.textContent = message;
        status.classList.toggle('error', isError);
        clearTimeout(statusTimer);
        statusTimer = setTimeout(() => status.remove(), 2500);
    };

    Sortable.create(tbody, {
        handle: '.reorder-handle',
        draggable: 'tr[data-id]',
        ghostClass: 'reorder-ghost',
        animation: 150,
        onEnd: async (event) => {
            if (event.oldIndex === event.newIndex) {
                return;
            }

            const ids = [...tbody.querySelectorAll('tr[data-id]')].map((row) => row.dataset.id);
            try {
                const response = await fetch(config.dataset.url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
                    body: JSON.stringify({ ids, _token: config.dataset.token }),
                });
                if (!response.ok) {
                    throw new Error(response.statusText);
                }
                const { positions } = await response.json();
                for (const [id, position] of Object.entries(positions)) {
                    const cell = tbody.querySelector(`tr[data-id="${id}"] td[data-column="position"]`);
                    if (cell) {
                        cell.textContent = position;
                    }
                }
                showStatus('Ordre enregistré');
            } catch (error) {
                showStatus('Échec de l\'enregistrement, rechargez la page.', true);
            }
        },
    });
}
