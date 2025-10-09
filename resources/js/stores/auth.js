import { defineStore } from 'pinia';
import api from '../api/axios';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user')) || null,
        token: localStorage.getItem('token') || null,
        isImpersonating: localStorage.getItem('is_impersonating') === 'true',
        originalAdminId: localStorage.getItem('original_admin_id') || null,
    }),

    getters: {
        isAuthenticated: (state) => !!state.token,
        isAdmin: (state) => state.user?.role === 'admin',
    },

    actions: {
        async login(credentials) {
            try {
                const response = await api.post('/login', credentials);
                this.token = response.data.token;
                this.user = response.data.user;
                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        async register(userData) {
            try {
                const response = await api.post('/register', userData);
                this.token = response.data.token;
                this.user = response.data.user;
                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        async logout() {
            try {
                await api.post('/logout');
            } catch (error) {
                console.error('Logout error:', error);
            } finally {
                this.token = null;
                this.user = null;
                this.isImpersonating = false;
                this.originalAdminId = null;
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                localStorage.removeItem('is_impersonating');
                localStorage.removeItem('original_admin_id');
            }
        },

        async stopImpersonating() {
            try {
                const response = await api.post('/stop-impersonating');
                this.token = response.data.token;
                this.user = response.data.user;
                this.isImpersonating = false;
                this.originalAdminId = null;
                
                localStorage.setItem('token', this.token);
                localStorage.setItem('user', JSON.stringify(this.user));
                localStorage.removeItem('is_impersonating');
                localStorage.removeItem('original_admin_id');
                
                return response.data;
            } catch (error) {
                throw error;
            }
        },

        async fetchUser() {
            try {
                const response = await api.get('/me');
                this.user = response.data.user;
                localStorage.setItem('user', JSON.stringify(this.user));
            } catch (error) {
                console.error('Fetch user error:', error);
            }
        },
    },
});