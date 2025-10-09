<template>
    <div class="prayer-records">
        <div class="page-header">
            <h1 class="page-title">Catatan Sholat</h1>
            <Button
                v-if="authStore.isAdmin"
                label="Tambah Catatan"
                icon="pi pi-plus"
                @click="openDialog()"
            />
        </div>

        <Card>
            <template #content>
                <div class="filters">
                    <div v-if="authStore.isAdmin" class="filter-group">
                        <label>Pengguna</label>
                        <Dropdown
                            v-model="filters.user_id"
                            :options="[{ id: null, name: 'Semua Pengguna' }, ...users]"
                            optionLabel="name"
                            optionValue="id"
                            placeholder="Semua Pengguna"
                            @change="fetchRecords"
                            filter
                        />
                    </div>
                    <div class="filter-group">
                        <label>Jenis Sholat</label>
                        <Dropdown
                            v-model="filters.prayer_type"
                            :options="prayerTypes"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Semua"
                            @change="fetchRecords"
                        />
                    </div>
                    <div class="filter-group">
                        <label>Tanggal Mulai</label>
                        <Calendar
                            v-model="filters.start_date"
                            dateFormat="yy-mm-dd"
                            @date-select="fetchRecords"
                        />
                    </div>
                    <div class="filter-group">
                        <label>Tanggal Akhir</label>
                        <Calendar
                            v-model="filters.end_date"
                            dateFormat="yy-mm-dd"
                            @date-select="fetchRecords"
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
                <DataView :value="records" :loading="loading" class="prayer-dataview">
                    <template #list="slotProps">
                        <div class="dataview-item" v-for="record in slotProps.items" :key="record.id">
                            <div class="record-header">
                                <div class="prayer-icon">
                                    <i class="pi pi-sun"></i>
                                </div>
                                <div class="record-main-info">
                                    <h3 class="prayer-type">{{ getPrayerTypeLabel(record.prayer_type) }}</h3>
                                    <p class="record-date">
                                        <i class="pi pi-calendar"></i>
                                        {{ formatDate(record.date) }}
                                    </p>
                                </div>
                                <span :class="getStatusClass(record.status)">
                                    {{ getStatusLabel(record.status) }}
                                </span>
                            </div>
                            
                            <div class="record-details">
                                <div v-if="authStore.isAdmin" class="detail-item">
                                    <i class="pi pi-user detail-icon"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Pengguna</span>
                                        <span class="detail-value">{{ record.user?.name }}</span>
                                    </div>
                                </div>
                                <div v-if="record.notes" class="detail-item notes-item">
                                    <i class="pi pi-comment detail-icon"></i>
                                    <div class="detail-content">
                                        <span class="detail-label">Catatan</span>
                                        <span class="detail-value">{{ record.notes }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div v-if="authStore.isAdmin" class="item-actions">
                                <Button
                                    icon="pi pi-pencil"
                                    label="Edit"
                                    @click="openDialog(record)"
                                    severity="info"
                                    size="small"
                                    outlined
                                />
                                <Button
                                    icon="pi pi-trash"
                                    label="Hapus"
                                    @click="confirmDelete(record)"
                                    severity="danger"
                                    size="small"
                                    outlined
                                />
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <div class="empty-state">
                            <i class="pi pi-inbox" style="font-size: 3rem; color: #9ca3af;"></i>
                            <p>Tidak ada catatan sholat ditemukan</p>
                        </div>
                    </template>
                </DataView>

                <!-- Desktop Table View -->
                <DataTable
                    :value="records"
                    :loading="loading"
                    responsiveLayout="scroll"
                    :rows="pagination.per_page"
                    class="mt-4 desktop-table w-full"
                >
                    <Column v-if="authStore.isAdmin" field="user.name" header="Pengguna"></Column>
                    <Column field="date" header="Tanggal">
                        <template #body="slotProps">
                            {{ formatDate(slotProps.data.date) }}
                        </template>
                    </Column>
                    <Column field="prayer_type" header="Jenis Sholat">
                        <template #body="slotProps">
                            {{ getPrayerTypeLabel(slotProps.data.prayer_type) }}
                        </template>
                    </Column>
                    <Column field="status" header="Status">
                        <template #body="slotProps">
                            <span :class="getStatusClass(slotProps.data.status)">
                                {{ getStatusLabel(slotProps.data.status) }}
                            </span>
                        </template>
                    </Column>
                    <Column field="notes" header="Catatan">
                        <template #body="slotProps">
                            {{ slotProps.data.notes || '-' }}
                        </template>
                    </Column>
                    <Column v-if="authStore.isAdmin" header="Aksi">
                        <template #body="slotProps">
                            <div class="action-buttons">
                                <Button
                                    icon="pi pi-pencil"
                                    @click="openDialog(slotProps.data)"
                                    severity="info"
                                    text
                                    rounded
                                />
                                <Button
                                    icon="pi pi-trash"
                                    @click="confirmDelete(slotProps.data)"
                                    severity="danger"
                                    text
                                    rounded
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
            :header="dialogMode === 'create' ? 'Tambah Catatan Sholat' : 'Edit Catatan Sholat'"
            :modal="true"
            :style="{ width: '500px' }"
        >
            <form @submit.prevent="saveRecord" class="dialog-form">
                <div v-if="authStore.isAdmin" class="field">
                    <label for="user_id">Pengguna *</label>
                    <Dropdown
                        id="user_id"
                        v-model="formData.user_id"
                        :options="users"
                        optionLabel="name"
                        optionValue="id"
                        placeholder="Pilih pengguna"
                        :class="{ 'p-invalid': errors.user_id }"
                        filter
                    />
                    <small v-if="errors.user_id" class="p-error">{{ errors.user_id }}</small>
                </div>

                <div class="field">
                    <label for="prayer_type">Jenis Sholat *</label>
                    <Dropdown
                        id="prayer_type"
                        v-model="formData.prayer_type"
                        :options="prayerTypes.filter(p => p.value)"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Pilih jenis sholat"
                        :class="{ 'p-invalid': errors.prayer_type }"
                    />
                    <small v-if="errors.prayer_type" class="p-error">{{ errors.prayer_type }}</small>
                </div>

                <div class="field">
                    <label for="date">Tanggal *</label>
                    <Calendar
                        id="date"
                        v-model="formData.date"
                        dateFormat="yy-mm-dd"
                        :class="{ 'p-invalid': errors.date }"
                    />
                    <small v-if="errors.date" class="p-error">{{ errors.date }}</small>
                </div>

                <div class="field">
                    <label for="status">Status *</label>
                    <Dropdown
                        id="status"
                        v-model="formData.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Pilih status"
                        :class="{ 'p-invalid': errors.status }"
                    />
                    <small v-if="errors.status" class="p-error">{{ errors.status }}</small>
                </div>

                <div class="field">
                    <label for="notes">Catatan</label>
                    <Textarea
                        id="notes"
                        v-model="formData.notes"
                        rows="3"
                        placeholder="Tambahkan catatan..."
                    />
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
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import api from '../api/axios';

const authStore = useAuthStore();
const toast = useToast();
const confirm = useConfirm();

const records = ref([]);
const users = ref([]);
const loading = ref(false);
const saving = ref(false);
const dialogVisible = ref(false);
const dialogMode = ref('create');
const errors = ref({});

const filters = ref({
    user_id: null,
    prayer_type: null,
    start_date: null,
    end_date: null,
});

const pagination = ref({
    current_page: 1,
    per_page: 15,
    total: 0,
});

const prayerTypes = [
    { label: 'All', value: null },
    { label: 'Dhuhur', value: 'dhuhur' },
    { label: 'Dhuha', value: 'dhuha' },
];

const statusOptions = [
    { label: 'Sholat', value: 'sholat' },
    { label: 'Tidak Sholat', value: 'tidak_sholat' },
    { label: 'Halangan', value: 'halangan' },
];

const formData = ref({
    id: null,
    user_id: null,
    prayer_type: null,
    date: new Date(),
    status: null,
    notes: '',
});

const fetchUsers = async () => {
    if (!authStore.isAdmin) return;
    
    try {
        const response = await api.get('/users');
        users.value = response.data.data || response.data;
    } catch (error) {
        console.error('Failed to fetch users:', error);
    }
};

const fetchRecords = async () => {
    loading.value = true;
    try {
        const params = {
            page: pagination.value.current_page,
            ...filters.value,
        };

        if (params.start_date) {
            params.start_date = formatDateForAPI(params.start_date);
        }
        if (params.end_date) {
            params.end_date = formatDateForAPI(params.end_date);
        }

        const response = await api.get('/prayer-records', { params });
        records.value = response.data.data;
        pagination.value = {
            current_page: response.data.current_page,
            per_page: response.data.per_page,
            total: response.data.total,
        };
    } catch (error) {
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: 'Failed to fetch records', 
            life: 3000 
        });
    } finally {
        loading.value = false;
    }
};

const openDialog = (record = null) => {
    errors.value = {};
    if (record) {
        dialogMode.value = 'edit';
        formData.value = {
            id: record.id,
            user_id: record.user_id,
            prayer_type: record.prayer_type,
            date: new Date(record.date),
            status: record.status,
            notes: record.notes || '',
        };
    } else {
        dialogMode.value = 'create';
        formData.value = {
            id: null,
            user_id: authStore.isAdmin ? null : authStore.user.id,
            prayer_type: null,
            date: new Date(),
            status: null,
            notes: '',
        };
    }
    dialogVisible.value = true;
};

const saveRecord = async () => {
    errors.value = {};
    saving.value = true;

    try {
        const data = {
            prayer_type: formData.value.prayer_type,
            date: formatDateForAPI(formData.value.date),
            status: formData.value.status,
            notes: formData.value.notes,
        };

        if (authStore.isAdmin) {
            data.user_id = formData.value.user_id;
        }

        if (dialogMode.value === 'edit') {
            await api.put(`/prayer-records/${formData.value.id}`, data);
            toast.add({ 
                severity: 'success', 
                summary: 'Success', 
                detail: 'Record updated successfully', 
                life: 3000 
            });
        } else {
            await api.post('/prayer-records', data);
            toast.add({ 
                severity: 'success', 
                summary: 'Success', 
                detail: 'Record created successfully', 
                life: 3000 
            });
        }

        dialogVisible.value = false;
        fetchRecords();
    } catch (error) {
        if (error.response?.data?.errors) {
            errors.value = error.response.data.errors;
        } else {
            toast.add({ 
                severity: 'error', 
                summary: 'Error', 
                detail: error.response?.data?.message || 'Failed to save record', 
                life: 3000 
            });
        }
    } finally {
        saving.value = false;
    }
};

const confirmDelete = (record) => {
    confirm.require({
        message: 'Are you sure you want to delete this record?',
        header: 'Confirm Delete',
        icon: 'pi pi-exclamation-triangle',
        accept: () => deleteRecord(record.id),
    });
};

const deleteRecord = async (id) => {
    try {
        await api.delete(`/prayer-records/${id}`);
        toast.add({ 
            severity: 'success', 
            summary: 'Success', 
            detail: 'Record deleted successfully', 
            life: 3000 
        });
        fetchRecords();
    } catch (error) {
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: 'Failed to delete record', 
            life: 3000 
        });
    }
};

const clearFilters = () => {
    filters.value = {
        user_id: null,
        prayer_type: null,
        start_date: null,
        end_date: null,
    };
    fetchRecords();
};

const onPageChange = (event) => {
    pagination.value.current_page = event.page + 1;
    fetchRecords();
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatDateForAPI = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getPrayerTypeLabel = (type) => {
    return type.charAt(0).toUpperCase() + type.slice(1);
};

const getStatusLabel = (status) => {
    const labels = {
        sholat: 'Sholat',
        tidak_sholat: 'Tidak Sholat',
        halangan: 'Halangan',
    };
    return labels[status] || status;
};

const getStatusClass = (status) => {
    const classes = {
        sholat: 'status-badge success',
        tidak_sholat: 'status-badge danger',
        halangan: 'status-badge warning',
    };
    return classes[status] || 'status-badge';
};

onMounted(() => {
    fetchRecords();
    fetchUsers();
});
</script>

<style scoped>
/* Mobile-first styles */
.prayer-records {
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
    justify-content: center;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.status-badge.success {
    background: #dcfce7;
    color: #166534;
}

.status-badge.danger {
    background: #fee2e2;
    color: #991b1b;
}

.status-badge.warning {
    background: #fef3c7;
    color: #92400e;
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

/* Prayer DataView Styles */
.prayer-dataview {
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

.record-header {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: flex-start;
}

.prayer-icon {
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
}

.prayer-icon i {
    font-size: 1.5rem;
    color: white;
}

.record-main-info {
    flex: 1;
    min-width: 0;
}

.prayer-type {
    font-size: 1.125rem;
    font-weight: 600;
    color: white;
    margin: 0 0 0.5rem 0;
    line-height: 1.4;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.record-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

.record-date i {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.6);
}

.record-details {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 12px;
    padding: 0.875rem;
    margin-bottom: 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.detail-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.detail-icon {
    font-size: 1rem;
    color: rgba(16, 185, 129, 0.9);
    margin-top: 0.25rem;
}

.detail-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
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
    word-break: break-word;
}

.notes-item .detail-value {
    font-weight: 400;
    line-height: 1.5;
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
    .prayer-dataview {
        display: none;
    }

    /* Show desktop table */
    .desktop-table {
        display: table;
    }
}

/* Tablet styles (768px and up) */
@media (min-width: 768px) {
    .prayer-records {
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

    .status-badge {
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
}

/* Desktop styles (1024px and up) */
@media (min-width: 1024px) {
    .prayer-records {
        gap: 2rem;
    }

    .page-title {
        font-size: 2rem;
    }

    .filter-group {
        min-width: 200px;
    }
}

</style>