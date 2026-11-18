// Definición común de los elementos de una campaña (sesiones, lugares, PNJ y
// encuentros). La usan el panel de campaña, la hoja de detalle y el editor.

export const LONG_TEXT_MAX = 3000;

export const ENCOUNTER_STATUSES = {
    planned: 'Preparado',
    active: 'Activo',
    completed: 'Completado',
};

export const ITEM_TYPES = {
    session: {
        label: 'Sesión',
        plural: 'Sesiones',
        endpoint: 'sessions',
        nameKey: 'title',
        blank: () => ({ title: '', session_date: '', summary: '', notes: '' }),
        fields: [
            { key: 'title', label: 'Título de la sesión', required: true, max: 255 },
            { key: 'session_date', label: 'Fecha', type: 'date' },
            { key: 'summary', label: 'Resumen', type: 'textarea', max: LONG_TEXT_MAX },
            { key: 'notes', label: 'Notas del Dungeon Master', type: 'textarea', max: LONG_TEXT_MAX },
        ],
    },
    location: {
        label: 'Lugar',
        plural: 'Lugares',
        endpoint: 'locations',
        nameKey: 'name',
        preserve: ['parent_id'],
        blank: () => ({ name: '', type: '', description: '' }),
        fields: [
            { key: 'name', label: 'Nombre del lugar', required: true, max: 255 },
            { key: 'type', label: 'Tipo', max: 100, placeholder: 'Ciudad, mazmorra, región...' },
            { key: 'description', label: 'Descripción', type: 'textarea', max: LONG_TEXT_MAX },
        ],
    },
    npc: {
        label: 'PNJ',
        plural: 'Personajes no jugadores',
        endpoint: 'npcs',
        nameKey: 'name',
        blank: () => ({ name: '', race: '', role: '', location: '', description: '' }),
        fields: [
            { key: 'name', label: 'Nombre', required: true, max: 255 },
            { key: 'race', label: 'Raza', max: 100 },
            { key: 'role', label: 'Papel', max: 100, placeholder: 'Mercader, aliado, villano...' },
            { key: 'location', label: 'Lugar', max: 255 },
            { key: 'description', label: 'Descripción', type: 'textarea', max: LONG_TEXT_MAX },
        ],
    },
    encounter: {
        label: 'Encuentro de combate',
        plural: 'Encuentros de combate',
        endpoint: 'encounters',
        nameKey: 'name',
        preserve: ['game_session_id'],
        // Por ahora todos los encuentros son de combate.
        fixed: { type: 'combat' },
        blank: () => ({ name: '', description: '', status: 'planned' }),
        fields: [
            { key: 'name', label: 'Nombre del encuentro', required: true, max: 255, placeholder: 'Emboscada en el camino real' },
            { key: 'description', label: 'Notas del encuentro', type: 'textarea', max: LONG_TEXT_MAX },
        ],
    },
};

export function formatDate(date) {
    if (!date) return '';
    const match = /^(\d{4})-(\d{2})-(\d{2})/.exec(date);
    if (!match) return date;
    return new Intl.DateTimeFormat('es-ES', { day: 'numeric', month: 'long', year: 'numeric' })
        .format(new Date(+match[1], +match[2] - 1, +match[3]));
}

// Línea corta que acompaña al nombre en listados.
export function itemSubtitle(type, item) {
    switch (type) {
        case 'session': return formatDate(item.session_date);
        case 'location': return item.type || '';
        case 'npc': return [item.race, item.role].filter(Boolean).join(' · ');
        case 'encounter': return ENCOUNTER_STATUSES[item.status] || ENCOUNTER_STATUSES.planned;
        default: return '';
    }
}

// Valores iniciales del formulario a partir de un elemento existente.
export function formFromItem(type, item) {
    const config = ITEM_TYPES[type];
    const form = config.blank();
    for (const key of Object.keys(form)) {
        const value = item[key];
        form[key] = value == null ? form[key] : value;
    }
    if (type === 'session' && form.session_date) form.session_date = String(form.session_date).slice(0, 10);
    return form;
}
