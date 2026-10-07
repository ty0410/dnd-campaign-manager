<template>
    <div>
        <header>
            <h1>Gestor de Tasha para todo</h1>

            <nav>
                <RouterLink to="/"> Inicio </RouterLink>

                <template v-if="isAuthenticated">
                    <span v-if="user"> Hola, {{ user.name }} </span>

                    <RouterLink to="/campaigns"> Mis campañas </RouterLink>

                    <button @click="handleLogout">Cerrar sesión</button>
                </template>

                <template v-else>
                    <RouterLink to="/login"> Iniciar sesión </RouterLink>

                    <RouterLink to="/register"> Registrarse </RouterLink>
                </template>
            </nav>
        </header>

        <main>
            <RouterView />
        </main>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { RouterLink, RouterView, useRouter } from "vue-router";
import { getToken, logout, getCurrentUser } from "./services/auth";
const router = useRouter();

const isAuthenticated = ref(!!getToken());
const user = ref(null);

onMounted(async () => {
    if (isAuthenticated.value) {
        try {
            user.value = await getCurrentUser();

            console.log("Usuario autenticado:", user.value);
        } catch (error) {
            console.error("No se pudo obtener el usuario:", error);
        }
    }
});
const updateAuthentication = () => {
    isAuthenticated.value = !!getToken();
};

window.addEventListener("auth-changed", updateAuthentication);

const handleLogout = async () => {
    await logout();

    isAuthenticated.value = false;

    router.push("/login");
};
</script>
