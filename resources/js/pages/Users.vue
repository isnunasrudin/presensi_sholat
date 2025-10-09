<template>
    <div class="users">
        <div class="page-header">
            <h1 class="page-title">Manajemen Pengguna</h1>
            <div class="header-actions">
                <Button
                    label="Import CSV"
                    icon="pi pi-upload"
                    @click="openImportDialog()"
                    severity="info"
                />
                <Button
                    label="Tambah Pengguna"
                    icon="pi pi-plus"
                    @click="openDialog()"
                />
            </div>
        </div>

        <Card>
            <template #content>
                <div class="filters">
                    <div class="filter-group">
                        <label>Peran</label>
                        <Dropdown
                            v-model="filters.role"
                            :options="roleOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Semua Peran"
                            @change="fetchUsers"
                        />
                    </div>
                    <div class="filter-group">
                        <label>Cari</label>
                        <InputText
                            v-model="filters.search"
                            placeholder="Cari berdasarkan nama atau email"
                            @input="debouncedSearch"
                        />
                    </div>
                    <Button
                        label="Hapus Filter"
                        icon="pi pi-filter-slash"
                        @click="clearFilters"
                        severity="secondary"
                    />
                </div>

                <!-- Mobile DataView -->
                <DataView :value="users" :loading="loading" class="user-dataview">
                    <template #list="slotProps">
                        <div class="dataview-item" v-for="isian in slotProps.items" :key="isian.id">
                            <div class="user-avatar-section">
                                <div class="user-avatar">
                                    <img
                                        v-if="isian.photo"
                                        :src="`${isian.photo}`"
                                        :alt="isian.name"
                                    />
                                    <i v-else class="pi pi-user"></i>
                                </div>
                                <div class="user-main-info">
                                    <div class="user-name-wrapper">
                                        <h3 class="user-name">{{ isian.name }}</h3>
                                        <span :class="getRoleClass(isian.role)">
                                            {{ getRoleLabel(isian.role) }}
                                        </span>
                                    </div>
                                    <p class="user-email">
                                        <i class="pi pi-envelope"></i>
                                        {{ isian.email }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="user-details">
                                <div class="detail-item">
                                    <i class="pi pi-graduation-cap detail-icon"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Rombongan Belajar</span>
                                        <span class="detail-value">{{ isian.rombongan_belajar ? `${isian.rombongan_belajar.nama_rombel} (${isian.rombongan_belajar.tingkat})` : '-' }}</span>
                                    </div>
                                </div>
                                <div class="detail-item">
                                    <i class="pi pi-calendar detail-icon"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Anggota Sejak</span>
                                        <span class="detail-value">{{ formatDate(isian.created_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="item-actions">
                                <Button
                                    icon="pi pi-calendar"
                                    label="Lihat Rekap"
                                    @click="openUserCalendar(isian)"
                                    severity="success"
                                    size="small"
                                    outlined
                                    class="w-full"
                                />
                                <Button
                                    v-if="isian.role !== 'admin' && isian.id !== authStore.user.id"
                                    icon="pi pi-sign-in"
                                    label="Masuk sebagai"
                                    @click="impersonateUser(isian)"
                                    severity="warning"
                                    size="small"
                                    outlined
                                />
                                <Button
                                    icon="pi pi-pencil"
                                    label="Edit"
                                    @click="openDialog(isian)"
                                    severity="info"
                                    size="small"
                                    outlined
                                />
                                <Button
                                    icon="pi pi-trash"
                                    label="Hapus"
                                    @click="confirmDelete(isian)"
                                    severity="danger"
                                    size="small"
                                    outlined
                                    :disabled="isian.id === authStore.user.id"
                                />
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <div class="empty-state">
                            <i class="pi pi-users" style="font-size: 3rem; color: #9ca3af;"></i>
                            <p>Tidak ada pengguna ditemukan</p>
                        </div>
                    </template>
                </DataView>

                <!-- Desktop Table View -->
                <DataTable
                    :value="users"
                    :loading="loading"
                    responsiveLayout="scroll"
                    class="mt-4 desktop-table w-full"
                >
                    <Column header="Foto">
                        <template #body="slotProps">
                            <div class="table-avatar">
                                <img
                                    v-if="slotProps.data.photo"
                                    :src="`${slotProps.data.photo}`"
                                    :alt="slotProps.data.name"
                                    class="table-avatar-img"
                                />
                                <i v-else class="pi pi-user table-avatar-icon"></i>
                            </div>
                        </template>
                    </Column>
                    <Column field="name" header="Nama"></Column>
                    <Column field="email" header="Email"></Column>
                    <Column field="role" header="Peran">
                        <template #body="slotProps">
                            <span :class="getRoleClass(slotProps.data.role)">
                                {{ getRoleLabel(slotProps.data.role) }}
                            </span>
                        </template>
                    </Column>
                    <Column header="Rombongan Belajar">
                        <template #body="slotProps">
                            {{ slotProps.data.rombongan_belajar ? `${slotProps.data.rombongan_belajar.nama_rombel} (${slotProps.data.rombongan_belajar.tingkat})` : '-' }}
                        </template>
                    </Column>
                    <Column field="created_at" header="Bergabung">
                        <template #body="slotProps">
                            {{ formatDate(slotProps.data.created_at) }}
                        </template>
                    </Column>
                    <Column header="Aksi">
                        <template #body="slotProps">
                            <div class="action-buttons">
                                <Button
                                    icon="pi pi-calendar"
                                    @click="openUserCalendar(slotProps.data)"
                                    severity="success"
                                    text
                                    rounded
                                    v-tooltip.top="'Lihat Rekap'"
                                />
                                <Button
                                    v-if="slotProps.data.role !== 'admin' && slotProps.data.id !== authStore.user.id"
                                    icon="pi pi-sign-in"
                                    @click="impersonateUser(slotProps.data)"
                                    severity="warning"
                                    text
                                    rounded
                                    v-tooltip.top="'Masuk sebagai pengguna ini'"
                                />
                                <Button
                                    icon="pi pi-pencil"
                                    @click="openDialog(slotProps.data)"
                                    severity="info"
                                    text
                                    rounded
                                    v-tooltip.top="'Edit'"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    @click="confirmDelete(slotProps.data)"
                                    severity="danger"
                                    text
                                    rounded
                                    :disabled="slotProps.data.id === authStore.user.id"
                                    v-tooltip.top="'Hapus'"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <Paginator
                    v-if="pagination.total > 0"
                    :rows="pagination.per_page"
                    :totalRecords="pagination.total"
                    :first="(pagination.current_page - 1) * pagination.per_page"
                    @page="onPageChange"
                    class="mt-4"
                />
            </template>
        </Card>

        <!-- Dialog for Add/Edit -->
        <Dialog
            v-model:visible="dialogVisible"
            :header="dialogMode === 'create' ? 'Tambah Pengguna' : 'Edit Pengguna'"
            :modal="true"
            :style="{ width: '500px' }"
        >
            <form @submit.prevent="saveUser" class="dialog-form">
                <div class="field">
                    <label for="name">Nama Lengkap *</label>
                    <InputText
                        id="name"
                        v-model="formData.name"
                        placeholder="Masukkan nama lengkap"
                        :class="{ 'p-invalid': errors.name }"
                    />
                    <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
                </div>

                <div class="field">
                    <label for="email">Email *</label>
                    <InputText
                        id="email"
                        v-model="formData.email"
                        type="email"
                        placeholder="Masukkan email"
                        :class="{ 'p-invalid': errors.email }"
                    />
                    <small v-if="errors.email" class="p-error">{{ errors.email }}</small>
                </div>

                <div class="field">
                    <label for="role">Peran *</label>
                    <Dropdown
                        id="role"
                        v-model="formData.role"
                        :options="roleOptions.filter(r => r.value)"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Pilih peran"
                        :class="{ 'p-invalid': errors.role }"
                    />
                    <small v-if="errors.role" class="p-error">{{ errors.role }}</small>
                </div>

                <div class="field">
                    <label for="gender">Jenis Kelamin</label>
                    <Dropdown
                        id="gender"
                        v-model="formData.gender"
                        :options="genderOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Pilih jenis kelamin"
                        showClear
                        :class="{ 'p-invalid': errors.gender }"
                    />
                    <small v-if="errors.gender" class="p-error">{{ errors.gender }}</small>
                </div>

                <div class="field">
                    <label for="rombongan_belajar">Rombongan Belajar</label>
                    <Dropdown
                        id="rombongan_belajar"
                        v-model="formData.rombongan_belajar_id"
                        :options="rombonganOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Pilih rombongan belajar"
                        :class="{ 'p-invalid': errors.rombongan_belajar_id }"
                        showClear
                    />
                    <small v-if="errors.rombongan_belajar_id" class="p-error">{{ errors.rombongan_belajar_id }}</small>
                </div>

                <div class="field">
                    <label for="password">Kata Sandi {{ dialogMode === 'edit' ? '(kosongkan untuk tetap menggunakan yang sekarang)' : '*' }}</label>
                    <Password
                        id="password"
                        v-model="formData.password"
                        placeholder="Masukkan kata sandi"
                        :feedback="dialogMode === 'create'"
                        toggleMask
                        :class="{ 'p-invalid': errors.password }"
                    />
                    <small v-if="errors.password" class="p-error">{{ errors.password }}</small>
                </div>

                <div class="dialog-actions">
                    <Button
                        label="Batal"
                        @click="dialogVisible = false"
                        severity="secondary"
                        text
                    />
                    <Button
                        type="submit"
                        label="Simpan"
                        :loading="saving"
                    />
                </div>
            </form>
        </Dialog>

        <!-- Import Dialog -->
        <Dialog
            v-model:visible="importDialogVisible"
            header="Import Siswa dari CSV"
            :modal="true"
            :style="{ width: '600px' }"
        >
            <div class="dialog-form">
                <div class="info-message">
                    <i class="pi pi-info-circle"></i>
                    <div>
                        <p><strong>Cara Import Siswa:</strong></p>
                        <ul>
                            <li><strong>Pilih Rombongan Manual:</strong> Pilih rombongan di dropdown, semua siswa akan masuk ke rombongan tersebut</li>
                            <li><strong>Import dengan Data Rombongan:</strong> Tambahkan kolom nama_rombel, tingkat, dan tahun_angkatan di CSV. Sistem akan otomatis membuat rombongan baru jika belum ada</li>
                        </ul>
                        <p class="note">Jika rombongan manual dipilih, data rombongan di CSV akan diabaikan.</p>
                    </div>
                </div>

                <div class="field">
                    <label>Template CSV</label>
                    <Button
                        label="Download Template"
                        icon="pi pi-download"
                        @click="downloadTemplate"
                        severity="secondary"
                        outlined
                        class="w-full"
                    />
                    <small class="help-text">Template berisi contoh format CSV yang diperlukan</small>
                </div>

                <div class="field">
                    <label for="import_rombongan">Rombongan Belajar (Opsional)</label>
                    <Dropdown
                        id="import_rombongan"
                        v-model="importFormData.rombongan_belajar_id"
                        :options="rombonganOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Pilih rombongan belajar"
                        showClear
                        class="w-full"
                    />
                    <small class="help-text">Jika dipilih, semua siswa yang diimport akan otomatis masuk ke rombongan ini</small>
                </div>

                <div class="field">
                    <label for="csv_file">File CSV *</label>
                    <input
                        type="file"
                        id="csv_file"
                        ref="fileInput"
                        @change="handleFileChange"
                        accept=".csv"
                        class="file-input"
                    />
                    <small v-if="importFormData.file" class="file-name">
                        <i class="pi pi-file"></i>
                        {{ importFormData.file.name }}
                    </small>
                    <small v-if="importErrors.file" class="p-error">{{ importErrors.file }}</small>
                </div>

                <div v-if="importResult" class="import-result">
                    <div :class="['result-card', importResult.success ? 'success' : 'warning']">
                        <div class="result-header">
                            <i :class="['pi', importResult.success ? 'pi-check-circle' : 'pi-exclamation-triangle']"></i>
                            <h4>{{ importResult.message }}</h4>
                        </div>
                        <div class="result-stats">
                            <div class="stat">
                                <span class="stat-label">Berhasil:</span>
                                <span class="stat-value success">{{ importResult.imported_count }}</span>
                            </div>
                            <div class="stat">
                                <span class="stat-label">Gagal:</span>
                                <span class="stat-value error">{{ importResult.skipped_count }}</span>
                            </div>
                        </div>
                        <div v-if="importResult.errors && importResult.errors.length > 0" class="result-errors">
                            <h5>Detail Error:</h5>
                            <div class="error-list">
                                <div v-for="(error, index) in importResult.errors.slice(0, 5)" :key="index" class="error-item">
                                    <strong>Baris {{ error.row }}:</strong>
                                    <span>{{ Object.values(error.errors).flat().join(', ') }}</span>
                                </div>
                                <div v-if="importResult.errors.length > 5" class="more-errors">
                                    + {{ importResult.errors.length - 5 }} error lainnya
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dialog-actions">
                    <Button
                        label="Tutup"
                        @click="closeImportDialog"
                        severity="secondary"
                        text
                    />
                    <Button
                        label="Import"
                        icon="pi pi-upload"
                        @click="submitImport"
                        :disabled="!importFormData.file"
                        :loading="importing"
                    />
                </div>
            </div>
        </Dialog>

        <!-- User Calendar Dialog -->
        <UserMonthlyCalendar
            v-model:visible="calendarVisible"
            :userId="selectedUser?.id"
            :userName="selectedUser?.name"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import api from '../api/axios';
import UserMonthlyCalendar from '../components/UserMonthlyCalendar.vue';

import { DataView } from 'primevue';

const authStore = useAuthStore();
const toast = useToast();
const confirm = useConfirm();

const users = ref([]);
const loading = ref(false);
const saving = ref(false);
const dialogVisible = ref(false);
const dialogMode = ref('create');
const errors = ref({});
const calendarVisible = ref(false);
const selectedUser = ref(null);
const rombonganOptions = ref([]);

// Import states
const importDialogVisible = ref(false);
const importing = ref(false);
const importFormData = ref({
    file: null,
    rombongan_belajar_id: null,
});
const importErrors = ref({});
const importResult = ref(null);
const fileInput = ref(null);

const filters = ref({
    role: null,
    search: '',
});

const pagination = ref({
    current_page: 1,
    per_page: 15,
    total: 0,
});

const roleOptions = [
    { label: 'All Roles', value: null },
    { label: 'Admin', value: 'admin' },
    { label: 'User', value: 'user' },
];

const genderOptions = [
    { label: 'Laki-laki', value: 'male' },
    { label: 'Perempuan', value: 'female' },
];

const formData = ref({
    id: null,
    name: '',
    email: '',
    role: 'user',
    gender: null,
    password: '',
    rombongan_belajar_id: null,
});

let searchTimeout = null;

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchUsers();
    }, 500);
};

const fetchUsers = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.value.current_page,
            ...filters.value,
        };

        const response = await api.get('/users', { params });
        // Map users to include full photo URL
        const usersWithPhoto = response.data.data.map(user => {
            return {
                ...user,
                photo: user.photo ? `/storage/${user.photo}` : null
            };
        });

        users.value = usersWithPhoto;
        pagination.value = {
            current_page: response.data.current_page,
            per_page: response.data.per_page,
            total: response.data.total,
        };
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch users',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const fetchRombonganOptions = async () => {
    try {
        const response = await api.get('/rombongan-belajar', { params: { per_page: 1000 } });
        rombonganOptions.value = [
            { label: 'Tidak ada rombongan', value: null },
            ...response.data.data.map(item => ({
                label: `${item.nama_rombel} (${item.tingkat} - ${item.tahun_angkatan})`,
                value: item.id
            }))
        ];
    } catch (error) {
        console.error('Failed to fetch rombongan options:', error);
    }
};

const openDialog = (user = null) => {
    errors.value = {};
    if (user) {
        dialogMode.value = 'edit';
        formData.value = {
            id: user.id,
            name: user.name,
            email: user.email,
            role: user.role,
            gender: user.gender || null,
            password: '',
            rombongan_belajar_id: user.rombongan_belajar_id || null,
        };
    } else {
        dialogMode.value = 'create';
        formData.value = {
            id: null,
            name: '',
            email: '',
            role: 'user',
            gender: null,
            password: '',
            rombongan_belajar_id: null,
        };
    }
    dialogVisible.value = true;
};

const saveUser = async () => {
    errors.value = {};
    saving.value = true;

    try {
        const data = {
            name: formData.value.name,
            email: formData.value.email,
            role: formData.value.role,
            gender: formData.value.gender,
            rombongan_belajar_id: formData.value.rombongan_belajar_id,
        };

        if (formData.value.password) {
            data.password = formData.value.password;
        }

        if (dialogMode.value === 'edit') {
            await api.put(`/users/${formData.value.id}`, data);
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'User updated successfully',
                life: 3000
            });
        } else {
            await api.post('/users', data);
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'User created successfully',
                life: 3000
            });
        }

        dialogVisible.value = false;
        fetchUsers();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response?.data?.message || 'Failed to save user',
                life: 3000
            });
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (user) => {
    confirm.require({
        message: `Are you sure you want to delete user "${user.name}"?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        accept: () => deleteUser(user.id),
    });
};

const deleteUser = async (id) => {
    try {
        await api.delete(`/users/${id}`);
        toast.add({ 
            severity: 'success', 
            summary: 'Success', 
            detail: 'User deleted successfully', 
            life: 3000 
        });
        fetchUsers();
    } catch (error) {
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: error.response?.data?.message || 'Failed to delete user', 
            life: 3000 
        });
    }
};

const clearFilters = () => {
    filters.value = {
        role: null,
        search: '',
    };
    fetchUsers();
};

const onPageChange = (event) => {
    pagination.value.current_page = event.page + 1;
    fetchUsers();
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const getRoleLabel = (role) => {
    return role.charAt(0).toUpperCase() + role.slice(1);
};

const getRoleClass = (role) => {
    return role === 'admin' ? 'role-badge admin' : 'role-badge user';
};

const impersonateUser = async (user) => {
    try {
        const response = await api.post(`/impersonate/${user.id}`);
        
        // Update auth store with impersonated user data
        authStore.token = response.data.token;
        authStore.user = response.data.user;
        authStore.isImpersonating = true;
        authStore.originalAdminId = response.data.original_admin_id;
        
        localStorage.setItem('token', response.data.token);
        localStorage.setItem('user', JSON.stringify(response.data.user));
        localStorage.setItem('is_impersonating', 'true');
        localStorage.setItem('original_admin_id', response.data.original_admin_id);
        
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Now logged in as ${user.name}`,
            life: 3000
        });
        
        // Redirect to dashboard
        setTimeout(() => {
            window.location.href = '/';
        }, 1000);
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to impersonate user',
            life: 3000
        });
    }
};

const openUserCalendar = (user) => {
    selectedUser.value = user;
    calendarVisible.value = true;
};

// Import functions
const openImportDialog = () => {
    importFormData.value = {
        file: null,
        rombongan_belajar_id: null,
    };
    importErrors.value = {};
    importResult.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    importDialogVisible.value = true;
};

const closeImportDialog = () => {
    importDialogVisible.value = false;
    importFormData.value = {
        file: null,
        rombongan_belajar_id: null,
    };
    importErrors.value = {};
    importResult.value = null;
    if (fileInput.value) {
        fileInput.value.value = '';
    }
    fetchUsers(); // Refresh user list
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        if (file.type !== 'text/csv' && !file.name.endsWith('.csv')) {
            importErrors.value.file = 'File harus berformat CSV';
            importFormData.value.file = null;
            return;
        }
        importFormData.value.file = file;
        importErrors.value.file = '';
    }
};

const downloadTemplate = async () => {
    try {
        const response = await api.get('/users/import/template', {
            responseType: 'blob'
        });
        
        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', 'user_import_template.csv');
        document.body.appendChild(link);
        link.click();
        link.remove();
        
        toast.add({
            severity: 'success',
            summary: 'Berhasil',
            detail: 'Template CSV berhasil didownload',
            life: 3000
        });
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Gagal mendownload template',
            life: 3000
        });
    }
};

const submitImport = async () => {
    if (!importFormData.value.file) {
        importErrors.value.file = 'File CSV harus dipilih';
        return;
    }
    
    importing.value = true;
    importErrors.value = {};
    
    try {
        const formData = new FormData();
        formData.append('file', importFormData.value.file);
        if (importFormData.value.rombongan_belajar_id) {
            formData.append('rombongan_belajar_id', importFormData.value.rombongan_belajar_id);
        }
        
        const response = await api.post('/users/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        });
        
        importResult.value = {
            success: response.data.skipped_count === 0,
            message: response.data.message,
            imported_count: response.data.imported_count,
            skipped_count: response.data.skipped_count,
            errors: response.data.errors || []
        };
        
        if (response.data.imported_count > 0) {
            toast.add({
                severity: 'success',
                summary: 'Import Selesai',
                detail: `${response.data.imported_count} siswa berhasil diimport`,
                life: 5000
            });
        }
        
        if (response.data.skipped_count > 0) {
            toast.add({
                severity: 'warn',
                summary: 'Perhatian',
                detail: `${response.data.skipped_count} baris dilewati karena error`,
                life: 5000
            });
        }
    } catch (error) {
        if (error.response?.data?.errors) {
            importErrors.value = error.response.data.errors;
        }
        
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Gagal mengimport data',
            life: 3000
        });
    } finally {
        importing.value = false;
    }
};

onMounted(() => {
    fetchUsers();
    fetchRombonganOptions();
});
</script>

<style scoped>
/* Mobile-first styles */
.users {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.page-title {
    font-size: 1.5rem;
    color: white;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.filters {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 140px;
    flex: 1;
}

.filter-group label {
    font-weight: 600;
    color: white;
    font-size: 0.8rem;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: left;
    flex-wrap: wrap;
}

.role-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.role-badge.admin {
    background: #dbeafe;
    color: #1e40af;
}

.role-badge.user {
    background: #f3f4f6;
    color: #374151;
}

.dialog-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    padding: 0.5rem 0;
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

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 0.5rem;
    flex-wrap: wrap;
}

.mt-4 {
    margin-top: 1.5rem;
}

.w-full {
    width: 100%;
}

/* DataView Styles */
.user-dataview {
    display: block;
}

/* Hide desktop table on mobile */
.desktop-table {
    display: none;
}

.dataview-item {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(15px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 16px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    box-shadow:
        0 4px 16px rgba(0, 0, 0, 0.1),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
    transition: all 0.3s ease;
}

.dataview-item:hover {
    background: rgba(255, 255, 255, 0.18);
    box-shadow:
        0 8px 24px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    transform: translateY(-3px);
    border-color: rgba(255, 255, 255, 0.35);
}

.user-avatar-section {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: flex-start;
}

.user-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: rgba(16, 185, 129, 0.4);
    backdrop-filter: blur(10px);
    border: 2px solid rgba(16, 185, 129, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow:
        0 4px 12px rgba(16, 185, 129, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    overflow: hidden;
}

.user-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.user-avatar i {
    font-size: 1.5rem;
    color: white;
}

.user-main-info {
    flex: 1;
    min-width: 0;
}

.user-name-wrapper {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-bottom: 0.5rem;
}

.user-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: white;
    margin: 0;
    line-height: 1.4;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.user-email {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    word-break: break-all;
}

.user-email i {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
}

.user-details {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 0.875rem;
    margin-bottom: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.detail-icon {
    font-size: 1rem;
    color: rgba(16, 185, 129, 0.9);
}

.detail-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.detail-value {
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
}

.item-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    padding-top: 0.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem 1rem;
    gap: 1rem;
}

.empty-state p {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.7);
    margin: 0;
}

/* Tablet and Desktop: Show table, hide DataView */
@media (min-width: 768px) {
    /* Hide mobile DataView */
    .user-dataview {
        display: none;
    }

    /* Show desktop table */
    .desktop-table {
        display: table;
    }
    .users {
        gap: 1.5rem;
    }

    .page-title {
        font-size: 1.75rem;
    }

    .filters {
        gap: 1rem;
    }

    .filter-group {
        min-width: 180px;
        flex: 0 1 auto;
    }

    .filter-group label {
        font-size: 0.875rem;
    }

    .role-badge {
        font-size: 0.875rem;
    }

    .dialog-form {
        gap: 1.5rem;
        padding: 1rem 0;
    }

    .field label {
        font-size: 1rem;
    }

    .dialog-actions {
        gap: 1rem;
        margin-top: 1rem;
    }

    /* DataView tablet enhancements */
    .dataview-item {
        padding: 1.5rem;
        border-radius: 16px;
    }

    .user-avatar {
        width: 64px;
        height: 64px;
    }

    .user-avatar i {
        font-size: 1.75rem;
    }

    .user-name {
        font-size: 1.25rem;
    }

    .user-email {
        font-size: 1rem;
    }

    .user-details {
        padding: 1rem;
    }

    .detail-icon {
        font-size: 1.125rem;
    }

    .detail-value {
        font-size: 1rem;
    }

    .item-actions {
        gap: 0.75rem;
        padding-top: 0.75rem;
    }
}

/* Desktop styles (1024px and up) */
@media (min-width: 1024px) {
    .users {
        gap: 2rem;
    }

    .page-title {
        font-size: 2rem;
    }

    .filter-group {
        min-width: 200px;
    }

    /* DataView desktop enhancements */
    .dataview-item {
        padding: 1.75rem;
    }

    .user-avatar-section {
        gap: 1.25rem;
    }

    .user-avatar {
        width: 72px;
        height: 72px;
    }

    .user-avatar i {
        font-size: 2rem;
    }

    .user-name {
        font-size: 1.375rem;
    }

    .user-details {
        padding: 1.25rem;
        display: flex;
        gap: 1.5rem;
    }

    .detail-item {
        flex: 1;
    }

    .item-actions {
        gap: 0.875rem;
        padding-top: 1rem;
    }
    
    /* Table avatar styles */
    .table-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(16, 185, 129, 0.2);
    }
    
    .table-avatar-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .table-avatar-icon {
        font-size: 1.2rem;
        color: rgba(255, 255, 255, 0.7);
    }
}

/* DataTable responsive overrides */
:deep(.p-datatable) {
    font-size: 0.875rem;
}

:deep(.p-datatable .p-datatable-thead > tr > th) {
    padding: 0.75rem 0.5rem;
    font-size: 0.875rem;
}

:deep(.p-datatable .p-datatable-tbody > tr > td) {
    padding: 0.75rem 0.5rem;
}

@media (min-width: 768px) {
    :deep(.p-datatable) {
        font-size: 1rem;
    }

    :deep(.p-datatable .p-datatable-thead > tr > th) {
        padding: 1rem 1rem;
        font-size: 1rem;
    }

    :deep(.p-datatable .p-datatable-tbody > tr > td) {
        padding: 1rem 1rem;
    }
}

/* Import Dialog Styles */
.header-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.info-message {
    display: flex;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    background: rgba(59, 130, 246, 0.1);
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.info-message i {
    color: rgba(59, 130, 246, 0.9);
    font-size: 1.25rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.info-message p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.875rem;
    margin: 0 0 0.5rem 0;
    line-height: 1.5;
}

.info-message ul {
    margin: 0.5rem 0;
    padding-left: 1.5rem;
    color: rgba(255, 255, 255, 0.85);
}

.info-message li {
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    line-height: 1.5;
}

.info-message .note {
    margin-top: 0.75rem;
    font-size: 0.8rem;
    color: rgba(245, 158, 11, 0.9);
    font-style: italic;
}

.help-text {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.75rem;
    font-style: italic;
    display: block;
    margin-top: 0.25rem;
}

.file-input {
    width: 100%;
    padding: 0.75rem;
    border: 2px dashed rgba(255, 255, 255, 0.3);
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.05);
    color: white;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.file-input:hover {
    border-color: rgba(59, 130, 246, 0.5);
    background: rgba(59, 130, 246, 0.1);
}

.file-input:focus {
    outline: none;
    border-color: rgba(59, 130, 246, 0.7);
    background: rgba(59, 130, 246, 0.15);
}

.file-name {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(16, 185, 129, 0.9);
    font-size: 0.875rem;
    margin-top: 0.5rem;
    padding: 0.5rem;
    background: rgba(16, 185, 129, 0.1);
    border-radius: 6px;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.file-name i {
    color: rgba(16, 185, 129, 0.9);
}

.import-result {
    margin-top: 1rem;
}

.result-card {
    border-radius: 12px;
    padding: 1.25rem;
    border: 1px solid;
}

.result-card.success {
    background: rgba(16, 185, 129, 0.1);
    border-color: rgba(16, 185, 129, 0.3);
}

.result-card.warning {
    background: rgba(245, 158, 11, 0.1);
    border-color: rgba(245, 158, 11, 0.3);
}

.result-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.result-header i {
    font-size: 1.5rem;
}

.result-card.success .result-header i {
    color: rgba(16, 185, 129, 0.9);
}

.result-card.warning .result-header i {
    color: rgba(245, 158, 11, 0.9);
}

.result-header h4 {
    margin: 0;
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.95);
    font-weight: 600;
}

.result-stats {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.result-stats .stat {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.result-stats .stat-label {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
}

.result-stats .stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
}

.result-stats .stat-value.success {
    background: rgba(16, 185, 129, 0.2);
    color: rgba(16, 185, 129, 0.95);
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.result-stats .stat-value.error {
    background: rgba(239, 68, 68, 0.2);
    color: rgba(239, 68, 68, 0.95);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.result-errors {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.result-errors h5 {
    margin: 0 0 0.75rem 0;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
}

.error-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.error-item {
    padding: 0.75rem;
    background: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.2);
    border-radius: 6px;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.5;
}

.error-item strong {
    color: rgba(239, 68, 68, 0.95);
    display: block;
    margin-bottom: 0.25rem;
}

.more-errors {
    padding: 0.5rem 0.75rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 6px;
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    text-align: center;
    font-style: italic;
}

@media (min-width: 768px) {
    .header-actions {
        gap: 0.75rem;
    }

    .info-message {
        padding: 1.25rem;
        gap: 1rem;
    }

    .info-message i {
        font-size: 1.5rem;
    }

    .info-message p {
        font-size: 1rem;
    }

    .help-text {
        font-size: 0.875rem;
    }

    .file-input {
        padding: 1rem;
        font-size: 1rem;
    }

    .file-name {
        font-size: 1rem;
        padding: 0.75rem;
    }

    .result-card {
        padding: 1.5rem;
    }

    .result-header i {
        font-size: 1.75rem;
    }

    .result-header h4 {
        font-size: 1.125rem;
    }

    .result-stats {
        gap: 2rem;
    }

    .result-stats .stat-label {
        font-size: 1rem;
    }

    .result-stats .stat-value {
        font-size: 1.5rem;
        padding: 0.375rem 1rem;
    }

    .result-errors h5 {
        font-size: 1rem;
    }

    .error-item {
        padding: 1rem;
        font-size: 0.875rem;
    }

    .more-errors {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
}
</style>
