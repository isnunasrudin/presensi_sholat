<template>
    <div class="auth-container">
        <Card class="auth-card">
            <template #header>
                <div class="card-header">
                    <i class="pi pi-user-plus" style="font-size: 3rem; color: #667eea;"></i>
                    <h2>Buat Akun</h2>
                    <p>Daftar untuk Aplikasi Kehadiran Sholat</p>
                </div>
            </template>
            <template #content>
                <form @submit.prevent="handleRegister" class="auth-form">
                    <div class="field">
                        <label for="name">Nama Lengkap</label>
                        <InputText
                            id="name"
                            v-model="formData.name"
                            placeholder="Masukkan nama lengkap Anda"
                            :class="{ 'p-invalid': errors.name }"
                        />
                        <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
                    </div>

                    <div class="field">
                        <label for="email">Email</label>
                        <InputText
                            id="email"
                            v-model="formData.email"
                            type="email"
                            placeholder="Masukkan email Anda"
                            :class="{ 'p-invalid': errors.email }"
                        />
                        <small v-if="errors.email" class="p-error">{{ errors.email }}</small>
                    </div>
                    
                    <div class="field">
                        <label for="password">Kata Sandi</label>
                        <Password
                            id="password"
                            v-model="formData.password"
                            placeholder="Masukkan kata sandi Anda"
                            toggleMask
                            :class="{ 'p-invalid': errors.password }"
                        />
                        <small v-if="errors.password" class="p-error">{{ errors.password }}</small>
                    </div>

                    <div class="field">
                        <label for="password_confirmation">Konfirmasi Kata Sandi</label>
                        <Password
                            id="password_confirmation"
                            v-model="formData.password_confirmation"
                            placeholder="Konfirmasi kata sandi Anda"
                            :feedback="false"
                            toggleMask
                            :class="{ 'p-invalid': errors.password_confirmation }"
                        />
                        <small v-if="errors.password_confirmation" class="p-error">{{ errors.password_confirmation }}</small>
                    </div>

                    <Button
                        type="submit"
                        label="Daftar"
                        :loading="loading"
                        class="w-full"
                    />
                </form>
            </template>
            <template #footer>
                <div class="card-footer">
                    <p>Sudah punya akun? <router-link to="/login">Masuk di sini</router-link></p>
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

const formData = ref({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const errors = ref({});
const loading = ref(false);

const handleRegister = async () => {
    errors.value = {};
    loading.value = true;

    try {
        await authStore.register(formData.value);
        toast.add({ severity: 'success', summary: 'Success', detail: 'Registration successful', life: 3000 });
        router.push('/');
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            toast.add({ 
                severity: 'error', 
                summary: 'Error', 
                detail: error.response?.data?.message || 'Registration failed', 
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
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
}

.card-header h2 {
    margin: 0.75rem 0 0.5rem;
    color: #333;
    font-size: 1.5rem;
}

.card-header p {
    color: #666;
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
    color: #333;
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

.card-footer a {
    color: #667eea;
    text-decoration: none;
    font-weight: 600;
}

.card-footer a:hover {
    text-decoration: underline;
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