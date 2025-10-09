<template>
    <div class="user-recap">
        <div class="page-header">
            <h1 class="page-title">Rekap Pengguna</h1>
        </div>

        <Card>
            <template #content>
                <div class="filters">
                    <div class="filter-group">
                        <label>Jenis Sholat</label>
                        <Dropdown
                            v-model="filters.prayer_type"
                            :options="prayerTypes"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Semua"
                            @change="fetchRecap"
                        />
                    </div>
                    <div class="filter-group">
                        <label>Tanggal Mulai</label>
                        <Calendar
                            v-model="filters.start_date"
                            dateFormat="yy-mm-dd"
                            @date-select="fetchRecap"
                        />
                    </div>
                    <div class="filter-group">
                        <label>Tanggal Akhir</label>
                        <Calendar
                            v-model="filters.end_date"
                            dateFormat="yy-mm-dd"
                            @date-select="fetchRecap"
                        />
                    </div>
                    <Button
                        label="Hapus Filter"
                        icon="pi pi-filter-slash"
                        @click="clearFilters"
                        severity="secondary"
                    />
                </div>

                <!-- Mobile Card View -->
                <DataView :value="recapData" :loading="loading" class="recap-dataview">
                    <template #list="slotProps">
                        <div class="dataview-item" v-for="item in slotProps.items" :key="item.user_id">
                            <div class="user-header-section">
                                <div class="user-avatar">
                                    <i class="pi pi-user"></i>
                                </div>
                                <div class="user-info-section">
                                    <h3 class="user-name">{{ item.user_name }}</h3>
                                    <p class="user-email">
                                        <i class="pi pi-envelope"></i>
                                        {{ item.user_email }}
                                    </p>
                                </div>
                            </div>
                            
                            <div class="stats-section">
                                <div class="stat-card">
                                    <div class="stat-icon total">
                                        <i class="pi pi-chart-line"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-label">Total Catatan</span>
                                        <span class="stat-number">{{ item.total_records }}</span>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon success">
                                        <i class="pi pi-check-circle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-label">Sholat</span>
                                        <span class="stat-number success-text">{{ item.sholat_count }}</span>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon danger">
                                        <i class="pi pi-times-circle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-label">Tidak Sholat</span>
                                        <span class="stat-number danger-text">{{ item.tidak_sholat_count }}</span>
                                    </div>
                                </div>
                                
                                <div class="stat-card">
                                    <div class="stat-icon warning">
                                        <i class="pi pi-exclamation-circle"></i>
                                    </div>
                                    <div class="stat-content">
                                        <span class="stat-label">Halangan</span>
                                        <span class="stat-number warning-text">{{ item.halangan_count }}</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="progress-section">
                                <div class="progress-header">
                                    <span class="progress-title">Tingkat Penyelesaian</span>
                                    <span class="progress-percentage">{{ item.percentage_sholat }}%</span>
                                </div>
                                <ProgressBar
                                    :value="item.percentage_sholat"
                                    :showValue="false"
                                    style="height: 0.875rem; border-radius: 0.5rem;"
                                    :class="getProgressBarClass(item.percentage_sholat)"
                                />
                            </div>
                            
                            <Button
                                icon="pi pi-calendar"
                                label="Lihat Detail"
                                @click="openCalendar(item)"
                                severity="info"
                                size="small"
                                class="w-full"
                                outlined
                            />
                        </div>
                    </template>
                    <template #empty>
                        <div class="empty-state">
                            <i class="pi pi-chart-bar" style="font-size: 3rem; color: #9ca3af;"></i>
                            <p>Tidak ada data tersedia. Silakan sesuaikan filter Anda.</p>
                        </div>
                    </template>
                </DataView>

                <!-- Desktop Table View -->
                <DataTable
                    :value="recapData"
                    :loading="loading"
                    responsiveLayout="scroll"
                    class="mt-4 desktop-only w-full"
                    :sortField="'percentage_sholat'"
                    :sortOrder="-1"
                >
                    <Column field="user_name" header="Nama" sortable></Column>
                    <Column field="user_email" header="Email" sortable></Column>
                    <Column field="total_records" header="Total Catatan" sortable>
                        <template #body="slotProps">
                            <span class="font-semibold">{{ slotProps.data.total_records }}</span>
                        </template>
                    </Column>
                    <Column field="sholat_count" header="Sholat" sortable>
                        <template #body="slotProps">
                            <span class="text-green-600 font-semibold">{{ slotProps.data.sholat_count }}</span>
                        </template>
                    </Column>
                    <Column field="tidak_sholat_count" header="Tidak Sholat" sortable>
                        <template #body="slotProps">
                            <span class="text-red-600 font-semibold">{{ slotProps.data.tidak_sholat_count }}</span>
                        </template>
                    </Column>
                    <Column field="halangan_count" header="Halangan" sortable>
                        <template #body="slotProps">
                            <span class="text-orange-600 font-semibold">{{ slotProps.data.halangan_count }}</span>
                        </template>
                    </Column>
                    <Column field="percentage_sholat" header="Persentase" sortable>
                        <template #body="slotProps">
                            <div class="flex items-center gap-2">
                                <ProgressBar
                                    :value="slotProps.data.percentage_sholat"
                                    :showValue="false"
                                    style="height: 0.5rem; width: 100px;"
                                    :class="getProgressBarClass(slotProps.data.percentage_sholat)"
                                />
                                <span class="font-semibold">{{ slotProps.data.percentage_sholat }}%</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Aksi">
                        <template #body="slotProps">
                            <Button
                                icon="pi pi-calendar"
                                label="Lihat Detail"
                                @click="openCalendar(slotProps.data)"
                                severity="info"
                                size="small"
                            />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>

        <UserMonthlyCalendar
            v-model:visible="calendarVisible"
            :userId="selectedUser?.user_id"
            :userName="selectedUser?.user_name"
        />
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '../api/axios';
import UserMonthlyCalendar from '../components/UserMonthlyCalendar.vue';
import DataView from 'primevue/dataview';

const toast = useToast();

const recapData = ref([]);
const loading = ref(false);
const calendarVisible = ref(false);
const selectedUser = ref(null);

const filters = ref({
    prayer_type: null,
    start_date: null,
    end_date: null,
});

const prayerTypes = [
    { label: 'All', value: null },
    { label: 'Dhuhur', value: 'dhuhur' },
    { label: 'Dhuha', value: 'dhuha' },
];

const fetchRecap = async () => {
    loading.value = true;
    try {
        const params = { ...filters.value };

        if (params.start_date) {
            params.start_date = formatDateForAPI(params.start_date);
        }
        if (params.end_date) {
            params.end_date = formatDateForAPI(params.end_date);
        }

        const response = await api.get('/prayer-records-user-recap', { params });
        recapData.value = response.data;
    } catch (error) {
        toast.add({ 
            severity: 'error', 
            summary: 'Error', 
            detail: 'Failed to fetch recap data', 
            life: 3000 
        });
    } finally {
        loading.value = false;
    }
};

const clearFilters = () => {
    filters.value = {
        prayer_type: null,
        start_date: null,
        end_date: null,
    };
    fetchRecap();
};

const formatDateForAPI = (date) => {
    if (!date) return null;
    const d = new Date(date);
    const year = d.getFullYear();
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
};

const getProgressBarClass = (percentage) => {
    if (percentage >= 80) return 'progress-excellent';
    if (percentage >= 60) return 'progress-good';
    if (percentage >= 40) return 'progress-average';
    return 'progress-poor';
};

const openCalendar = (userData) => {
    selectedUser.value = userData;
    calendarVisible.value = true;
};

onMounted(() => {
    fetchRecap();
});
</script>

<style scoped>
/* Mobile-first styles */
.user-recap {
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
    color: #fff;
    margin: 0;
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
    color: #333;
    font-size: 0.8rem;
}

.mt-4 {
    margin-top: 1.5rem;
}

.w-full {
    width: 100%;
}

.font-semibold {
    font-weight: 600;
}

.text-green-600 {
    color: #16a34a;
}

.text-red-600 {
    color: #dc2626;
}

.text-orange-600 {
    color: #ea580c;
}

.text-gray-500 {
    color: #6b7280;
}

.flex {
    display: flex;
}

.items-center {
    align-items: center;
}

.gap-2 {
    gap: 0.5rem;
}

.text-center {
    text-align: center;
}

.py-8 {
    padding-top: 2rem;
    padding-bottom: 2rem;
}

/* Recap DataView Styles */
.recap-dataview {
    display: block;
}

/* Hide desktop table on mobile */
.desktop-only {
    display: none;
}

.dataview-item {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.25rem;
    margin-bottom: 1rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.dataview-item:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    transform: translateY(-2px);
    border-color: #cbd5e1;
}

.user-header-section {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    align-items: flex-start;
}

.user-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 4px 6px rgba(102, 126, 234, 0.3);
}

.user-avatar i {
    font-size: 1.5rem;
    color: white;
}

.user-info-section {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin: 0 0 0.5rem 0;
    line-height: 1.4;
}

.user-email {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
    word-break: break-all;
}

.user-email i {
    font-size: 0.75rem;
    color: #9ca3af;
}

.stats-section {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.stat-card {
    background: #f9fafb;
    border-radius: 8px;
    padding: 0.875rem;
    display: flex;
    gap: 0.75rem;
    align-items: center;
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.stat-icon i {
    font-size: 1.125rem;
    color: white;
}

.stat-icon.total {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
}

.stat-icon.success {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
}

.stat-icon.danger {
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
}

.stat-icon.warning {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
}

.stat-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    flex: 1;
    min-width: 0;
}

.stat-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.stat-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.2;
}

.stat-number.success-text {
    color: #16a34a;
}

.stat-number.danger-text {
    color: #dc2626;
}

.stat-number.warning-text {
    color: #ea580c;
}

.progress-section {
    background: #f9fafb;
    border-radius: 8px;
    padding: 0.875rem;
    margin-bottom: 1rem;
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.625rem;
}

.progress-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #1f2937;
}

.progress-percentage {
    font-size: 1rem;
    font-weight: 700;
    color: #6366f1;
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
    color: #6b7280;
    margin: 0;
}

:deep(.progress-excellent .p-progressbar-value) {
    background: #16a34a;
}

:deep(.progress-good .p-progressbar-value) {
    background: #84cc16;
}

:deep(.progress-average .p-progressbar-value) {
    background: #f59e0b;
}

:deep(.progress-poor .p-progressbar-value) {
    background: #dc2626;
}

/* Tablet and Desktop: Show table, hide DataView */
@media (min-width: 768px) {
    /* Hide mobile DataView */
    .recap-dataview {
        display: none;
    }

    /* Show desktop table */
    .desktop-only {
        display: table;
    }

    .user-recap {
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
}

/* Desktop styles (1024px and up) */
@media (min-width: 1024px) {
    .user-recap {
        gap: 2rem;
    }

    .page-title {
        font-size: 2rem;
    }

    .filter-group {
        min-width: 200px;
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
</style>