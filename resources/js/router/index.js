import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth';

const routes = [
    {
        path: '/login',
        name: 'Login',
        component: () => import('../pages/Login.vue'),
        meta: { guest: true },
    },
    {
        path: '/register',
        name: 'Register',
        component: () => import('../pages/Register.vue'),
        meta: { guest: true },
    },
    {
        path: '/',
        component: () => import('../layouts/MainLayout.vue'),
        meta: { requiresAuth: true },
        children: [
            {
                path: '',
                name: 'Dashboard',
                component: () => import('../pages/Dashboard.vue'),
            },
            {
                path: 'prayer-records',
                name: 'PrayerRecords',
                component: () => import('../pages/PrayerRecords.vue'),
            },
            {
                path: 'users',
                name: 'Users',
                component: () => import('../pages/Users.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'users/import',
                name: 'UserImport',
                component: () => import('../pages/UserImport.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'rombongan-belajar',
                name: 'RombonganBelajar',
                component: () => import('../pages/RombonganBelajar.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'user-recap',
                name: 'UserRecap',
                component: () => import('../pages/UserRecap.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'excel-export',
                name: 'ExcelExport',
                component: () => import('../pages/ExcelExport.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'nfc-scanner',
                name: 'NfcScanner',
                component: () => import('../pages/NfcScanner.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'nfc-writer',
                name: 'NfcWriter',
                component: () => import('../pages/NfcWriter.vue'),
                meta: { requiresAdmin: true },
            },
            {
                path: 'profile',
                name: 'Profile',
                component: () => import('../pages/Profile.vue'),
                meta: { requiresAuth: true },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, from, next) => {
    const authStore = useAuthStore();
    
    if (to.meta.requiresAuth && !authStore.isAuthenticated) {
        next('/login');
    } else if (to.meta.guest && authStore.isAuthenticated) {
        next('/');
    } else if (to.meta.requiresAdmin && !authStore.isAdmin) {
        next('/');
    } else {
        next();
    }
});

export default router;