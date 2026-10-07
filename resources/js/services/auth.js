import api from './api';

const TOKEN_KEY = 'dnd_auth_token';

export function saveToken(token) {
    localStorage.setItem(TOKEN_KEY, token);
}

export function getToken() {
    return localStorage.getItem(TOKEN_KEY);
}

export function removeToken() {
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

    return response.data;
}