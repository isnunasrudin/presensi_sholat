<template>
    <div class="prayer-records">
        <div class="page-header">
            <h1 class="page-title">Catatan Sholat Harian</h1>
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
                    <div class="filter-group">
                        <label>Tanggal</label>
                        <Calendar
                            v-model="selectedDate"
                            dateFormat="yy-mm-dd"
                            @date-select="fetchDailyRecap"
                            :showIcon="true"
                        />
                    </div>
                    <div v-if="authStore.isAdmin" class="filter-group">
                        <label>Rombel</label>
                        <Dropdown
                            v-model="filters.rombongan_belajar_id"
                            :options="[{ id: null, nama_rombel: 'Semua Rombel' }, ...rombels]"
                            optionLabel="nama_rombel"
                            optionValue="id"
                            placeholder="Semua Rombel"
                            @change="fetchDailyRecap"
                            filter
                        />
                    </div>
                    <div class="filter-group">
                        <label>&nbsp;</label>
                        <Button
                            label="Refresh"
                            icon="pi pi-refresh"
                            @click="fetchDailyRecap"
                            severity="secondary"
                        />
                    </div>
                </div>

                <!-- Daily Recap Table -->
                <DataTable
                    :value="dailyRecords"
                    :loading="loading"
                    responsiveLayout="scroll"
                    class="daily-recap-table mt-4"
                    :paginator="true"
                    :rows="20"
                    :totalRecords="dailyRecords.length"
                    dataKey="user_id"
                >
                    <Column field="name" header="Nama" sortable>
                        <template #body="slotProps">
                            <div class="user-info">
                                <div class="user-name">{{ slotProps.data.name }}</div>
                                <div class="user-email">{{ getUserEmail(slotProps.data.user_id) }}</div>
                            </div>
                        </template>
                    </Column>

                    <Column field="rombel" header="Rombel" sortable>
                        <template #body="slotProps">
                            <span class="rombel-badge">{{ slotProps.data.rombel }}</span>
                        </template>
                    </Column>

                    <Column field="dhuha_status" header="Sholat Dhuha" sortable>
                        <template #body="slotProps">
                            <div class="prayer-column">
                                <span :class="getPrayerBadgeClass(slotProps.data.dhuha_status)">
                                    {{ getPrayerBadgeText(slotProps.data.dhuha_status) }}
                                </span>
                                <small v-if="slotProps.data.dhuha_notes" class="notes-text">
                                    {{ slotProps.data.dhuha_notes }}
                                </small>
                            </div>
                        </template>
                    </Column>

                    <Column field="dhuhur_status" header="Sholat Dhuhur" sortable>
                        <template #body="slotProps">
                            <div class="prayer-column">
                                <span :class="getPrayerBadgeClass(slotProps.data.dhuhur_status)">
                                    {{ getPrayerBadgeText(slotProps.data.dhuhur_status) }}
                                </span>
                                <small v-if="slotProps.data.dhuhur_notes" class="notes-text">
                                    {{ slotProps.data.dhuhur_notes }}
                                </small>
                            </div>
                        </template>
                    </Column>

                    <Column header="Catatan">
                        <template #body="slotProps">
                            <div class="combined-notes">
                                <div v-if="slotProps.data.dhuha_notes" class="note-item">
                                    <strong>Dhuha:</strong> {{ slotProps.data.dhuha_notes }}
                                </div>
                                <div v-if="slotProps.data.dhuhur_notes" class="note-item">
                                    <strong>Dhuhur:</strong> {{ slotProps.data.dhuhur_notes }}
                                </div>
                                <span v-if="!slotProps.data.dhuha_notes && !slotProps.data.dhuhur_notes" class="no-notes">
                                    -
                                </span>
                            </div>
                        </template>
                    </Column>

                    <Column v-if="authStore.isAdmin" header="Hapus Data Rekap" style="min-width: 150px;">
                        <template #body="slotProps">
                            <div class="delete-actions">
                                <Button
                                    icon="pi pi-trash"
                                    label="Hapus Dhuha"
                                    @click="confirmDeleteDailyRecord(slotProps.data, 'dhuha')"
                                    severity="warning"
                                    size="small"
                                    text
                                    :disabled="!slotProps.data.dhuha_record_id"
                                    v-tooltip="'Hapus data sholat Dhuha'"
                                />
                                <Button
                                    icon="pi pi-trash"
                                    label="Hapus Dhuhur"
                                    @click="confirmDeleteDailyRecord(slotProps.data, 'dhuhur')"
                                    severity="danger"
                                    size="small"
                                    text
                                    :disabled="!slotProps.data.dhuhur_record_id"
                                    v-tooltip="'Hapus data sholat Dhuhur'"
                                />
                            </div>
                        </template>
                    </Column>
                </DataTable>

                <div v-if="!loading && dailyRecords.length === 0" class="empty-state">
                    <i class="pi pi-inbox" style="font-size: 3rem; color: #9ca3af;"></i>
                    <p>Tidak ada data untuk tanggal {{ formatDate(selectedDate) }}</p>
                </div>
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
import { ref, onMounted, computed } from 'vue';
import { useAuthStore } from '../stores/auth';
import { useToast } from 'primevue/usetoast';
import { useConfirm } from 'primevue/useconfirm';
import api from '../api/axios';

const authStore = useAuthStore();
const toast = useToast();
const confirm = useConfirm();

const dailyRecords = ref([]);
const users = ref([]);
const rombels = ref([]);
const loading = ref(false);
const saving = ref(false);
const dialogVisible = ref(false);
const dialogMode = ref('create');
const errors = ref({});
const selectedDate = ref(new Date());

const filters = ref({
    rombongan_belajar_id: null,
});

const prayerTypes = [
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

const fetchRombels = async () => {
    if (!authStore.isAdmin) return;

    try {
        const response = await api.get('/rombongan-belajar');
        rombels.value = response.data.data || response.data;
    } catch (error) {
        console.error('Failed to fetch rombels:', error);
    }
};

const fetchDailyRecap = async () => {
    loading.value = true;
    try {
        const params = {
            date: formatDateForAPI(selectedDate.value),
        };

        if (filters.value.rombongan_belajar_id) {
            params.rombongan_belajar_id = filters.value.rombongan_belajar_id;
        }

        const response = await api.get('/prayer-records-daily-recap', { params });
        dailyRecords.value = response.data;
    } catch (error) {
        console.error('Failed to fetch daily recap:', error);
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Gagal mengambil data harian',
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
            date: selectedDate.value,
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
        fetchDailyRecap();
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

const confirmDeleteDailyRecord = (record, prayerType) => {
    const recordId = prayerType === 'dhuha' ? record.dhuha_record_id : record.dhuhur_record_id;
    if (!recordId) return;

    const prayerName = prayerType === 'dhuha' ? 'Dhuha' : 'Dhuhur';

    confirm.require({
        message: `Apakah Anda yakin ingin menghapus data sholat ${prayerName} untuk ${record.name} pada ${formatDate(selectedDate)}?`,
        header: 'Konfirmasi Hapus',
        icon: 'pi pi-exclamation-triangle',
        accept: () => deleteDailyRecord(record.user_id, formatDateForAPI(selectedDate), prayerType),
        reject: () => {}
    });
};

const deleteDailyRecord = async (userId, date, prayerType) => {
    try {
        await api.delete('/prayer-records-delete-by-user-date-type', {
            data: {
                user_id: userId,
                date: date,
                prayer_type: prayerType,
            }
        });

        toast.add({
            severity: 'success',
            summary: 'Success',
            detail: `Data sholat ${prayerType === 'dhuha' ? 'Dhuha' : 'Dhuhur'} berhasil dihapus`,
            life: 3000
        });

        fetchDailyRecap();
    } catch (error) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Failed to delete record',
            life: 3000
        });
    }
};

const getUserEmail = (userId) => {
    const user = users.value.find(u => u.id === userId);
    return user?.email || '';
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

const getPrayerBadgeClass = (status) => {
    const classes = {
        sholat: 'prayer-badge success',
        tidak_sholat: 'prayer-badge danger',
        halangan: 'prayer-badge warning',
        null: 'prayer-badge empty',
    };
    return classes[status] || 'prayer-badge empty';
};

const getPrayerBadgeText = (status) => {
    const texts = {
        sholat: 'OK',
        tidak_sholat: '-',
        halangan: 'Halangan',
        null: '-',
    };
    return texts[status] || '-';
};

onMounted(() => {
    fetchDailyRecap();
    fetchUsers();
    fetchRombels();
});
</script>

<style scoped>
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

/* Daily Recap Table Styles */
.daily-recap-table {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(15px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 16px;
    overflow: hidden;
}

.user-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.user-name {
    font-weight: 600;
    color: white;
    font-size: 0.9rem;
}

.user-email {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
}

.rombel-badge {
    background: rgba(59, 130, 246, 0.2);
    color: #60a5fa;
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 500;
    white-space: nowrap;
}

.prayer-column {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.prayer-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-align: center;
    min-width: 50px;
    display: inline-block;
}

.prayer-badge.success {
    background: #dcfce7;
    color: #166534;
}

.prayer-badge.danger {
    background: #fee2e2;
    color: #991b1b;
}

.prayer-badge.warning {
    background: #fef3c7;
    color: #92400e;
}

.prayer-badge.empty {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.6);
}

.notes-text {
    font-size: 0.7rem;
    color: rgba(255, 255, 255, 0.8);
    font-style: italic;
    line-height: 1.3;
    max-width: 150px;
    word-break: break-word;
}

.combined-notes {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-width: 200px;
}

.note-item {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.9);
    line-height: 1.4;
    word-break: break-word;
}

.note-item strong {
    color: #60a5fa;
    display: block;
    margin-bottom: 0.125rem;
}

.no-notes {
    color: rgba(255, 255, 255, 0.5);
    font-style: italic;
    font-size: 0.8rem;
}

.delete-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
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

    .delete-actions {
        flex-direction: row;
        gap: 0.5rem;
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

/* Responsive table for mobile */
@media (max-width: 767px) {
    .daily-recap-table {
        font-size: 0.75rem;
    }

    .user-name {
        font-size: 0.8rem;
    }

    .rombel-badge {
        font-size: 0.65rem;
        padding: 0.2rem 0.5rem;
    }

    .prayer-badge {
        font-size: 0.65rem;
        min-width: 40px;
    }

    .notes-text,
    .note-item {
        font-size: 0.65rem;
    }

    .delete-actions .p-button {
        font-size: 0.65rem;
        padding: 0.25rem 0.5rem;
    }
}
</style>