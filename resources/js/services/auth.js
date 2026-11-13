import api from './api';

const TOKEN_KEY = 'dnd_auth_token';

// Usuario en memoria para no repetir /me en cada navegación.
let cachedUser = null;

export function saveToken(token) {
    cachedUser = null;
    localStorage.setItem(TOKEN_KEY, token);
}

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function removeToken() {
    cachedUser = null;
    localStorage.removeItem(TOKEN_KEY);
}

export async function logout() {
    try {
        await api.post('/logout');
    } finally {
        removeToken();
    }
}

export async function getCurrentUser() {
    const response = await api.get('/me');

    cachedUser = response.data;

    return response.data;
}

// Devuelve el usuario ya cargado o lo pide una vez; null si no hay sesión válida.
export async function getCachedUser() {
    if (cachedUser) return cachedUser;
    if (!getToken()) return null;

    try {
        return await getCurrentUser();
    } catch {
        return null;
    }
}

export const isAdminUser = (user) => user?.role === 'admin';