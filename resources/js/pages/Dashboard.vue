<template>
    <div class="dashboard">
        <h1 class="page-title">{{ isUser ? 'Dashboard Sholat' : 'Dashboard Manajemen Sholat' }}</h1>

        <div class="welcome-card">
            <Card>
                <template #content>
                    <div class="welcome-content">
                        <div class="welcome-text">
                            <h2>{{ isUser ? `Selamat Datang, ${authStore.user?.name}! 👋` : 'Dashboard Manajemen Sholat 📊' }}</h2>
                            <p>{{ isUser ? 'Lacak kehadiran sholat Dhuhur dan Dhuha harian Anda' : 'Pantau statistik kehadiran sholat seluruh siswa' }}</p>
                        </div>
                        <div class="welcome-date">
                            <i class="pi pi-calendar"></i>
                            <span>{{ currentDate }}</span>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- User Dashboard -->
        <div v-if="isUser" class="user-dashboard">
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
                                <p>Berhalangan</p>
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
                                <p>Tingkat Kehadiran</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>

            <div class="today-status">
                <Card>
                    <template #header>
                        <h3 class="card-title">Status Sholat Hari Ini</h3>
                    </template>
                    <template #content>
                        <div class="today-grid">
                            <div class="today-item">
                                <div class="prayer-header">
                                    <i class="pi pi-sun prayer-icon dhuha"></i>
                                    <h4>Sholat Dhuha</h4>
                                </div>
                                <div v-if="todayDhuha" class="prayer-status">
                                    <span :class="getPrayerBadgeClass(todayDhuha.status)">
                                        {{ getPrayerBadgeText(todayDhuha.status) }}
                                    </span>
                                    <small v-if="todayDhuha.notes" class="prayer-notes">
                                        {{ todayDhuha.notes }}
                                    </small>
                                </div>
                                <div v-else class="prayer-empty">
                                    <span class="empty-badge">Belum Dicatat</span>
                                    <Button
                                        label="Catat Sekarang"
                                        icon="pi pi-plus"
                                        size="small"
                                        @click="quickAddRecord('dhuha')"
                                        class="mt-2"
                                    />
                                </div>
                            </div>

                            <div class="today-item">
                                <div class="prayer-header">
                                    <i class="pi pi-sun prayer-icon dhuhur"></i>
                                    <h4>Sholat Dhuhur</h4>
                                </div>
                                <div v-if="todayDhuhur" class="prayer-status">
                                    <span :class="getPrayerBadgeClass(todayDhuhur.status)">
                                        {{ getPrayerBadgeText(todayDhuhur.status) }}
                                    </span>
                                    <small v-if="todayDhuhur.notes" class="prayer-notes">
                                        {{ todayDhuhur.notes }}
                                    </small>
                                </div>
                                <div v-else class="prayer-empty">
                                    <span class="empty-badge">Belum Dicatat</span>
                                    <Button
                                        label="Catat Sekarang"
                                        icon="pi pi-plus"
                                        size="small"
                                        @click="quickAddRecord('dhuhur')"
                                        class="mt-2"
                                    />
                                </div>
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
                                label="Riwayat Sholat"
                                icon="pi pi-list"
                                @click="$router.push('/prayer-records')"
                                severity="secondary"
                                size="large"
                            />
                            <Button
                                label="Scan NFC"
                                icon="pi pi-mobile"
                                @click="$router.push('/nfc-scanner')"
                                severity="info"
                                size="large"
                                v-if="!isAdmin"
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Admin Dashboard -->
        <div v-else class="admin-dashboard">
            <div class="admin-stats">
                <div class="stats-row">
                    <Card class="stat-card wide">
                        <template #content>
                            <div class="stat-content">
                                <i class="pi pi-users stat-icon info"></i>
                                <div class="stat-info">
                                    <h3>{{ adminStats.total_users }}</h3>
                                    <p>Total Pengguna</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="stat-card wide">
                        <template #content>
                            <div class="stat-content">
                                <i class="pi pi-building stat-icon info"></i>
                                <div class="stat-info">
                                    <h3>{{ adminStats.total_rombels }}</h3>
                                    <p>Total Rombel</p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>

                <div class="stats-grid !mb-5">
                    <Card class="stat-card">
                        <template #content>
                            <div class="stat-content">
                                <i class="pi pi-check-circle stat-icon success"></i>
                                <div class="stat-info">
                                    <h3>{{ adminStats.sholat }}</h3>
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
                                    <h3>{{ adminStats.tidak_sholat }}</h3>
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
                                    <h3>{{ adminStats.halangan }}</h3>
                                    <p>Berhalangan</p>
                                </div>
                            </div>
                        </template>
                    </Card>

                    <Card class="stat-card">
                        <template #content>
                            <div class="stat-content">
                                <i class="pi pi-chart-line stat-icon info"></i>
                                <div class="stat-info">
                                    <h3>{{ adminStats.percentage_sholat }}%</h3>
                                    <p>Tingkat Kehadiran</p>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <div class="admin-actions">
                <Card>
                    <template #header>
                        <h3 class="card-title">Manajemen Sholat</h3>
                    </template>
                    <template #content>
                        <div class="action-grid">
                            <div class="action-item" @click="$router.push('/prayer-records')">
                                <i class="pi pi-list action-icon"></i>
                                <h4>Catatan Sholat</h4>
                                <p>Kelola catatan sholat harian</p>
                            </div>
                            <div class="action-item" @click="$router.push('/prayer-records?date=' + formatDateForAPI(new Date()))">
                                <i class="pi pi-calendar-day action-icon"></i>
                                <h4>Data Hari Ini</h4>
                                <p>Lihat catatan sholat hari ini</p>
                            </div>
                            <div class="action-item" @click="$router.push('/nfc-scanner')">
                                <i class="pi pi-mobile action-icon"></i>
                                <h4>Scan NFC</h4>
                                <p>Scan kehadiran dengan NFC</p>
                            </div>
                            <div class="action-item" @click="$router.push('/nfc-writer')">
                                <i class="pi pi-qrcode action-icon"></i>
                                <h4>Write NFC</h4>
                                <p>Tulis kartu NFC baru</p>
                            </div>
                            <div class="action-item" @click="$router.push('/user-recap')">
                                <i class="pi pi-chart-bar action-icon"></i>
                                <h4>Rekap User</h4>
                                <p>Statistik per pengguna</p>
                            </div>
                            <div class="action-item" @click="$router.push('/users')">
                                <i class="pi pi-users action-icon"></i>
                                <h4>Manajemen User</h4>
                                <p>Kelola data pengguna</p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Quick Add Dialog -->
        <Dialog
            v-model:visible="quickAddVisible"
            header="Catat Sholat Cepat"
            :modal="true"
            :style="{ width: '450px' }"
        >
            <form @submit.prevent="saveQuickRecord" class="dialog-form">
                <div class="field">
                    <label for="quickPrayerType">Jenis Sholat *</label>
                    <Dropdown
                        id="quickPrayerType"
                        v-model="quickForm.prayer_type"
                        :options="quickPrayerOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Pilih jenis sholat"
                        :class="{ 'p-invalid': quickErrors.prayer_type }"
                        :disabled="!!quickForm.prayer_type"
                    />
                    <small v-if="quickErrors.prayer_type" class="p-error">{{ quickErrors.prayer_type }}</small>
                </div>

                <div class="field">
                    <label for="quickStatus">Status *</label>
                    <Dropdown
                        id="quickStatus"
                        v-model="quickForm.status"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Pilih status"
                        :class="{ 'p-invalid': quickErrors.status }"
                    />
                    <small v-if="quickErrors.status" class="p-error">{{ quickErrors.status }}</small>
                </div>

                <div class="field">
                    <label for="quickNotes">Catatan</label>
                    <Textarea
                        id="quickNotes"
                        v-model="quickForm.notes"
                        rows="3"
                        placeholder="Tambahkan catatan..."
                    />
                </div>

                <div class="dialog-actions">
                    <Button
                        label="Batal"
                        @click="quickAddVisible = false"
                        severity="secondary"
                        text
                    />
                    <Button
                        type="submit"
                        label="Simpan"
                        :loading="quickSaving"
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
import api from '../api/axios';

const authStore = useAuthStore();
const toast = useToast();

const statistics = ref({
    total: 0,
    sholat: 0,
    tidak_sholat: 0,
    halangan: 0,
    percentage_sholat: 0,
});

const adminStats = ref({
    total_users: 0,
    total_rombels: 0,
    sholat: 0,
    tidak_sholat: 0,
    halangan: 0,
    percentage_sholat: 0,
});

const recentRecords = ref([]);
const todayDhuha = ref(null);
const todayDhuhur = ref(null);
const currentDate = ref('');
const quickAddVisible = ref(false);
const quickSaving = ref(false);
const quickErrors = ref({});

const quickForm = ref({
    prayer_type: null,
    status: null,
    notes: '',
});

const quickPrayerOptions = [
    { label: 'Sholat Dhuha', value: 'dhuha' },
    { label: 'Sholat Dhuhur', value: 'dhuhur' },
];

const statusOptions = [
    { label: 'Sholat', value: 'sholat' },
    { label: 'Tidak Sholat', value: 'tidak_sholat' },
    { label: 'Berhalangan', value: 'halangan' },
];

const isUser = computed(() => !authStore.isAdmin);
const isAdmin = computed(() => authStore.isAdmin);

const updateCurrentDate = () => {
    currentDate.value = new Date().toLocaleDateString('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const fetchStatistics = async () => {
    try {
        const params = {
            start_date: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
            end_date: new Date().toISOString().split('T')[0],
        };

        const response = await api.get('/prayer-records-statistics', { params });
        statistics.value = response.data;
    } catch (error) {
        console.error('Error fetching statistics:', error);
    }
};

const fetchAdminStats = async () => {
    try {
        // Get prayer statistics
        const prayerResponse = await api.get('/prayer-records-statistics', {
            params: {
                start_date: new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0],
                end_date: new Date().toISOString().split('T')[0],
            }
        });

        // Get users count
        const usersResponse = await api.get('/users');
        const totalUsers = (usersResponse.data.data || usersResponse.data).length;

        // Get rombels count
        const rombelsResponse = await api.get('/rombongan-belajar');
        const totalRombels = (rombelsResponse.data.data || rombelsResponse.data).length;

        adminStats.value = {
            ...prayerResponse.data,
            total_users: totalUsers,
            total_rombels: totalRombels,
        };
    } catch (error) {
        console.error('Error fetching admin stats:', error);
    }
};

const fetchTodayStatus = async () => {
    if (isAdmin.value) return;

    try {
        const today = new Date().toISOString().split('T')[0];
        const response = await api.get('/prayer-records', {
            params: {
                start_date: today,
                end_date: today,
                per_page: 10,
            }
        });

        const records = response.data.data || [];
        todayDhuha.value = records.find(r => r.prayer_type === 'dhuha');
        todayDhuhur.value = records.find(r => r.prayer_type === 'dhuhur');
    } catch (error) {
        console.error('Error fetching today status:', error);
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

const quickAddRecord = (prayerType) => {
    quickForm.value = {
        prayer_type: prayerType,
        status: null,
        notes: '',
    };
    quickErrors.value = {};
    quickAddVisible.value = true;
};

const saveQuickRecord = async () => {
    quickErrors.value = {};
    quickSaving.value = true;

    try {
        const data = {
            prayer_type: quickForm.value.prayer_type,
            date: new Date().toISOString().split('T')[0],
            status: quickForm.value.status,
            notes: quickForm.value.notes,
        };

        if (isAdmin.value) {
            data.user_id = authStore.user.id;
        }

        await api.post('/prayer-records', data);

        toast.add({
            severity: 'success',
            summary: 'Berhasil',
            detail: 'Catatan sholat berhasil disimpan',
            life: 3000
        });

        quickAddVisible.value = false;
        fetchTodayStatus();
        fetchStatistics();
        fetchRecentRecords();
    } catch (error) {
        if (error.response?.data?.errors) {
            quickErrors.value = error.response.data.errors;
        } else {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: error.response?.data?.message || 'Gagal menyimpan catatan',
                life: 3000
            });
        }
    } finally {
        quickSaving.value = false;
    }
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatDateForAPI = (date) => {
    return date.toISOString().split('T')[0];
};

const getPrayerBadgeClass = (status) => {
    const classes = {
        sholat: 'prayer-badge success',
        tidak_sholat: 'prayer-badge danger',
        halangan: 'prayer-badge warning',
    };
    return classes[status] || 'prayer-badge empty';
};

const getPrayerBadgeText = (status) => {
    const texts = {
        sholat: 'OK',
        tidak_sholat: '-',
        halangan: 'Halangan',
    };
    return texts[status] || '-';
};

const getPrayerTypeLabel = (type) => {
    const labels = {
        dhuha: 'Dhuha',
        dhuhur: 'Dhuhur',
    };
    return labels[type] || type;
};

onMounted(() => {
    updateCurrentDate();

    if (isAdmin.value) {
        fetchAdminStats();
    } else {
        fetchStatistics();
        fetchTodayStatus();
    }

    fetchRecentRecords();

    // Update date every minute
    setInterval(updateCurrentDate, 60000);
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

.welcome-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.welcome-date {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: rgba(255, 255, 255, 0.8);
    font-size: 0.875rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
}

.stats-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.12);
}

.stat-card.wide {
    grid-column: 1 / -1;
}

.stat-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.75rem;
    padding: 1rem;
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

/* Today Status */
.today-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.today-item {
    text-align: center;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
}

.prayer-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.prayer-icon {
    font-size: 1.5rem;
    color: rgba(255, 255, 255, 0.8);
}

.prayer-icon.dhuha {
    color: #fbbf24;
}

.prayer-icon.dhuhur {
    color: #f59e0b;
}

.prayer-header h4 {
    margin: 0;
    color: white;
    font-size: 0.9rem;
}

.prayer-status {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.prayer-notes {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    font-style: italic;
}

.prayer-empty {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.empty-badge {
    background: rgba(255, 255, 255, 0.1);
    color: rgba(255, 255, 255, 0.6);
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    display: inline-block;
}

.prayer-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
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

/* Admin Actions */
.action-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.action-item {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.action-item:hover {
    background: rgba(255, 255, 255, 0.1);
    transform: translateY(-2px);
    border-color: rgba(255, 255, 255, 0.3);
}

.action-icon {
    font-size: 2rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 0.75rem;
}

.action-item h4 {
    margin: 0 0 0.5rem;
    color: white;
    font-size: 1rem;
}

.action-item p {
    margin: 0;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
}

/* Dialog Form */
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

/* Recent Records */
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

.item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.item-date {
    font-weight: 600;
    color: white;
    font-size: 0.9rem;
}

.item-content {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.item-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
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

.prayer-type {
    text-transform: capitalize;
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

    .welcome-content {
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.25rem;
    }

    .stats-row {
        grid-template-columns: repeat(2, 1fr);
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

    .action-grid {
        grid-template-columns: repeat(3, 1fr);
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

    .stats-row {
        grid-template-columns: repeat(2, 1fr);
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
        font-size: 1.25rem;
    }

    .action-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }
}

/* Responsive adjustments */
@media (max-width: 767px) {
    .welcome-content {
        flex-direction: column;
        align-items: flex-start;
    }

    .today-grid {
        grid-template-columns: 1fr;
    }

    .action-grid {
        grid-template-columns: 1fr;
    }

    .stats-row {
        grid-template-columns: 1fr;
    }
}
</style>