import axios from 'axios';
import { getToken } from './auth'; // Importa la función getToken desde el servicio de autenticación

const api = axios.create({ // Crea una instancia de Axios con la configuración base
    baseURL: '/api',// Establece la URL base para las solicitudes a la API
    headers: { // Establece los encabezados predeterminados para las solicitudes
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
});

api.interceptors.request.use((config) => { // Interceptor de solicitud para agregar el token de autenticación a los encabezados
    const token = getToken(); // Obtiene el token de autenticación desde el almacenamiento local

    if (token) { // Si hay un token, agrega el encabezado de autorización con el token
        config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
});

export default api;