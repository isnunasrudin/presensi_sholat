<template>
    <div class="auth-container">
        <Card class="auth-card">
            <template #header>
                <div class="card-header">
                    <i class="pi pi-check-circle" style="font-size: 3rem; color: #fff;"></i>
                    <h2>SNESA Apps</h2>
                    <p>Aplikasi Management Presensi Sholat</p>
                </div>
            </template>
            <template #content>
                <form @submit.prevent="handleLogin" class="auth-form">
                    <div class="field">
                        <label for="credential">Email atau Nomor HP</label>
                        <InputText
                            id="credential"
                            v-model="credentials.credential"
                            placeholder="Masukkan email atau nomor HP"
                            :class="{ 'p-invalid': errors.credential }"
                        />
                        <small v-if="errors.credential" class="p-error">{{ errors.credential }}</small>
                    </div>
                    
                    <div class="field">
                        <label for="password">Kata Sandi</label>
                        <Password
                            id="password"
                            v-model="credentials.password"
                            placeholder="Masukkan kata sandi Anda"
                            :feedback="false"
                            toggleMask
                            :class="{ 'p-invalid': errors.password }"
                        />
                        <small v-if="errors.password" class="p-error">{{ errors.password }}</small>
                    </div>

                    <Button
                        type="submit"
                        label="Masuk"
                        :loading="loading"
                        class="w-full"
                    />
                </form>
            </template>
            <template #footer>
                <div class="card-footer">
                    <p class="flex gap-2 justify-center cursor-pointer" onclick="location.href='https:\/\/pelajartrenggalek.or.id'">
                        <span>Powered by </span>
                        <img src="../../image/edo.png" alt="" class=" h-3 w-3 object-contain">
                    </p>
                    <!-- <p>Belum punya akun? <router-link to="/register">Daftar di sini</router-link></p> -->
                </div>
            </template>
        </Card>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useToast } from 'primevue/usetoast';

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const credentials = ref({
    credential: '',
    password: '',
});

const errors = ref({});
const loading = ref(false);

const handleLogin = async () => {
    errors.value = {};
    loading.value = true;

    try {
        await authStore.login(credentials.value);
        toast.add({ severity: 'success', summary: 'Success', detail: 'Login successful', life: 3000 });
        router.push('/');
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            toast.add({ 
                severity: 'error', 
                summary: 'Error', 
                detail: error.response?.data?.message || 'Login failed', 
                life: 3000 
            });
        }
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
/* Mobile-first styles */
.auth-container {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.auth-card {
    width: 100%;
    max-width: 450px;
}

.card-header {
    text-align: center;
    padding: 1.5rem 1rem;
}

.card-header i {
    font-size: 2.5rem;
    color: rgba(16, 185, 129, 0.9);
    filter: drop-shadow(0 2px 4px rgba(16, 185, 129, 0.3));
}

.card-header h2 {
    margin: 0.75rem 0 0.5rem;
    color: white;
    font-size: 1.5rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.card-header p {
    color: rgba(255, 255, 255, 0.85);
    margin: 0;
    font-size: 0.875rem;
}

.auth-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.field label {
    font-weight: 600;
    color: white;
    font-size: 0.875rem;
}

.card-footer {
    text-align: center;
    padding-top: 1rem;
}

.card-footer p {
    font-size: 0.875rem;
    margin: 0;
}

.card-footer {
    color: rgba(255, 255, 255, 0.9);
}

.card-footer a {
    color: rgba(16, 185, 129, 0.9);
    text-decoration: none;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.card-footer a:hover {
    text-decoration: underline;
    color: #10b981;
}

.w-full {
    width: 100%;
}

/* Tablet and up (768px) */
@media (min-width: 768px) {
    .auth-container {
        padding: 2rem;
    }

    .card-header {
        padding: 2rem;
    }

    .card-header i {
        font-size: 3rem;
    }

    .card-header h2 {
        font-size: 1.75rem;
        margin: 1rem 0 0.5rem;
    }

    .card-header p {
        font-size: 1rem;
    }

    .auth-form {
        gap: 1.5rem;
    }

    .field label {
        font-size: 1rem;
    }

    .card-footer p {
        font-size: 1rem;
    }
}
</style>