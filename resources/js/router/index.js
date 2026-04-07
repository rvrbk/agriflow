import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import DashboardView from '../views/DashboardView.vue';
import CorporationsView from '../views/CorporationsView.vue';
import ProductsView from '../views/ProductsView.vue';
import HarvestsView from '../views/HarvestsView.vue';
import LoginView from '../views/LoginView.vue';

const routes = [
    {
        path: '/',
        name: 'dashboard',
        component: DashboardView,
        meta: { requiresAuth: true },
    },
    {
        path: '/corporations',
        name: 'corporations',
        component: CorporationsView,
        meta: { requiresAuth: true },
    },
    {
        path: '/products',
        name: 'products',
        component: ProductsView,
        meta: { requiresAuth: true },
    },
    {
        path: '/harvests',
        name: 'harvests',
        component: HarvestsView,
        meta: { requiresAuth: true },
    },
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { guestOnly: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach(async (to) => {
    const auth = useAuthStore();

    await auth.ensureInitialized();

    const isAuthenticated = Boolean(auth.state.user);

    if (to.meta.requiresAuth && !isAuthenticated) {
        return {
            name: 'login',
            query: { redirect: to.fullPath },
        };
    }

    if (to.meta.guestOnly && isAuthenticated) {
        return { name: 'dashboard' };
    }

    return true;
});

export default router;
