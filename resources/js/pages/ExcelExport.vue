<template>
    <div class="excel-export-page">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-info">
                    <h1 class="page-title">
                        <i class="pi pi-file-excel"></i>
                        Export Data Sholat
                    </h1>
                    <p class="page-description">
                        Export data kehadiran sholat dhuhur dan dhuha ke dalam format Excel
                    </p>
                </div>
                <Button
                    icon="pi pi-refresh"
                    label="Refresh"
                    @click="refreshData"
                    :disabled="loading"
                    class="refresh-btn"
                    size="small"
                />
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="main-grid">
            <!-- Export Options Card -->
            <Card class="export-options-card">
                <template #title>
                    <div class="card-title">
                        <i class="pi pi-cog"></i>
                        Opsi Export
                    </div>
                </template>
                <template #content>
                    <div class="export-form">
                        <!-- Monthly Recap Export -->
                        <div class="export-section">
                            <div class="section-header">
                                <div class="section-icon monthly">
                                    <i class="pi pi-calendar"></i>
                                </div>
                                <div class="section-info">
                                    <h3>Rekap Bulanan</h3>
                                    <p>Export semua kelas dalam satu bulan</p>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Bulan</label>
                                    <Dropdown
                                        v-model="selectedMonth"
                                        :options="months"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Pilih Bulan"
                                        class="w-full"
                                        :disabled="loading"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Tahun</label>
                                    <Dropdown
                                        v-model="selectedYear"
                                        :options="years"
                                        placeholder="Pilih Tahun"
                                        class="w-full"
                                        :disabled="loading"
                                    />
                                </div>
                            </div>

                            <Button
                                @click="exportMonthlyRecap"
                                :disabled="loading || !selectedMonth"
                                :loading="loading"
                                label="Export Rekap Bulanan"
                                icon="pi pi-download"
                                class="export-btn monthly"
                            />
                        </div>

                        <!-- Per Class Export -->
                        <div class="export-section">
                            <div class="section-header">
                                <div class="section-icon class-specific">
                                    <i class="pi pi-users"></i>
                                </div>
                                <div class="section-info">
                                    <h3>Per Kelas</h3>
                                    <p>Export data untuk kelas tertentu</p>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group">
                                    <label>Kelas</label>
                                    <Dropdown
                                        v-model="selectedClass"
                                        :options="classes"
                                        optionLabel="nama_rombel"
                                        optionValue="id"
                                        placeholder="Pilih Kelas"
                                        class="w-full"
                                        :disabled="loadingClass"
                                        filter
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Bulan</label>
                                    <Dropdown
                                        v-model="selectedMonthForClass"
                                        :options="months"
                                        optionLabel="label"
                                        optionValue="value"
                                        placeholder="Pilih Bulan"
                                        class="w-full"
                                        :disabled="loadingClass"
                                    />
                                </div>
                                <div class="form-group">
                                    <label>Tahun</label>
                                    <Dropdown
                                        v-model="selectedYearForClass"
                                        :options="years"
                                        placeholder="Pilih Tahun"
                                        class="w-full"
                                        :disabled="loadingClass"
                                    />
                                </div>
                            </div>

                            <Button
                                @click="exportClassData"
                                :disabled="loadingClass || !selectedClass || !selectedMonthForClass"
                                :loading="loadingClass"
                                label="Export Data Kelas"
                                icon="pi pi-download"
                                class="export-btn class-specific"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Statistics Card -->
            <Card class="stats-card">
                <template #title>
                    <div class="card-title">
                        <i class="pi pi-chart-bar"></i>
                        Statistik Data
                    </div>
                </template>
                <template #content>
                    <div v-if="statistics" class="stats-content">
                        <h4>Statistik {{ statistics.month_name }} {{ selectedYear }}</h4>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value">{{ statistics.total_students }}</div>
                                <div class="stat-label">Total Siswa</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ statistics.total_records }}</div>
                                <div class="stat-label">Total Record</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ statistics.total_present }}</div>
                                <div class="stat-label">Sholat Hadir</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">{{ statistics.attendance_rate }}%</div>
                                <div class="stat-label">Kehadiran</div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="no-stats">
                        <i class="pi pi-info-circle"></i>
                        <p>Pilih bulan untuk melihat statistik</p>
                    </div>
                </template>
            </Card>

            <!-- Quick Actions Card -->
            <Card class="quick-actions-card">
                <template #title>
                    <div class="card-title">
                        <i class="pi pi-bolt"></i>
                        Aksi Cepat
                    </div>
                </template>
                <template #content>
                    <div class="quick-actions">
                        <Button
                            @click="exportCurrentMonth"
                            :disabled="loading"
                            :loading="loading"
                            label="Export Bulan Ini"
                            icon="pi pi-calendar"
                            severity="success"
                            class="quick-btn"
                        />
                        <Button
                            @click="exportPreviousMonth"
                            :disabled="loading"
                            :loading="loading"
                            label="Export Bulan Lalu"
                            icon="pi pi-calendar-minus"
                            severity="info"
                            class="quick-btn"
                        />
                    </div>
                </template>
            </Card>

            <!-- Available Months Card -->
            <Card v-if="availableMonths.length > 0" class="months-card">
                <template #title>
                    <div class="card-title">
                        <i class="pi pi-calendar-times"></i>
                        Data Tersedia
                    </div>
                </template>
                <template #content>
                    <div class="months-content">
                        <p class="months-description">Klik bulan untuk memilih data yang akan diekspor:</p>
                        <div class="months-grid">
                            <div
                                v-for="month in availableMonths"
                                :key="month.value"
                                @click="selectMonth(month.value)"
                                class="month-chip"
                                :class="{ active: selectedMonth === month.value }"
                            >
                                <span class="month-name">{{ month.label }}</span>
                                <!-- <span class="month-count">{{ month.count }}</span> -->
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Empty State -->
            <Card v-if="availableMonths.length === 0 && !loading" class="empty-card">
                <template #content>
                    <div class="empty-state">
                        <i class="pi pi-folder-open"></i>
                        <h3>Belum Ada Data</h3>
                        <p>Tidak ada data sholat yang tersedia untuk diekspor</p>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Loading Overlay -->
        <div v-if="loading || loadingClass" class="loading-overlay">
            <div class="loading-content">
                <ProgressSpinner />
                <h3>Sedang Export...</h3>
                <p>Mohon tunggu, sedang memproses data</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../api/axios'

const toast = useToast()

// Data
const loading = ref(false)
const loadingClass = ref(false)
const classes = ref([])
const availableMonths = ref([])
const statistics = ref(null)

// Form data
const selectedMonth = ref('')
const selectedYear = ref(new Date().getFullYear())
const selectedClass = ref('')
const selectedMonthForClass = ref('')
const selectedYearForClass = ref(new Date().getFullYear())

// Options
const months = ref([
    { value: 1, label: 'Januari' },
    { value: 2, label: 'Februari' },
    { value: 3, label: 'Maret' },
    { value: 4, label: 'April' },
    { value: 5, label: 'Mei' },
    { value: 6, label: 'Juni' },
    { value: 7, label: 'Juli' },
    { value: 8, label: 'Agustus' },
    { value: 9, label: 'September' },
    { value: 10, label: 'Oktober' },
    { value: 11, label: 'November' },
    { value: 12, label: 'Desember' }
])

const currentYear = new Date().getFullYear()
const years = ref(Array.from({ length: currentYear - 2020 + 1 }, (_, i) => 2020 + i))

// Methods
const fetchClasses = async () => {
    try {
        const response = await api.get('/rombongan-belajar')
        const classData = response.data.data || response.data
        classes.value = Array.isArray(classData) ? classData : []
    } catch (error) {
        console.error('Error fetching classes:', error)
        classes.value = []
        if (error.response?.status === 401) {
            toast.add({
                severity: 'warn',
                summary: 'Sesi Expired',
                detail: 'Silakan login kembali',
                life: 3000
            })
        }
    }
}

const fetchAvailableMonths = async () => {
    try {
        const response = await api.get(`/export/available-months?year=${selectedYear.value}`)
        const monthData = response.data
        availableMonths.value = Array.isArray(monthData) ? monthData : []
    } catch (error) {
        console.error('Error fetching available months:', error)
        availableMonths.value = []
    }
}

const fetchStatistics = async () => {
    if (!selectedMonth.value) {
        statistics.value = null
        return
    }

    try {
        const response = await api.get(`/export/month-statistics?month=${selectedMonth.value}&year=${selectedYear.value}`)
        statistics.value = response.data
    } catch (error) {
        console.error('Error fetching statistics:', error)
        statistics.value = null
    }
}

const exportMonthlyRecap = async () => {
    if (!selectedMonth.value) {
        toast.add({
            severity: 'warn',
            summary: 'Validasi',
            detail: 'Silakan pilih bulan terlebih dahulu',
            life: 3000
        })
        return
    }

    loading.value = true

    try {
        const response = await api.get(`/export/monthly-recap`, {
            params: {
                month: selectedMonth.value,
                year: selectedYear.value
            },
            responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url

        const monthName = months.value.find(m => m.value === selectedMonth.value)?.label || selectedMonth.value
        const fileName = `Rekap-Sholat-${monthName}-${selectedYear.value}.xlsx`

        link.setAttribute('download', fileName)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)

        toast.add({
            severity: 'success',
            summary: 'Export Berhasil',
            detail: `File "${fileName}" berhasil diunduh`,
            life: 5000
        })
    } catch (error) {
        console.error('Error exporting:', error)
        toast.add({
            severity: 'error',
            summary: 'Export Gagal',
            detail: 'Gagal export data',
            life: 3000
        })
    } finally {
        loading.value = false
    }
}

const exportClassData = async () => {
    if (!selectedClass.value || !selectedMonthForClass.value) {
        toast.add({
            severity: 'warn',
            summary: 'Validasi',
            detail: 'Silakan pilih kelas dan bulan terlebih dahulu',
            life: 3000
        })
        return
    }

    loadingClass.value = true

    try {
        const response = await api.get(`/export/class/${selectedClass.value}`, {
            params: {
                month: selectedMonthForClass.value,
                year: selectedYearForClass.value
            },
            responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url

        const className = classes.value.find(c => c.id === selectedClass.value)?.nama_rombel || 'Kelas'
        const monthName = months.value.find(m => m.value === selectedMonthForClass.value)?.label || selectedMonthForClass.value
        const fileName = `Rekap-Sholat-${className}-${monthName}-${selectedYearForClass.value}.xlsx`

        link.setAttribute('download', fileName)
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)

        toast.add({
            severity: 'success',
            summary: 'Export Berhasil',
            detail: `File "${fileName}" berhasil diunduh`,
            life: 5000
        })
    } catch (error) {
        console.error('Error exporting:', error)
        toast.add({
            severity: 'error',
            summary: 'Export Gagal',
            detail: 'Gagal export data',
            life: 3000
        })
    } finally {
        loadingClass.value = false
    }
}

const selectMonth = (monthValue) => {
    selectedMonth.value = monthValue
    toast.add({
        severity: 'info',
        summary: 'Bulan Dipilih',
        detail: `Bulan ${months.value.find(m => m.value === monthValue)?.label} telah dipilih`,
        life: 2000
    })
}

const exportCurrentMonth = () => {
    const currentMonth = new Date().getMonth() + 1
    selectedMonth.value = currentMonth
    selectedYear.value = new Date().getFullYear()
    exportMonthlyRecap()
}

const exportPreviousMonth = () => {
    const date = new Date()
    date.setMonth(date.getMonth() - 1)
    selectedMonth.value = date.getMonth() + 1
    selectedYear.value = date.getFullYear()
    exportMonthlyRecap()
}

const refreshData = () => {
    fetchClasses()
    fetchAvailableMonths()
    if (selectedMonth.value) {
        fetchStatistics()
    }
    toast.add({
        severity: 'success',
        summary: 'Data Di-refresh',
        detail: 'Data telah diperbarui',
        life: 2000
    })
}

// Watchers
watch(selectedYear, () => {
    fetchAvailableMonths()
    if (selectedMonth.value) {
        fetchStatistics()
    }
})

watch(selectedMonth, () => {
    fetchStatistics()
})

// Lifecycle
onMounted(() => {
    fetchClasses()
    fetchAvailableMonths()
})
</script>

<style scoped>
.excel-export-page {
    min-height: 100vh;
    background: #396349;
    background-attachment: fixed;
    padding: 1.5rem;
    position: relative;
    overflow-x: hidden;
}

/* Animated background overlay for depth */
.excel-export-page::before {
    content: '';
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background:
        radial-gradient(circle at 20% 50%, rgba(16, 185, 129, 0.3) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(52, 211, 153, 0.2) 0%, transparent 50%),
        radial-gradient(circle at 40% 20%, rgba(5, 150, 105, 0.25) 0%, transparent 50%);
    animation: floatingGradient 15s ease-in-out infinite;
    pointer-events: none;
    z-index: 0;
}

@keyframes floatingGradient {
    0%, 100% {
        opacity: 0.5;
        transform: scale(1) translate(0, 0);
    }
    50% {
        opacity: 0.8;
        transform: scale(1.1) translate(20px, -20px);
    }
}

/* Header Section */
.header-section {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(20px) saturate(180%);
    border-radius: 20px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3),
        0 1px 2px rgba(0, 0, 0, 0.1);
    position: relative;
    z-index: 1;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    position: relative;
    z-index: 1;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin: 0;
    text-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}

.page-title i {
    font-size: 1.8rem;
    color: rgba(16, 185, 129, 0.9);
}

.page-description {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.95);
    margin: 0.5rem 0 0 0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.refresh-btn {
    background: rgba(16, 185, 129, 0.2) !important;
    border: 1px solid rgba(16, 185, 129, 0.4) !important;
    color: white !important;
    backdrop-filter: blur(10px) saturate(150%) !important;
}

.refresh-btn:hover {
    background: rgba(16, 185, 129, 0.3) !important;
    border-color: rgba(16, 185, 129, 0.6) !important;
}

/* Main Grid */
.main-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
    position: relative;
    z-index: 1;
}

/* Cards */
.export-options-card,
.stats-card,
.quick-actions-card,
.months-card,
.empty-card {
    background: rgba(255, 255, 255, 0.12);
    backdrop-filter: blur(20px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.25);
    border-radius: 20px;
    box-shadow:
        0 8px 32px rgba(0, 0, 0, 0.15),
        inset 0 1px 0 rgba(255, 255, 255, 0.3),
        0 1px 2px rgba(0, 0, 0, 0.1);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: visible;
}

.export-options-card::before,
.stats-card::before,
.quick-actions-card::before,
.months-card::before,
.empty-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.2) 0%,
        rgba(255, 255, 255, 0.05) 50%,
        rgba(255, 255, 255, 0.15) 100%
    );
    border-radius: 20px;
    opacity: 0.7;
    z-index: -1;
    transition: opacity 0.3s ease;
}

.export-options-card:hover,
.stats-card:hover,
.quick-actions-card:hover,
.months-card:hover {
    transform: translateY(-4px);
    box-shadow:
        0 12px 40px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.4),
        0 1px 3px rgba(0, 0, 0, 0.12);
}

.export-options-card:hover::before,
.stats-card:hover::before,
.quick-actions-card:hover::before,
.months-card:hover::before {
    opacity: 1;
}

.card-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    font-weight: 600;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.card-title i {
    font-size: 1.2rem;
    color: rgba(16, 185, 129, 0.9);
}

/* Export Form */
.export-form {
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.export-section {
    background: rgba(255, 255, 255, 0.08);
    border-radius: 15px;
    padding: 1.5rem;
    border: 1px solid rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px) saturate(150%);
    position: relative;
}

.section-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.section-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
}

.section-icon.monthly {
    background: rgba(16, 185, 129, 0.25);
    border: 1px solid rgba(16, 185, 129, 0.5);
    backdrop-filter: blur(5px);
}

.section-icon.monthly i {
    color: #10b981;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.section-icon.class-specific {
    background: rgba(34, 197, 94, 0.25);
    border: 1px solid rgba(34, 197, 94, 0.5);
    backdrop-filter: blur(5px);
}

.section-icon.class-specific i {
    color: #22c55e;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.section-info h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin: 0 0 0.25rem 0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.section-info p {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-group label {
    font-weight: 600;
    color: white;
    font-size: 0.875rem;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

/* Export Buttons */
.export-btn {
    padding: 1rem;
    font-weight: 600;
    border-radius: 12px;
    border: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px) saturate(150%);
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.export-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(
        135deg,
        rgba(255, 255, 255, 0.2) 0%,
        rgba(255, 255, 255, 0.1) 100%
    );
    opacity: 0;
    transition: opacity 0.3s ease;
}

.export-btn:hover::before {
    opacity: 1;
}

.export-btn.monthly {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: 1px solid rgba(16, 185, 129, 0.4);
    box-shadow:
        0 4px 16px rgba(16, 185, 129, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.export-btn.monthly:hover:not(:disabled) {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-3px);
    box-shadow:
        0 8px 25px rgba(16, 185, 129, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.3),
        0 0 20px rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.6);
}

.export-btn.class-specific {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    border: 1px solid rgba(34, 197, 94, 0.4);
    box-shadow:
        0 4px 16px rgba(34, 197, 94, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.export-btn.class-specific:hover:not(:disabled) {
    background: linear-gradient(135deg, #16a34a, #15803d);
    transform: translateY(-3px);
    box-shadow:
        0 8px 25px rgba(34, 197, 94, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.3),
        0 0 20px rgba(34, 197, 94, 0.2);
    border-color: rgba(34, 197, 94, 0.6);
}

/* Statistics */
.stats-content h4 {
    font-size: 1.125rem;
    font-weight: 600;
    color: white;
    margin: 0 0 1rem 0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.stat-item {
    background: rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    padding: 1rem;
    text-align: center;
    border: 1px solid rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px) saturate(150%);
    transition: all 0.3s ease;
}

.stat-item:hover {
    background: rgba(255, 255, 255, 0.18);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 700;
    color: #fbbf24;
    margin-bottom: 0.25rem;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.stat-label {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.95);
    font-weight: 500;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.no-stats {
    text-align: center;
    padding: 2rem;
    color: rgba(255, 255, 255, 0.6);
}

.no-stats i {
    font-size: 3rem;
    margin-bottom: 1rem;
}

/* Quick Actions */
.quick-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.quick-btn {
    padding: 1rem;
    font-weight: 600;
    border-radius: 10px;
}

/* Months */
.months-description {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0 0 1rem 0;
}

.months-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.month-chip {
    background: rgba(16, 185, 129, 0.2);
    border: 1px solid rgba(16, 185, 129, 0.4);
    border-radius: 25px;
    padding: 0.75rem 1.25rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
    min-width: 120px;
    backdrop-filter: blur(5px) saturate(150%);
}

.month-chip:hover {
    background: rgba(16, 185, 129, 0.3);
    border-color: rgba(16, 185, 129, 0.6);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.month-chip.active {
    background: rgba(16, 185, 129, 0.4);
    border-color: rgba(16, 185, 129, 0.8);
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
}

.month-name {
    display: block;
    font-weight: 600;
    color: white;
    font-size: 0.875rem;
    margin-bottom: 0.25rem;
}

.month-count {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.7);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: rgba(255, 255, 255, 0.6);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
}

.empty-state h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin: 0 0 0.5rem 0;
}

.empty-state p {
    font-size: 0.875rem;
    margin: 0;
}

/* Loading Overlay */
.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.8);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.loading-content {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 20px;
    padding: 2.5rem;
    text-align: center;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    max-width: 350px;
}

.loading-content h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1f2937;
    margin: 1rem 0 0.5rem 0;
}

.loading-content p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

/* Responsive Design */
@media (min-width: 768px) {
    .header-section {
        padding: 2.5rem;
    }

    .page-title {
        font-size: 2.25rem;
    }

    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .stats-grid {
        grid-template-columns: repeat(4, 1fr);
    }

    .quick-actions {
        flex-direction: row;
    }

    .quick-btn {
        flex: 1;
    }
}

@media (min-width: 1024px) {
    .excel-export-page {
        padding: 2rem;
    }

    .main-grid {
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }

    .export-options-card {
        grid-row: span 2;
    }

    .quick-actions-card {
        grid-column: 2;
    }

    .months-card {
        grid-column: 2;
    }

    .empty-card {
        grid-column: 1 / -1;
    }
}
</style>