<template>
    <div class="rombongan-belajar">
        <div class="page-header">
            <h1 class="page-title">Manajemen Rombongan Belajar</h1>
            <div class="header-actions">
                <Button
                    label="Naik Kelas"
                    icon="pi pi-arrow-up"
                    @click="openPromoteDialog()"
                    severity="info"
                />
                <Button
                    label="Kelulusan"
                    icon="pi pi-graduation-cap"
                    @click="openGraduateDialog()"
                    severity="success"
                />
                <Button
                    label="Tambah Rombongan"
                    icon="pi pi-plus"
                    @click="openDialog()"
                />
            </div>
        </div>

        <Card>
            <template #content>
                <div class="filters">
                    <div class="filter-group">
                        <label>Cari</label>
                        <InputText
                            v-model="filters.search"
                            placeholder="Cari berdasarkan nama rombel atau tingkat"
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
                <DataView :value="rombonganBelajar" :loading="loading" class="rombongan-dataview">
                    <template #list="slotProps">
                        <div class="dataview-item" v-for="item in slotProps.items" :key="item.id">
                            <div class="rombongan-info">
                                <div class="rombongan-main">
                                    <h3 class="rombongan-name">{{ item.nama_rombel }}</h3>
                                    <p class="rombongan-details">
                                        <span class="detail-badge">{{ item.tingkat }}</span>
                                        <span class="detail-year">Angkatan {{ item.tahun_angkatan }}</span>
                                    </p>
                                </div>
                                <div class="rombongan-stats">
                                    <div class="stat-item">
                                        <i class="pi pi-users"></i>
                                        <span>{{ item.users_count }} Siswa</span>
                                    </div>
                                </div>
                            </div>

                            <div class="item-actions">
                                <Button
                                    icon="pi pi-eye"
                                    label="Lihat Siswa"
                                    @click="viewUsers(item)"
                                    severity="info"
                                    size="small"
                                    outlined
                                    class="w-full"
                                />
                                <Button
                                    icon="pi pi-pencil"
                                    label="Edit"
                                    @click="openDialog(item)"
                                    severity="success"
                                    size="small"
                                    outlined
                                />
                                <Button
                                    icon="pi pi-trash"
                                    label="Hapus"
                                    @click="confirmDelete(item)"
                                    severity="danger"
                                    size="small"
                                    outlined
                                />
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <div class="empty-state">
                            <i class="pi pi-graduation-cap" style="font-size: 3rem; color: #9ca3af;"></i>
                            <p>Tidak ada rombongan belajar ditemukan</p>
                        </div>
                    </template>
                </DataView>

                <!-- Desktop Table View -->
                <DataTable
                    :value="rombonganBelajar"
                    :loading="loading"
                    responsiveLayout="scroll"
                    class="mt-4 desktop-table w-full"
                >
                    <Column field="nama_rombel" header="Nama Rombel"></Column>
                    <Column field="tingkat" header="Tingkat"></Column>
                    <Column field="tahun_angkatan" header="Tahun Angkatan"></Column>
                    <Column header="Jumlah Siswa">
                        <template #body="slotProps">
                            {{ slotProps.data.users_count }}
                        </template>
                    </Column>
                    <Column header="Aksi">
                        <template #body="slotProps">
                            <div class="action-buttons">
                                <Button
                                    icon="pi pi-eye"
                                    @click="viewUsers(slotProps.data)"
                                    severity="info"
                                    text
                                    rounded
                                    v-tooltip.top="'Lihat Siswa'"
                                />
                                <Button
                                    icon="pi pi-pencil"
                                    @click="openDialog(slotProps.data)"
                                    severity="success"
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
            :header="dialogMode === 'create' ? 'Tambah Rombongan Belajar' : 'Edit Rombongan Belajar'"
            :modal="true"
            :style="{ width: '500px' }"
        >
            <form @submit.prevent="saveRombongan" class="dialog-form">
                <div class="field">
                    <label for="nama_rombel">Nama Rombel *</label>
                    <InputText
                        id="nama_rombel"
                        v-model="formData.nama_rombel"
                        placeholder="Masukkan nama rombel"
                        :class="{ 'p-invalid': errors.nama_rombel }"
                    />
                    <small v-if="errors.nama_rombel" class="p-error">{{ errors.nama_rombel }}</small>
                </div>

                <div class="field">
                    <label for="tingkat">Tingkat *</label>
                    <InputText
                        id="tingkat"
                        v-model="formData.tingkat"
                        placeholder="Contoh: X, XI, XII"
                        :class="{ 'p-invalid': errors.tingkat }"
                    />
                    <small v-if="errors.tingkat" class="p-error">{{ errors.tingkat }}</small>
                </div>

                <div class="field">
                    <label for="tahun_angkatan">Tahun Angkatan *</label>
                    <InputText
                        id="tahun_angkatan"
                        v-model="formData.tahun_angkatan"
                        placeholder="Contoh: 2023"
                        :class="{ 'p-invalid': errors.tahun_angkatan }"
                    />
                    <small v-if="errors.tahun_angkatan" class="p-error">{{ errors.tahun_angkatan }}</small>
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

        <!-- Promote Dialog -->
        <Dialog
            v-model:visible="promoteDialogVisible"
            header="Naik Kelas Rombongan Belajar"
            :modal="true"
            :style="{ width: '600px' }"
        >
            <div class="dialog-form">
                <div class="info-message">
                    <i class="pi pi-info-circle"></i>
                    <p>Pilih tahun angkatan yang akan dinaikkan. Semua rombongan belajar dalam angkatan tersebut akan naik ke tingkat berikutnya secara otomatis.</p>
                </div>

                <div class="field">
                    <label for="promote_tahun_angkatan">Pilih Tahun Angkatan *</label>
                    <Dropdown
                        id="promote_tahun_angkatan"
                        v-model="promoteTahunAngkatan"
                        :options="tahunAngkatanOptions"
                        placeholder="Pilih tahun angkatan"
                        :loading="loadingTahunAngkatan"
                        class="w-full"
                        @change="loadPromotePreview"
                    />
                </div>

                <div v-if="promotePreviewData.length > 0" class="promotion-preview">
                    <div class="preview-card">
                        <h4>Preview Kenaikan Kelas ({{ promotePreviewData.length }} Rombongan)</h4>
                        <div class="preview-list">
                            <div v-for="(item, index) in promotePreviewData" :key="index" class="preview-list-item">
                                <span class="rombel-name">{{ item.nama_rombel }}</span>
                                <div class="tingkat-change">
                                    <span class="tingkat current">{{ item.tingkat }}</span>
                                    <i class="pi pi-arrow-right"></i>
                                    <span class="tingkat next">{{ item.next_tingkat }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="dialog-actions">
                    <Button
                        label="Batal"
                        @click="promoteDialogVisible = false"
                        severity="secondary"
                        text
                    />
                    <Button
                        label="Naikkan Kelas"
                        icon="pi pi-arrow-up"
                        @click="confirmPromote"
                        :disabled="!promoteTahunAngkatan"
                        :loading="promoting"
                    />
                </div>
            </div>
        </Dialog>

        <!-- Graduate Dialog -->
        <Dialog
            v-model:visible="graduateDialogVisible"
            header="Kelulusan Rombongan Belajar"
            :modal="true"
            :style="{ width: '500px' }"
        >
            <div class="dialog-form">
                <div class="warning-message">
                    <i class="pi pi-exclamation-triangle"></i>
                    <p><strong>Perhatian:</strong> Tindakan ini akan meluluskan semua siswa pada tingkat terpilih. Siswa akan di-detach dari rombongan dan rombongan belajar akan dihapus.</p>
                </div>

                <div class="field">
                    <label for="graduate_tingkat">Pilih Tingkat yang Akan Lulus *</label>
                    <Dropdown
                        id="graduate_tingkat"
                        v-model="graduateTingkat"
                        :options="graduatableTingkatOptions"
                        placeholder="Pilih tingkat"
                        class="w-full"
                    />
                </div>

                <div class="dialog-actions">
                    <Button
                        label="Batal"
                        @click="graduateDialogVisible = false"
                        severity="secondary"
                        text
                    />
                    <Button
                        label="Luluskan"
                        icon="pi pi-graduation-cap"
                        @click="confirmGraduate"
                        :disabled="!graduateTingkat"
                        :loading="graduating"
                        severity="danger"
                    />
                </div>
            </div>
        </Dialog>

        <!-- Users Dialog -->
        <Dialog
            v-model:visible="usersDialogVisible"
            :header="`Siswa di ${selectedRombongan?.nama_rombel || ''}`"
            :modal="true"
            :style="{ width: '900px' }"
        >
            <div v-if="selectedRombonganUsers.length > 0">
                <!-- Search and Actions -->
                <div class="users-dialog-header">
                    <div class="search-section">
                        <InputText
                            v-model="userSearchQuery"
                            placeholder="Cari siswa..."
                            @input="filterUsers"
                            class="search-input"
                        />
                        <i class="pi pi-search search-icon"></i>
                    </div>
                    <div class="actions-section">
                        <Button
                            v-if="selectedUsers.length > 0"
                            label="Detach Terpilih"
                            icon="pi pi-times"
                            @click="confirmBulkDetach"
                            severity="danger"
                            size="small"
                        />
                        <Button
                            label="Pilih Semua"
                            icon="pi pi-check-square"
                            @click="selectAllUsers"
                            severity="info"
                            size="small"
                            outlined
                            :disabled="filteredUsers.length === 0"
                        />
                        <Button
                            v-if="selectedUsers.length > 0"
                            label="Batal Pilih"
                            icon="pi pi-times-circle"
                            @click="clearSelection"
                            severity="secondary"
                            size="small"
                            text
                        />
                    </div>
                </div>

                <!-- Users Table -->
                <DataTable
                    :value="filteredUsers"
                    :selection="selectedUsers"
                    @selection-change="onSelectionChange"
                    selectionMode="multiple"
                    class="w-full users-table"
                    :paginator="filteredUsers.length > 10"
                    :rows="10"
                >
                    <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
                    <Column field="name" header="Nama" sortable></Column>
                    <Column field="email" header="Email" sortable></Column>
                    <Column field="role" header="Peran" sortable>
                        <template #body="slotProps">
                            <span :class="getRoleClass(slotProps.data.role)">
                                {{ getRoleLabel(slotProps.data.role) }}
                            </span>
                        </template>
                    </Column>
                    <Column header="Aksi">
                        <template #body="slotProps">
                            <Button
                                icon="pi pi-times"
                                @click="confirmDetachUser(slotProps.data)"
                                severity="danger"
                                text
                                rounded
                                v-tooltip.top="'Detach dari rombongan'"
                            />
                        </template>
                    </Column>
                </DataTable>

                <!-- Selection Info -->
                <div v-if="selectedUsers.length > 0" class="selection-info">
                    <i class="pi pi-info-circle"></i>
                    <span>{{ selectedUsers.length }} siswa dipilih</span>
                </div>
            </div>
            <div v-else class="empty-state">
                <i class="pi pi-users" style="font-size: 2rem; color: #9ca3af;"></i>
                <p>Belum ada siswa di rombongan ini</p>
            </div>
        </Dialog>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import api from '../api/axios';

import { DataView } from 'primevue';

const toast = useToast();
const confirm = useConfirm();

const rombonganBelajar = ref([]);
const loading = ref(false);
const saving = ref(false);
const dialogVisible = ref(false);
const dialogMode = ref('create');
const usersDialogVisible = ref(false);
const selectedRombongan = ref(null);
const selectedRombonganUsers = ref([]);
const filteredUsers = ref([]);
const selectedUsers = ref([]);
const userSearchQuery = ref('');
const errors = ref({});

// Promotion and Graduation states
const promoteDialogVisible = ref(false);
const graduateDialogVisible = ref(false);
const promoteTahunAngkatan = ref(null);
const graduateTingkat = ref(null);
const promoting = ref(false);
const graduating = ref(false);
const tahunAngkatanOptions = ref([]);
const loadingTahunAngkatan = ref(false);
const promotePreviewData = ref([]);

// Tingkat mapping for promotions
const tingkatMap = {
    // SMA
    'X': 'XI',
    'XI': 'XII',
    '10': '11',
    '11': '12',
    // SMP - Angka
    '7': '8',
    '8': '9',
    // SMP - Romawi
    'VII': 'VIII',
    'VIII': 'IX',
    // SD - Angka
    '1': '2',
    '2': '3',
    '3': '4',
    '4': '5',
    '5': '6',
    '6': '7',
    // SD - Romawi
    'I': 'II',
    'II': 'III',
    'III': 'IV',
    'IV': 'V',
    'V': 'VI',
    'VI': 'VII',
};

// Available tingkat options for graduation (final levels)
const graduatableTingkatOptions = ref(['XII', '12', 'IX', '9', 'VI', '6']);

const filters = ref({
    search: '',
});

const pagination = ref({
    current_page: 1,
    per_page: 15,
    total: 0,
});

const formData = ref({
    id: null,
    nama_rombel: '',
    tingkat: '',
    tahun_angkatan: null,
});

let searchTimeout = null;

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchRombonganBelajar();
    }, 500);
};

const fetchRombonganBelajar = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.value.current_page,
            ...filters.value,
        };

        const response = await api.get('/rombongan-belajar', { params });
        rombonganBelajar.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            per_page: response.data.per_page,
            total: response.data.total,
        };
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch rombongan belajar',
            life: 3000
        });
    } finally {
        loading.value = false;
    }
};

const openDialog = (item = null) => {
    errors.value = {};
    if (item) {
        dialogMode.value = 'edit';
        formData.value = {
            id: item.id,
            nama_rombel: item.nama_rombel,
            tingkat: item.tingkat,
            tahun_angkatan: item.tahun_angkatan,
        };
    } else {
        dialogMode.value = 'create';
        formData.value = {
            id: null,
            nama_rombel: '',
            tingkat: '',
            tahun_angkatan: null,
        };
    }
    dialogVisible.value = true;
};

const saveRombongan = async () => {
    errors.value = {};
    saving.value = true;

    try {
        const data = {
            nama_rombel: formData.value.nama_rombel,
            tingkat: formData.value.tingkat,
            tahun_angkatan: formData.value.tahun_angkatan,
        };

        if (dialogMode.value === 'edit') {
            await api.put(`/rombongan-belajar/${formData.value.id}`, data);
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Rombongan Belajar updated successfully',
                life: 3000
            });
        } else {
            await api.post('/rombongan-belajar', data);
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Rombongan Belajar created successfully',
                life: 3000
            });
        }

        dialogVisible.value = false;
        fetchRombonganBelajar();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response?.data?.message || 'Failed to save rombongan belajar',
                life: 3000
            });
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (item) => {
    confirm.require({
        message: `Are you sure you want to delete "${item.nama_rombel}"?`,
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        accept: () => deleteRombongan(item.id),
    });
};

const deleteRombongan = async (id) => {
    try {
        await api.delete(`/rombongan-belajar/${id}`);
        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'Rombongan Belajar deleted successfully',
            life: 3000
        });
        fetchRombonganBelajar();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to delete rombongan belajar',
            life: 3000
        });
    }
};

const viewUsers = async (item) => {
    selectedRombongan.value = item;
    selectedUsers.value = [];
    userSearchQuery.value = '';
    try {
        const response = await api.get(`/rombongan-belajar/${item.id}`);
        selectedRombonganUsers.value = response.data.users || [];
        filteredUsers.value = [...selectedRombonganUsers.value];
        usersDialogVisible.value = true;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch users',
            life: 3000
        });
    }
};

const clearFilters = () => {
    filters.value = {
        search: '',
    };
    fetchRombonganBelajar();
};

const onPageChange = (event) => {
    pagination.value.current_page = event.page + 1;
    fetchRombonganBelajar();
};

const getRoleLabel = (role) => {
    return role.charAt(0).toUpperCase() + role.slice(1);
};

const getRoleClass = (role) => {
    return role === 'admin' ? 'role-badge admin' : 'role-badge user';
};

const filterUsers = () => {
    const query = userSearchQuery.value.toLowerCase();
    filteredUsers.value = selectedRombonganUsers.value.filter(user =>
        user.name.toLowerCase().includes(query) ||
        user.email.toLowerCase().includes(query) ||
        user.role.toLowerCase().includes(query)
    );
};

const onSelectionChange = (selection) => {
    selectedUsers.value = selection;
};

const selectAllUsers = () => {
    selectedUsers.value = [...filteredUsers.value];
};

const clearSelection = () => {
    selectedUsers.value = [];
};

const confirmDetachUser = (user) => {
    confirm.require({
        message: `Detach "${user.name}" dari rombongan "${selectedRombongan.value.nama_rombel}"?`,
        header: 'Konfirmasi Detach',
        icon: 'pi pi-exclamation-triangle',
        accept: () => detachUser(user.id),
    });
};

const confirmBulkDetach = () => {
    confirm.require({
        message: `Detach ${selectedUsers.value.length} siswa terpilih dari rombongan "${selectedRombongan.value.nama_rombel}"?`,
        header: 'Konfirmasi Bulk Detach',
        icon: 'pi pi-exclamation-triangle',
        accept: () => bulkDetachUsers(),
    });
};

const detachUser = async (userId) => {
    try {
        await api.put(`/users/${userId}`, {
            rombongan_belajar_id: null
        });

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: 'User berhasil di-detach dari rombongan',
            life: 3000
        });

        // Refresh the users list
        await refreshUsersList();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Failed to detach user',
            life: 3000
        });
    }
};

const bulkDetachUsers = async () => {
    try {
        // Detach users one by one
        const promises = selectedUsers.value.map(user =>
            api.put(`/users/${user.id}`, { rombongan_belajar_id: null })
        );

        await Promise.all(promises);

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `${selectedUsers.value.length} siswa berhasil di-detach`,
            life: 3000
        });

        selectedUsers.value = [];
        await refreshUsersList();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to detach some users',
            life: 3000
        });
    }
};

const refreshUsersList = async () => {
    try {
        const response = await api.get(`/rombongan-belajar/${selectedRombongan.value.id}`);
        selectedRombonganUsers.value = response.data.users || [];
        filterUsers();
        // Update the rombongan count in the main list
        fetchRombonganBelajar();
    } catch (error) {
        console.error('Failed to refresh users list:', error);
    }
};

// Promotion functions
const openPromoteDialog = async () => {
    promoteTahunAngkatan.value = null;
    promotePreviewData.value = [];
    promoteDialogVisible.value = true;
    
    // Fetch available tahun angkatan
    loadingTahunAngkatan.value = true;
    try {
        const response = await api.get('/rombongan-belajar-tahun-angkatan');
        tahunAngkatanOptions.value = response.data;
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to fetch tahun angkatan',
            life: 3000
        });
    } finally {
        loadingTahunAngkatan.value = false;
    }
};

const loadPromotePreview = async () => {
    if (!promoteTahunAngkatan.value) {
        promotePreviewData.value = [];
        return;
    }

    // Get all rombongan with selected tahun angkatan from current list
    const rombonganList = rombonganBelajar.value.filter(
        r => r.tahun_angkatan == promoteTahunAngkatan.value
    );

    // Create preview data with next tingkat
    promotePreviewData.value = rombonganList.map(r => ({
        nama_rombel: r.nama_rombel,
        tingkat: r.tingkat,
        next_tingkat: tingkatMap[r.tingkat] || r.tingkat
    }));
};

const getNextTingkat = (currentTingkat) => {
    return tingkatMap[currentTingkat] || '-';
};

const confirmPromote = () => {
    const summary = promotePreviewData.value.length > 0 
        ? `${promotePreviewData.value.length} rombongan` 
        : 'rombongan';
        
    confirm.require({
        message: `Apakah Anda yakin ingin menaikkan semua rombongan belajar angkatan ${promoteTahunAngkatan.value} ke tingkat berikutnya? Total ${promotePreviewData.value.length} rombongan akan dinaikkan.`,
        header: 'Konfirmasi Naik Kelas',
        icon: 'pi pi-question-circle',
        accept: () => promoteRombongan(),
    });
};

const promoteRombongan = async () => {
    promoting.value = true;
    try {
        const response = await api.post(`/rombongan-belajar/promote/${promoteTahunAngkatan.value}`);
        
        toast.add({
            severity: 'success',
            summary: 'Berhasil',
            detail: response.data.message,
            life: 5000
        });

        promoteDialogVisible.value = false;
        promoteTahunAngkatan.value = null;
        promotePreviewData.value = [];
        fetchRombonganBelajar();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Gagal menaikkan kelas rombongan belajar',
            life: 3000
        });
    } finally {
        promoting.value = false;
    }
};

// Graduation functions
const openGraduateDialog = () => {
    graduateTingkat.value = null;
    graduateDialogVisible.value = true;
};

const confirmGraduate = () => {
    confirm.require({
        message: `PERINGATAN: Tindakan ini akan meluluskan semua siswa pada tingkat ${graduateTingkat.value}. Siswa akan di-detach dari rombongan dan semua rombongan belajar tingkat ${graduateTingkat.value} akan dihapus. Apakah Anda yakin?`,
        header: 'Konfirmasi Kelulusan',
        icon: 'pi pi-exclamation-triangle',
        acceptLabel: 'Ya, Luluskan',
        rejectLabel: 'Batal',
        acceptClass: 'p-button-danger',
        accept: () => graduateRombongan(),
    });
};

const graduateRombongan = async () => {
    graduating.value = true;
    try {
        const response = await api.post(`/rombongan-belajar/graduate/${graduateTingkat.value}`);
        
        toast.add({
            severity: 'success',
            summary: 'Berhasil',
            detail: response.data.message,
            life: 5000
        });

        graduateDialogVisible.value = false;
        fetchRombonganBelajar();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: error.response?.data?.message || 'Gagal meluluskan rombongan belajar',
            life: 3000
        });
    } finally {
        graduating.value = false;
    }
};

onMounted(() => {
    fetchRombonganBelajar();
});
</script>

<style scoped>
/* Mobile-first styles */
.rombongan-belajar {
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
    min-width: 200px;
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
.rombongan-dataview {
    display: block;
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

.rombongan-info {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.rombongan-main {
    flex: 1;
    min-width: 0;
}

.rombongan-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: white;
    margin: 0 0 0.5rem 0;
    line-height: 1.4;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.rombongan-details {
    display: flex;
    gap: 0.75rem;
    align-items: center;
    flex-wrap: wrap;
}

.detail-badge {
    background: rgba(16, 185, 129, 0.2);
    color: rgba(16, 185, 129, 0.9);
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
    font-weight: 600;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.detail-year {
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.875rem;
    font-weight: 500;
}

.rombongan-stats {
    display: flex;
    gap: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.875rem;
    font-weight: 500;
}

.stat-item i {
    color: rgba(16, 185, 129, 0.8);
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

/* Hide desktop table on mobile */
.desktop-table {
    display: none;
}

/* Tablet and Desktop: Show table, hide DataView */
@media (min-width: 768px) {
    .rombongan-dataview {
        display: none;
    }

    .desktop-table {
        display: table;
    }
    .rombongan-belajar {
        gap: 1.5rem;
    }

    .page-title {
        font-size: 1.75rem;
    }

    .filters {
        gap: 1rem;
    }

    .filter-group {
        min-width: 250px;
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

    .dataview-item {
        padding: 1.5rem;
        border-radius: 16px;
    }

    .rombongan-name {
        font-size: 1.25rem;
    }

    .rombongan-details {
        gap: 1rem;
    }

    .detail-badge {
        font-size: 1rem;
    }

    .detail-year {
        font-size: 1rem;
    }

    .stat-item {
        font-size: 1rem;
    }

    .item-actions {
        gap: 0.75rem;
        padding-top: 0.75rem;
    }
}

@media (min-width: 1024px) {
    .rombongan-belajar {
        gap: 2rem;
    }

    .page-title {
        font-size: 2rem;
    }

    .filter-group {
        min-width: 300px;
    }

    .dataview-item {
        padding: 1.75rem;
    }

    .rombongan-info {
        gap: 1.5rem;
    }

    .rombongan-name {
        font-size: 1.375rem;
    }

    .rombongan-stats {
        gap: 1.5rem;
    }

    .item-actions {
        gap: 0.875rem;
        padding-top: 1rem;
    }
}

/* Users Dialog Styles */
.users-dialog-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}

.search-section {
    position: relative;
    flex: 1;
    min-width: 200px;
}

.search-input {
    width: 100%;
    padding-right: 2.5rem;
}

.search-icon {
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.875rem;
}

.actions-section {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.users-table {
    margin-bottom: 1rem;
}

.selection-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background: rgba(16, 185, 129, 0.1);
    border: 1px solid rgba(16, 185, 129, 0.2);
    border-radius: 8px;
    font-size: 0.875rem;
    color: rgba(16, 185, 129, 0.9);
}

.selection-info i {
    font-size: 1rem;
}

/* Promotion and Graduation Dialog Styles */
.info-message,
.warning-message {
    display: flex;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 0.5rem;
}

.info-message {
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
    margin: 0;
    line-height: 1.5;
}

.warning-message {
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.3);
}

.warning-message i {
    color: rgba(245, 158, 11, 0.9);
    font-size: 1.25rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.warning-message p {
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.875rem;
    margin: 0;
    line-height: 1.5;
}

.warning-message strong {
    color: rgba(245, 158, 11, 0.95);
}

.promotion-preview {
    margin-top: 1rem;
}

.preview-card {
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 1rem;
}

.preview-card h4 {
    margin: 0 0 1rem 0;
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.9);
    font-weight: 600;
}

.preview-content {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.preview-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    text-align: center;
    flex: 1;
    min-width: 100px;
}

.preview-item .label {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.5px;
}

.preview-item .value {
    font-size: 1.5rem;
    font-weight: 700;
    padding: 0.5rem 1rem;
    border-radius: 8px;
}

.preview-item .value.current {
    background: rgba(59, 130, 246, 0.2);
    color: rgba(59, 130, 246, 0.95);
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.preview-item .value.next {
    background: rgba(16, 185, 129, 0.2);
    color: rgba(16, 185, 129, 0.95);
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.arrow-icon {
    color: rgba(255, 255, 255, 0.6);
    font-size: 1.5rem;
    flex-shrink: 0;
}

.preview-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    max-height: 300px;
    overflow-y: auto;
}

.preview-list-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 8px;
    gap: 1rem;
    flex-wrap: wrap;
}

.preview-list-item .rombel-name {
    flex: 1;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.95);
    font-size: 0.875rem;
}

.preview-list-item .tingkat-change {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.preview-list-item .tingkat {
    padding: 0.25rem 0.75rem;
    border-radius: 6px;
    font-size: 0.875rem;
    font-weight: 600;
}

.preview-list-item .tingkat.current {
    background: rgba(59, 130, 246, 0.2);
    color: rgba(59, 130, 246, 0.95);
    border: 1px solid rgba(59, 130, 246, 0.3);
}

.preview-list-item .tingkat.next {
    background: rgba(16, 185, 129, 0.2);
    color: rgba(16, 185, 129, 0.95);
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.preview-list-item .tingkat-change i {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.875rem;
}

.header-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

@media (min-width: 768px) {
    .info-message,
    .warning-message {
        gap: 1rem;
        padding: 1.25rem;
    }

    .info-message i,
    .warning-message i {
        font-size: 1.5rem;
    }

    .info-message p,
    .warning-message p {
        font-size: 1rem;
    }

    .preview-card {
        padding: 1.5rem;
    }

    .preview-card h4 {
        font-size: 1.125rem;
        margin-bottom: 1.5rem;
    }

    .preview-content {
        gap: 1.5rem;
    }

    .preview-item {
        min-width: 120px;
    }

    .preview-item .label {
        font-size: 0.875rem;
    }

    .preview-item .value {
        font-size: 1.75rem;
        padding: 0.75rem 1.25rem;
    }

    .arrow-icon {
        font-size: 2rem;
    }

    .header-actions {
        gap: 0.75rem;
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

    .users-dialog-header {
        flex-direction: row;
        align-items: center;
    }

    .search-section {
        flex: 0 1 300px;
    }
}
</style>
