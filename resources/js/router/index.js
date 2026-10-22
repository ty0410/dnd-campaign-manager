
import { createRouter, createWebHistory } from 'vue-router';
import { getToken } from '../services/auth';

import Home from '../views/Home.vue';
import Login from '../views/Login.vue';
import Register from '../views/Register.vue';
import Dashboard from '../views/Dashboard.vue';
import Campaigns from '../views/Campaigns.vue';
import CampaignDetail from '../views/CampaignDetail.vue';
import TestCharacterApi from '../views/TestCharacterApi.vue';
import Characters from '../views/Characters.vue';
import CharacterCreate from '../views/CharacterCreate.vue';
import OtherCampaigns from '../views/OtherCampaigns.vue';
import DndApi2014 from '../views/DndApi2014.vue';

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
            meta: { guestOnly: true },
        },
        {
            path: '/register',
            name: 'register',
            component: Register,
            meta: { guestOnly: true },
        },
        {
            path: '/dashboard',
            name: 'dashboard',
            component: Dashboard,
            meta: { requiresAuth: true },
        },
        {
            path: '/characters',
            name: 'characters',
            component: Characters,
            meta: { requiresAuth: true },
        },
        {
            path: '/characters/create',
            name: 'character-create',
            component: CharacterCreate,
            meta: { requiresAuth: true },
        },
        {
            path: '/campaigns',
            name: 'campaigns',
            component: Campaigns,
            meta: { requiresAuth: true },
        },
        {
            path: '/campaigns/:id',
            name: 'campaign-detail',
            component: CampaignDetail,
            meta: { requiresAuth: true },
        },
        {
            path: '/other-campaigns',
            name: 'other-campaigns',
            component: OtherCampaigns,
            meta: { requiresAuth: true },
        },
        {
            path: '/api-2014',
            name: 'api-2014',
            component: DndApi2014,
            meta: { requiresAuth: true },
        },
        {
            path: '/test-character-api',
            name: 'test-character-api',
            component: TestCharacterApi,
            meta: { requiresAuth: true },
        },
    ],
});

router.beforeEach((to) => {
    const authenticated = !!getToken();

    if (to.name === 'home' && authenticated) {
        return { name: 'dashboard' };
    }

    if (to.meta.requiresAuth && !authenticated) {
        return {
            name: 'login',
            query: { redirect: to.fullPath },
        };
    }

    if (to.meta.guestOnly && authenticated) {
        return { name: 'dashboard' };
    }
});

export default router;
