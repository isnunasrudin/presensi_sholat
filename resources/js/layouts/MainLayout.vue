<template>
    <div class="layout">
        <header class="header">
            <div class="header-content">
                <h1 class="logo">
                    <i class="pi pi-check-circle"></i>
                    <span class="logo-text">SNESA</span>
                </h1>
                
                <!-- Mobile Menu Toggle -->
                <Button
                    icon="pi pi-bars"
                    class="mobile-menu-toggle"
                    @click="mobileMenuOpen = !mobileMenuOpen"
                    text
                    rounded
                />

                <!-- Desktop Navigation -->
                <nav class="nav desktop-nav">
                    <router-link to="/" class="nav-link">
                        <i class="pi pi-home"></i>
                        <span>Dasbor</span>
                    </router-link>
                    <router-link to="/prayer-records" class="nav-link">
                        <i class="pi pi-calendar"></i>
                        <span>Catatan Sholat</span>
                    </router-link>
                    
                    <!-- Admin Menu Dropdown -->
                    <div v-if="authStore.isAdmin" class="nav-dropdown">
                        <div class="nav-link dropdown-toggle" @click="toggleAdminMenu">
                            <i class="pi pi-cog"></i>
                            <span>Admin</span>
                            <i class="pi pi-chevron-down"></i>
                        </div>
                        <div v-show="adminMenuOpen" class="dropdown-menu">
                            <router-link to="/users" class="dropdown-item" @click="adminMenuOpen = false">
                                <i class="pi pi-users"></i>
                                <span>Pengguna</span>
                            </router-link>
                            <router-link to="/rombongan-belajar" class="dropdown-item" @click="adminMenuOpen = false">
                                <i class="pi pi-graduation-cap"></i>
                                <span>Rombongan Belajar</span>
                            </router-link>
                            <router-link to="/user-recap" class="dropdown-item" @click="adminMenuOpen = false">
                                <i class="pi pi-chart-bar"></i>
                                <span>Rekap Pengguna</span>
                            </router-link>
                            <div class="dropdown-divider"></div>
                            <router-link to="/nfc-scanner" class="dropdown-item" @click="adminMenuOpen = false">
                                <i class="pi pi-search"></i>
                                <span>Scan NFC</span>
                            </router-link>
                            <router-link to="/nfc-writer" class="dropdown-item" @click="adminMenuOpen = false">
                                <i class="pi pi-pencil"></i>
                                <span>Write NFC</span>
                            </router-link>
                        </div>
                    </div>
                </nav>

                <!-- Desktop User Menu -->
                <div class="user-menu desktop-user-menu">
                    <!-- Profile link for all users -->
                    <router-link to="/profile" class="nav-link">
                        <i class="pi pi-user"></i>
                        <span>{{ authStore.user.name }}</span>
                    </router-link>
                    <Button
                        icon="pi pi-sign-out"
                        label="Keluar"
                        @click="handleLogout"
                        severity="danger"
                        text
                    />
                </div>
            </div>

            <!-- Impersonation Banner -->
            <div v-if="authStore.isImpersonating" class="impersonation-banner">
                <i class="pi pi-exclamation-triangle"></i>
                <span>Melihat sebagai {{ authStore.user?.name }}</span>
                <Button
                    icon="pi pi-times"
                    label="Berhenti"
                    @click="handleStopImpersonating"
                    severity="warning"
                    size="small"
                />
            </div>

            <!-- Mobile Navigation Menu -->
            <Transition name="slide">
                <div v-if="mobileMenuOpen" class="mobile-menu">
                    <nav class="mobile-nav">
                        <router-link to="/" class="mobile-nav-link" @click="mobileMenuOpen = false">
                            <i class="pi pi-home"></i>
                            <span>Dasbor</span>
                        </router-link>
                        <router-link to="/prayer-records" class="mobile-nav-link" @click="mobileMenuOpen = false">
                            <i class="pi pi-calendar"></i>
                            <span>Catatan Sholat</span>
                        </router-link>
                        <router-link v-if="authStore.isAdmin" to="/users" class="mobile-nav-link" @click="mobileMenuOpen = false">
                            <i class="pi pi-users"></i>
                            <span>Pengguna</span>
                        </router-link>
                        <router-link v-if="authStore.isAdmin" to="/rombongan-belajar" class="mobile-nav-link" @click="mobileMenuOpen = false">
                            <i class="pi pi-graduation-cap"></i>
                            <span>Rombongan Belajar</span>
                        </router-link>
                        <router-link v-if="authStore.isAdmin" to="/user-recap" class="mobile-nav-link" @click="mobileMenuOpen = false">
                            <i class="pi pi-chart-bar"></i>
                            <span>Rekap Pengguna</span>
                        </router-link>
                        <!-- Profile link for all users -->
                        <router-link to="/profile" class="mobile-nav-link" @click="mobileMenuOpen = false">
                            <i class="pi pi-user"></i>
                            <span>Profil</span>
                        </router-link>
                        
                        <!-- NFC Menu for Mobile -->
                        <div v-if="authStore.isAdmin" class="mobile-nav-link mobile-dropdown-toggle" @click="toggleMobileNfcMenu">
                            <i class="pi pi-qrcode"></i>
                            <span>NFC</span>
                            <i :class="mobileNfcMenuOpen ? 'pi pi-chevron-up' : 'pi pi-chevron-down'"></i>
                        </div>
                        <router-link v-if="authStore.isAdmin && mobileNfcMenuOpen" to="/nfc-scanner" class="mobile-nav-link mobile-dropdown-item" @click="mobileMenuOpen = false">
                            <i class="pi pi-search"></i>
                            <span>Scan NFC</span>
                        </router-link>
                        <router-link v-if="authStore.isAdmin && mobileNfcMenuOpen" to="/nfc-writer" class="mobile-nav-link mobile-dropdown-item" @click="mobileMenuOpen = false">
                            <i class="pi pi-pencil"></i>
                            <span>Write NFC</span>
                        </router-link>
                    </nav>
                    <div class="mobile-user-section">
                        <div class="mobile-user-info">
                            <i class="pi pi-user"></i>
                            <span>{{ authStore.user?.name }}</span>
                        </div>
                        <Button
                            icon="pi pi-sign-out"
                            label="Keluar"
                            @click="handleLogout"
                            severity="danger"
                            class="mobile-logout-btn"
                        />
                    </div>
                </div>
            </Transition>

            <!-- Mobile Menu Overlay -->
            <div
                v-if="mobileMenuOpen"
                class="mobile-menu-overlay"
                @click="mobileMenuOpen = false"
            ></div>
        </header>
        <main class="main-content">
            <router-view />
        </main>

        <div class="flex gap-2 justify-center cursor-pointer !mb-5 !text-white" onclick="location.href='https:\/\/pelajartrenggalek.or.id'">
            <span>Powered by </span>
            <img src="../../image/edo.png" alt="" class=" h-3 w-3 object-contain">
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useRouter } from 'vue-router';
import { useToast } from 'primevue/usetoast';

const authStore = useAuthStore();
const router = useRouter();
const toast = useToast();
const mobileMenuOpen = ref(false);
const adminMenuOpen = ref(false);
const nfcMenuOpen = ref(false);
const mobileNfcMenuOpen = ref(false);

const handleLogout = async () => {
    mobileMenuOpen.value = false;
    try {
        await authStore.logout();
        toast.add({ severity: 'success', summary: 'Success', detail: 'Logged out successfully', life: 3000 });
        router.push('/login');
    } catch (error) {
        console.error('Logout error:', error);
    }
};

const handleStopImpersonating = async () => {
    try {
        await authStore.stopImpersonating();
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Returned to admin account',
            life: 3000
        });
        window.location.href = '/';
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to stop impersonating',
            life: 3000
        });
    }
};

const toggleAdminMenu = () => {
    adminMenuOpen.value = !adminMenuOpen.value;
};

const toggleNfcMenu = () => {
    nfcMenuOpen.value = !nfcMenuOpen.value;
};

const toggleMobileNfcMenu = () => {
    mobileNfcMenuOpen.value = !mobileNfcMenuOpen.value;
};

// Close admin menu when clicking outside
document.addEventListener('click', (event) => {
    const dropdown = document.querySelector('.nav-dropdown');
    if (dropdown && !dropdown.contains(event.target)) {
        adminMenuOpen.value = false;
        nfcMenuOpen.value = false;
    }
});
</script>

<style scoped>
/* Mobile-first base styles */
.layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

.header {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(20px) saturate(180%);
    -webkit-backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid rgba(255, 255, 255, 0.25);
    color: white;
    box-shadow:
        0 4px 16px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    position: sticky;
    top: 0;
    z-index: 1000;
}

.header-content {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.logo {
    font-size: 1.25rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
    white-space: nowrap;
}

.logo-text {
    display: none;
}

.logo i {
    font-size: 1.5rem;
}

/* Mobile Menu Toggle */
.mobile-menu-toggle {
    color: white !important;
    font-size: 1.5rem;
}

/* Hide desktop navigation on mobile */
.desktop-nav,
.desktop-user-menu {
    display: none;
}

/* Mobile Menu */
.mobile-menu {
    position: fixed;
    top: 0;
    right: 0;
    width: 80%;
    max-width: 320px;
    height: 100vh;
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(30px) saturate(180%);
    -webkit-backdrop-filter: blur(30px) saturate(180%);
    border-left: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow:
        -4px 0 32px rgba(0, 0, 0, 0.2),
        inset 1px 0 0 rgba(255, 255, 255, 0.3);
    z-index: 1001;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
}

.mobile-menu-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
}

.mobile-nav {
    flex: 1;
    padding: 1.5rem 0;
    display: flex;
    flex-direction: column;
}

.mobile-nav-link {
    color: white;
    text-decoration: none;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 1rem;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.mobile-nav-link:hover {
    background: rgba(255, 255, 255, 0.15);
}

.mobile-nav-link.router-link-active {
    background: rgba(16, 185, 129, 0.25);
    border-left-color: rgba(16, 185, 129, 0.8);
    color: white;
    font-weight: 600;
}

.mobile-nav-link i {
    font-size: 1.25rem;
}

.mobile-user-section {
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background: rgba(255, 255, 255, 0.05);
}

.mobile-user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: white;
    font-weight: 500;
    padding: 0.5rem;
}

.mobile-user-info i {
    font-size: 1.25rem;
    color: rgba(16, 185, 129, 0.9);
}

.mobile-logout-btn {
    width: 100%;
}

/* Impersonation Banner */
.impersonation-banner {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: rgba(245, 158, 11, 0.25);
    backdrop-filter: blur(10px);
    padding: 0.75rem 1rem;
    border-bottom: 1px solid rgba(245, 158, 11, 0.4);
    font-size: 0.875rem;
    flex-wrap: wrap;
}

.impersonation-banner i {
    color: #fbbf24;
}

.impersonation-banner span {
    font-weight: 500;
    color: white;
}

/* Slide Transition */
.slide-enter-active,
.slide-leave-active {
    transition: transform 0.3s ease;
}

.slide-enter-from {
    transform: translateX(100%);
}

.slide-leave-to {
    transform: translateX(100%);
}

/* Main Content */
.main-content {
    flex: 1;
    max-width: 1400px;
    width: 100%;
    margin: 0 auto;
    padding: 1rem;
}

/* Tablet styles (768px and up) */
@media (min-width: 768px) {
    .header-content {
        padding: 1rem 1.5rem;
    }

    .logo {
        font-size: 1.5rem;
    }

    .logo-text {
        display: inline;
    }

    .main-content {
        padding: 1.5rem;
    }

    .impersonation-banner {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
    }
}

/* Desktop styles (1024px and up) */
@media (min-width: 1024px) {
    .header-content {
        padding: 1rem 2rem;
        gap: 2rem;
    }

    /* Hide mobile menu toggle on desktop */
    .mobile-menu-toggle {
        display: none;
    }

    /* Show desktop navigation */
    .desktop-nav {
        display: flex;
        gap: 0.25rem;
        flex: 1;
    }

    .nav-link {
        color: white;
        text-decoration: none;
        padding: 0.5rem 0.75rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        transition: all 0.3s;
        white-space: nowrap;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        font-size: 0.875rem;
    }
    
    .nav-link i {
        font-size: 1rem;
    }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .nav-link.router-link-active {
        background: rgba(16, 185, 129, 0.3);
        border-color: rgba(16, 185, 129, 0.5);
        font-weight: 600;
    }

    /* Show desktop user menu */
    .desktop-user-menu {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-name {
        font-weight: 500;
        display: block;
    }

    .main-content {
        padding: 2rem;
    }

    .impersonation-banner {
        gap: 0.75rem;
        padding: 0.5rem 1.5rem;
    }
}

/* Large desktop styles (1280px and up) */
@media (min-width: 1280px) {
    .desktop-nav {
        gap: 1rem;
    }

    .nav-link {
        padding: 0.5rem 1.25rem;
    }
}
/* NFC Dropdown Styles */
.nav-dropdown {
    position: relative;
}

.dropdown-toggle {
    cursor: pointer;
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    background: rgba(30, 30, 40, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
    padding: 0.5rem 0;
    min-width: 200px;
    z-index: 1000;
    margin-top: 0.5rem;
}

.dropdown-item {
    color: white;
    text-decoration: none;
    padding: 0.75rem 1rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    transition: all 0.3s;
}

.dropdown-item:hover {
    background: rgba(255, 255, 255, 0.25);
}

.dropdown-divider {
    height: 1px;
    background: rgba(255, 255, 255, 0.2);
    margin: 0.5rem 0;
}

/* Mobile NFC Dropdown Styles */
.mobile-dropdown-toggle {
    cursor: pointer;
    justify-content: space-between;
}

.mobile-dropdown-item {
    padding-left: 2.5rem;
    background: rgba(255, 255, 255, 0.1);
}

/* Tablet styles (768px and up) */
@media (min-width: 768px) {
    .header-content {
        padding: 1rem 1.5rem;
    }

    .logo {
        font-size: 1.5rem;
    }

    .logo-text {
        display: inline;
    }

    .main-content {
        padding: 1.5rem;
    }

    .impersonation-banner {
        padding: 0.75rem 1.5rem;
        font-size: 0.9rem;
    }
    
    .dropdown-menu {
        min-width: 200px;
    }
}

/* Desktop styles (1024px and up) */
@media (min-width: 1024px) {
    .header-content {
        padding: 1rem 2rem;
        gap: 2rem;
    }

    /* Hide mobile menu toggle on desktop */
    .mobile-menu-toggle {
        display: none;
    }

    /* Show desktop navigation */
    .desktop-nav {
        display: flex;
        gap: 0.5rem;
        flex: 1;
    }

    .nav-link {
        color: white;
        text-decoration: none;
        padding: 0.5rem 1rem;
        border-radius: 12px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s;
        white-space: nowrap;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .nav-link:hover {
        background: rgba(255, 255, 255, 0.2);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .nav-link.router-link-active {
        background: rgba(16, 185, 129, 0.3);
        border-color: rgba(16, 185, 129, 0.5);
        font-weight: 600;
    }

    /* Show desktop user menu */
    .desktop-user-menu {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .user-name {
        font-weight: 500;
        display: block;
    }

    .main-content {
        padding: 2rem;
    }

    .impersonation-banner {
        gap: 0.75rem;
        padding: 0.5rem 1.5rem;
    }
}

/* Large desktop styles (1280px and up) */
@media (min-width: 1280px) {
    .desktop-nav {
        gap: 1rem;
    }

    .nav-link {
        padding: 0.5rem 1.25rem;
    }
}
</style>
