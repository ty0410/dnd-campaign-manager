import { createRouter, createWebHistory } from 'vue-router';
import { getToken } from '../services/auth';
import Home from '../views/Home.vue';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import Campaigns from '../views/Campaigns.vue';
import CampaignDetail from '../views/CampaignDetail.vue';
const router = createRouter({
    history: createWebHistory(),

    routes: [
        {
            path: '/',
            name: 'home',
            component: Home,
        },
        {
            path: '/login',
            name: 'login',
            component: Login,
        },
        {
            path: '/register',
            name: 'register',
            component: Register,
        },
        {
            path: '/campaigns',
            name: 'campaigns',
            component: Campaigns,
            meta: { requiresAuth: true }, // Esta ruta requiere autenticación
        },
        {
            path: '/campaigns/:id',
            name: 'campaign-detail',
            component: CampaignDetail,
            meta: { requiresAuth: true }, // Esta ruta requiere autenticación
        }

    ],
});
router.beforeEach((to) => { // Guard para verificar la autenticación antes de acceder a rutas protegidas
    if (to.meta.requiresAuth && !getToken()) {
        return {
            name: 'login',
        };
    }
});
export default router;