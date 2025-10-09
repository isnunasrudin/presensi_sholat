<template>
    <div class="dashboard">
        <h1 class="page-title">Dasbor</h1>
        
        <div class="welcome-card">
            <Card>
                <template #content>
                    <h2>Selamat Datang, {{ authStore.user?.name }}! 👋</h2>
                    <p>Lacak kehadiran sholat Dhuhur dan Dhuha harian Anda</p>
                </template>
            </Card>
        </div>

        <div class="stats-grid">
            <Card class="stat-card">
                <template #content>
                    <div class="stat-content">
                        <i class="pi pi-check-circle stat-icon success"></i>
                        <div class="stat-info">
                            <h3>{{ statistics.sholat }}</h3>
                            <p>Sholat Selesai</p>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="stat-card">
                <template #content>
                    <div class="stat-content">
                        <i class="pi pi-times-circle stat-icon danger"></i>
                        <div class="stat-info">
                            <h3>{{ statistics.tidak_sholat }}</h3>
                            <p>Sholat Terlewat</p>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="stat-card">
                <template #content>
                    <div class="stat-content">
                        <i class="pi pi-info-circle stat-icon warning"></i>
                        <div class="stat-info">
                            <h3>{{ statistics.halangan }}</h3>
                            <p>Dibatalkan</p>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="stat-card">
                <template #content>
                    <div class="stat-content">
                        <i class="pi pi-chart-line stat-icon info"></i>
                        <div class="stat-info">
                            <h3>{{ statistics.percentage_sholat }}%</h3>
                            <p>Tingkat Penyelesaian</p>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <div class="quick-actions">
            <Card>
                <template #header>
                    <h3 class="card-title">Aksi Cepat</h3>
                </template>
                <template #content>
                    <div class="action-buttons">
                        <Button
                            label="Catat Sholat Hari Ini"
                            icon="pi pi-plus"
                            @click="$router.push('/prayer-records')"
                            size="large"
                        />
                        <Button
                            label="Lihat Semua Catatan"
                            icon="pi pi-list"
                            @click="$router.push('/prayer-records')"
                            severity="secondary"
                            size="large"
                        />
                    </div>
                </template>
            </Card>
        </div>

        <div class="recent-records" v-if="recentRecords.length > 0">
            <Card>
                <template #header>
                    <h3 class="card-title">Catatan Sholat Terbaru</h3>
                </template>
                <template #content>
                    <!-- Mobile DataView -->
                    <DataView :value="recentRecords" class="mobile-dataview">
                        <template #list="slotProps">
                            <div class="dataview-item">
                                <div class="item-row">
                                    <span class="item-label">Tanggal:</span>
                                    <span class="item-value">{{ formatDate(slotProps.data.date) }}</span>
                                </div>
                                <div class="item-row">
                                    <span class="item-label">Sholat:</span>
                                    <span class="item-value">{{ slotProps.data.prayer_type.charAt(0).toUpperCase() + slotProps.data.prayer_type.slice(1) }}</span>
                                </div>
                                <div class="item-row">
                                    <span class="item-label">Status:</span>
                                    <span :class="getStatusClass(slotProps.data.status)">
                                        {{ getStatusLabel(slotProps.data.status) }}
                                    </span>
                                </div>
                            </div>
                        </template>
                    </DataView>

                    <!-- Desktop Table View -->
                    <DataTable :value="recentRecords" responsiveLayout="scroll" class="desktop-table">
                        <Column field="date" header="Tanggal">
                            <template #body="slotProps">
                                {{ formatDate(slotProps.data.date) }}
                            </template>
                        </Column>
                        <Column field="prayer_type" header="Sholat">
                            <template #body="slotProps">
                                {{ slotProps.data.prayer_type.charAt(0).toUpperCase() + slotProps.data.prayer_type.slice(1) }}
                            </template>
                        </Column>
                        <Column field="status" header="Status">
                            <template #body="slotProps">
                                <span :class="getStatusClass(slotProps.data.status)">
                                    {{ getStatusLabel(slotProps.data.status) }}
                                </span>
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useAuthStore } from '../stores/auth';
import api from '../api/axios';

const authStore = useAuthStore();

const statistics = ref({
    total: 0,
    sholat: 0,
    tidak_sholat: 0,
    halangan: 0,
    percentage_sholat: 0,
});

const recentRecords = ref([]);

const fetchStatistics = async () => {
    try {
        const response = await api.get('/prayer-records/statistics/summary');
        statistics.value = response.data;
    } catch (error) {
        console.error('Error fetching statistics:', error);
    }
};

const fetchRecentRecords = async () => {
    try {
        const response = await api.get('/prayer-records', {
            params: { per_page: 5 }
        });
        recentRecords.value = response.data.data || [];
    } catch (error) {
        console.error('Error fetching recent records:', error);
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
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
    fetchStatistics();
    fetchRecentRecords();
});
</script>

<style scoped>
/* Mobile-first styles */
.dashboard {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.page-title {
    font-size: 1.5rem;
    color: white;
    margin: 0;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.welcome-card h2 {
    margin: 0 0 0.5rem;
    color: white;
    font-size: 1.25rem;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.welcome-card p {
    margin: 0;
    color: rgba(255, 255, 255, 0.9);
    font-size: 0.875rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.12);
}

.stat-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.75rem;
    padding: 0.5rem;
}

.stat-icon {
    font-size: 2rem;
}

.stat-icon.success {
    color: #22c55e;
}

.stat-icon.danger {
    color: #ef4444;
}

.stat-icon.warning {
    color: #f59e0b;
}

.stat-icon.info {
    color: #3b82f6;
}

.stat-info h3 {
    font-size: 1.5rem;
    margin: 0;
    color: white;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.stat-info p {
    margin: 0.25rem 0 0;
    color: rgba(255, 255, 255, 0.85);
    font-size: 0.75rem;
}

.card-title {
    padding: 1rem;
    margin: 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
    font-size: 1rem;
    color: white;
}

.action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-block;
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

/* Mobile DataView */
.mobile-dataview {
    display: block;
}

.dataview-item {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
}

.dataview-item:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}

.item-row:not(:last-child) {
    border-bottom: 1px solid #f3f4f6;
}

.item-label {
    font-weight: 600;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
}

.item-value {
    font-weight: 500;
    color: white;
    text-align: right;
}

.desktop-table {
    display: none;
}

/* Tablet styles (768px and up) */
@media (min-width: 768px) {
    .mobile-dataview {
        display: none;
    }

    .desktop-table {
        display: table;
    }
}

/* More tablet styles (768px and up) */
@media (min-width: 768px) {
    .dashboard {
        gap: 1.5rem;
    }

    .page-title {
        font-size: 1.75rem;
    }

    .welcome-card h2 {
        font-size: 1.5rem;
    }

    .welcome-card p {
        font-size: 1rem;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }

    .stat-content {
        flex-direction: row;
        text-align: left;
        gap: 1rem;
        padding: 0;
    }

    .stat-icon {
        font-size: 2.5rem;
    }

    .stat-info h3 {
        font-size: 1.75rem;
    }

    .stat-info p {
        font-size: 0.875rem;
    }

    .card-title {
        padding: 1rem 1.25rem;
        font-size: 1.125rem;
    }

    .action-buttons {
        flex-direction: row;
        gap: 1rem;
    }

    .status-badge {
        font-size: 0.875rem;
    }
}

/* Desktop styles (1024px and up) */
@media (min-width: 1024px) {
    .dashboard {
        gap: 2rem;
    }

    .page-title {
        font-size: 2rem;
    }

    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }

    .stat-content {
        gap: 1.5rem;
    }

    .stat-icon {
        font-size: 3rem;
    }

    .stat-info h3 {
        font-size: 2rem;
    }

    .card-title {
        padding: 1rem 1.5rem;
    }
}
</style>