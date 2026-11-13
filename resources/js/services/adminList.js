import { onBeforeUnmount, ref, watch } from 'vue';
import api from './api';

// Listado paginado con filtros para las pestañas de administración.
// Los filtros de texto se aplican con una pequeña espera para no lanzar
// una petición por cada tecla.
export function useAdminList(endpoint, filters) {
    const rows = ref([]);
    const page = ref(1);
    const lastPage = ref(1);
    const total = ref(0);
    const loading = ref(false);
    const error = ref('');

    let timer = null;
    let request = 0;

    async function load() {
        const current = ++request;
        loading.value = true;
        error.value = '';

        const params = { page: page.value };
        for (const [key, value] of Object.entries(filters.value)) {
            if (value !== '' && value !== null && value !== undefined) params[key] = value;
        }

        try {
            const { data } = await api.get(endpoint, { params });
            if (current !== request) return;
            rows.value = data.data;
            lastPage.value = data.last_page;
            total.value = data.total;
            // Si se borró el último elemento de la última página, retrocede.
            if (page.value > data.last_page && data.last_page > 0) {
                page.value = data.last_page;
                await load();
            }
        } catch (err) {
            if (current === request) error.value = err.response?.data?.message || 'No se pudo cargar el listado.';
        } finally {
            if (current === request) loading.value = false;
        }
    }

    watch(filters, () => {
        clearTimeout(timer);
        timer = setTimeout(() => { page.value = 1; load(); }, 300);
    }, { deep: true });

    function goTo(next) {
        if (next < 1 || next > lastPage.value || next === page.value) return;
        page.value = next;
        load();
    }

    onBeforeUnmount(() => clearTimeout(timer));

    return { rows, page, lastPage, total, loading, error, load, goTo };
}
