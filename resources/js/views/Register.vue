<template>
    <main class="auth-page">
        <h1>Crear cuenta</h1>

        <form @submit.prevent="register">
            <div>
                <label for="name">Nombre</label>

                <input
                    id="name"
                    v-model="name"
                    type="text"
                    placeholder="Tu nombre"
                    required
                >
            </div>

            <div>
                <label for="username">Nombre de usuario público</label>

                <input
                    id="username"
                    v-model="username"
                    type="text"
                    placeholder="aventurero_42"
                    minlength="3"
                    maxlength="30"
                    pattern="[A-Za-z0-9_]+"
                    autocomplete="username"
                    required
                >
                <small>Es lo que verán los demás (3 a 30: letras, números y guion bajo).</small>
            </div>

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

            <div>
                <label for="password_confirmation">
                    Repetir contraseña
                </label>

                <input
                    id="password_confirmation"
                    v-model="passwordConfirmation"
                    type="password"
                    placeholder="Repite la contraseña"
                    required
                >
            </div>

            <button type="submit">
                Registrarse
            </button>
        </form>

        <p v-if="message" :class="messageIsError ? 'error-message' : 'success-message'" role="alert">
            {{ message }}
        </p>

        <RouterLink to="/login">
            Ya tengo una cuenta
        </RouterLink>
    </main>
</template>

<script setup>
import { ref } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import api from '../services/api';
import { saveToken } from '../services/auth';

const router = useRouter();

const name = ref('');
const username = ref('');
const email = ref('');
const password = ref('');
const passwordConfirmation = ref('');
const message = ref('');
const messageIsError = ref(false);

const register = async () => {
    message.value = '';
    messageIsError.value = false;

    try {
        const response = await api.post('/register', {
            name: name.value,
            username: username.value,
            email: email.value,
            password: password.value,
            password_confirmation: passwordConfirmation.value,
        });

        message.value = response.data.message;

        saveToken(response.data.token);

        window.dispatchEvent(new Event('auth-changed'));

        router.push('/dashboard');
    } catch (error) {
        messageIsError.value = true;
        if (error.response) {
            if (error.response.data.errors) {
                const errors = error.response.data.errors;

                message.value = Object.values(errors)
                    .flat()
                    .join(' ');
            } else {
                message.value =
                    error.response.data.message ||
                    'No se pudo crear la cuenta.';
            }
        } else {
            message.value =
                'No se pudo conectar con el servidor.';
        }
    }
};
</script>