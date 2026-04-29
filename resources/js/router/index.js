import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import DashboardView from '../views/DashboardView.vue';
import CorporationsView from '../views/CorporationsView.vue';
import ProductsView from '../views/ProductsView.vue';
import HarvestsView from '../views/HarvestsView.vue';
import InventoryView from '../views/InventoryView.vue';
import WarehousesView from '../views/WarehousesView.vue';
import HarvestPublicView from '../views/HarvestPublicView.vue';
import LoginView from '../views/LoginView.vue';
import UsersView from '../views/UsersView.vue';
import SetPasswordView from '../views/SetPasswordView.vue';
import ForgotPasswordView from '../views/ForgotPasswordView.vue';
import SellInventoryView from '../views/SellInventoryView.vue';
import SalesHistoryView from '../views/SalesHistoryView.vue';
import ReceiptView from '../views/ReceiptView.vue';

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
        path: '/inventory',
        name: 'inventory',
        component: InventoryView,
        meta: { requiresAuth: true },
    },
    {
        path: '/warehouses',
        name: 'warehouses',
        component: WarehousesView,
        meta: { requiresAuth: true },
    },
    {
        path: '/users',
        name: 'users',
        component: UsersView,
        meta: { requiresAuth: true },
    },
    {
        path: '/sales',
        name: 'sales',
        component: SellInventoryView,
        meta: { requiresAuth: true },
    },
    {
        path: '/sales-history',
        name: 'sales-history',
        component: SalesHistoryView,
        meta: { requiresAuth: true },
    },
    {
        path: '/receipt/:uuid',
        name: 'receipt',
        component: ReceiptView,
        meta: { requiresAuth: true },
    },
    {
        path: '/harvest/public/:batchUuid',
        name: 'harvest-public',
        component: HarvestPublicView,
    },
    {
        path: '/harvest/:batchUuid',
        redirect: (to) => ({ name: 'harvest-public', params: { batchUuid: to.params.batchUuid } }),
    },
    {
        path: '/login',
        name: 'login',
        component: LoginView,
        meta: { guestOnly: true },
    },
    {
        path: '/set-password',
        name: 'set-password',
        component: SetPasswordView,
        meta: { guestOnly: true },
    },
    {
        path: '/forgot-password',
        name: 'forgot-password',
        component: ForgotPasswordView,
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

    const isSetPasswordRoute = to.name === 'set-password';
    const hasResetToken = typeof to.query.token === 'string' && to.query.token.length > 0;
    const hasResetEmail = typeof to.query.email === 'string' && to.query.email.length > 0;

    if (isSetPasswordRoute && hasResetToken && hasResetEmail) {
        return true;
    }

    if (to.meta.guestOnly && isAuthenticated) {
        if (!auth.state.user?.corporation_id) {
            return { name: 'corporations' };
        }

        return { name: 'dashboard' };
    }

    if (to.meta.requiresAuth && isAuthenticated && !auth.state.user?.corporation_id && to.name !== 'corporations') {
        return {
            name: 'corporations',
            query: { redirect: to.fullPath },
        };
    }

    return true;
});

export default router;
