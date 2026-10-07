<template>
    <main>
        <h1>Iniciar sesión</h1>

        <form @submit.prevent="login">
            <div>
                <label for="email">Correo electrónico</label>

                <input
                    id="email"
                    v-model="email"
                    type="email"
                    placeholder="correo@ejemplo.com"
                    required
                >
            </div>

            <div>
                <label for="password">Contraseña</label>

                <input
                    id="password"
                    v-model="password"
                    type="password"
                    placeholder="Contraseña"
                    required
                >
            </div>

            <button type="submit">
                Iniciar sesión
            </button>
        </form>

        <p v-if="message">
            {{ message }}
        </p>

        <RouterLink to="/register">
            Crear una cuenta
        </RouterLink>
    </main>
</template>

<script setup>
// Importamos las funciones necesarias de Vue y Vue Router para manejar el estado y la navegación
import { ref } from 'vue';
import { RouterLink } from 'vue-router';
import api from '../services/api';
import { saveToken } from '../services/auth';
// Definimos las variables reactivas para el correo electrónico, la contraseña y el mensaje de error
const email = ref('');
const password = ref('');
const message = ref('');

const login = async () => {
    message.value = '';

    try { // Hacemos una solicitud POST al endpoint de inicio de sesión con el correo electrónico y la contraseña
        const response = await api.post('/login', {
            email: email.value,
            password: password.value,
        });

        message.value = response.data.message;
// Guardamos el token de autenticación en el almacenamiento local del navegador
        saveToken(response.data.token);
        window.dispatchEvent(new Event('auth-changed')); // Disparamos un evento para notificar que el estado de autenticación ha cambiado
// Mostramos en la consola la información del usuario autenticado
        console.log('Usuario:', response.data.user);
    } catch (error) { // Si ocurre un error, mostramos el mensaje de error correspondiente
        if (error.response) {
            message.value = error.response.data.message;
        } else {
            message.value = 'No se pudo conectar con el servidor.';
        }
    }
};
</script>