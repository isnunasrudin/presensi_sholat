<template>
    <div class="user-import-page">
        <!-- Header Section -->
        <div class="header-section">
            <div class="header-content">
                <div class="header-info">
                    <h1 class="page-title">
                        <i class="pi pi-upload"></i>
                        Import Users
                    </h1>
                    <p class="page-description">
                        Import data pengguna dari file Excel atau CSV dengan auto-creation rombel
                    </p>
                </div>
                <Button
                    icon="pi pi-arrow-left"
                    label="Kembali"
                    @click="$router.push('/users')"
                    class="back-btn"
                    size="small"
                />
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="main-grid">
            <!-- Import Form Card -->
            <Card class="import-form-card">
                <template #title>
                    <div class="card-title">
                        <i class="pi pi-file-import"></i>
                        Upload File Import
                    </div>
                </template>
                <template #content>
                    <div class="import-form">
                        <!-- File Upload Section -->
                        <div class="upload-section">
                            <div class="upload-area"
                                 :class="{ 'drag-over': isDragOver, 'has-file': selectedFile }"
                                 @dragover.prevent="isDragOver = true"
                                 @dragleave.prevent="isDragOver = false"
                                 @drop.prevent="handleDrop"
                                 @click="$refs.fileInput.click()">

                                <div v-if="!selectedFile" class="upload-placeholder">
                                    <i class="pi pi-cloud-upload"></i>
                                    <h3>Drag & Drop File</h3>
                                    <p>atau klik untuk memilih file</p>
                                    <small class="file-info">Mendukung: Excel (.xlsx, .xls) atau CSV (.csv) - Max 10MB</small>
                                </div>

                                <div v-else class="selected-file">
                                    <i class="pi pi-file"></i>
                                    <div class="file-details">
                                        <h4>{{ selectedFile.name }}</h4>
                                        <p>{{ formatFileSize(selectedFile.size) }}</p>
                                    </div>
                                    <Button
                                        icon="pi pi-times"
                                        @click.stop="removeFile"
                                        class="remove-file-btn"
                                        text
                                        rounded
                                    />
                                </div>

                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".xlsx,.xls,.csv,.txt"
                                    @change="handleFileSelect"
                                    style="display: none;"
                                />
                            </div>
                        </div>

                        <!-- Import Button -->
                        <div class="import-actions">
                            <Button
                                @click="importUsers"
                                :disabled="!selectedFile || importing"
                                :loading="importing"
                                label="Import Users"
                                icon="pi pi-upload"
                                class="import-btn"
                            />
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Template Download Card -->
            <Card class="template-card">
                <template #title>
                    <div class="card-title">
                        <i class="pi pi-download"></i>
                        Template Import
                    </div>
                </template>
                <template #content>
                    <div class="template-content">
                        <p class="template-description">
                            Download template untuk format import yang benar
                        </p>
                        <Button
                            @click="downloadTemplate"
                            :disabled="downloading"
                            :loading="downloading"
                            label="Download Template"
                            icon="pi pi-file-download"
                            class="template-btn"
                        />
                    </div>
                </template>
            </Card>

            <!-- Import Instructions Card -->
            <Card class="instructions-card">
                <template #title>
                    <div class="card-title">
                        <i class="pi pi-info-circle"></i>
                        Petunjuk Import
                    </div>
                </template>
                <template #content>
                    <div class="instructions-content">
                        <div class="instruction-section">
                            <h4>Field Wajib (*)</h4>
                            <ul>
                                <li><strong>name*</strong> - Nama lengkap user</li>
                                <li><strong>role*</strong> - Role (admin/user)</li>
                                <li><strong>tahun_angkatan*</strong> - Tahun angkatan (contoh: 2024) [tabel: rombongan_belajar]</li>
                                <li><strong>nama_rombel*</strong> - Nama rombel (contoh: XII-A) [tabel: rombongan_belajar]</li>
                                <li><strong>tingkat*</strong> - Tingkat kelas (contoh: XII) [tabel: rombongan_belajar]</li>
                            </ul>
                        </div>

                        <div class="instruction-section">
                            <h4>Field Opsional</h4>
                            <ul>
                                <li><strong>email</strong> - Email (akan digenerate otomatis jika kosong)</li>
                                <li><strong>nisn</strong> - Nomor induk siswa nasional (opsional, max 20 karakter)</li>
                                <li><strong>gender</strong> - Jenis kelamin (L/P)</li>
                            </ul>
                        </div>

                        <div class="instruction-section">
                            <h4>Auto-Creation Rombel (Tabel: rombongan_belajar)</h4>
                            <ul>
                                <li><strong>Rombel baru akan dibuat otomatis</strong> jika belum ada di tabel</li>
                                <li>Sistem akan mencocokkan berdasarkan <strong>nama_rombel + tingkat + tahun_angkatan</strong></li>
                                <li>Jika rombel sudah ada, user akan langsung diattach ke rombel tersebut</li>
                                <li>Field di tabel: <code>nama_rombel</code>, <code>tingkat</code>, <code>tahun_angkatan</code></li>
                            </ul>
                        </div>

                        <div class="instruction-section">
                            <h4>Catatan</h4>
                            <ul>
                                <li>Password default: <code>password</code></li>
                                <li>Email akan digenerate otomatis jika tidak diisi (format: namasiswa@snesa.local)</li>
                                <li>Hanya admin yang bisa melakukan import</li>
                                <li>File yang error akan dilewati dengan pesan error</li>
                                <li>Rombel yang sama tidak akan dibuat duplikatnya</li>
                            </ul>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Available Rombel Card -->
            <Card class="rombel-card">
                <template #title>
                    <div class="card-title">
                        <i class="pi pi-users"></i>
                        Rombel Tersedia
                    </div>
                </template>
                <template #content>
                    <div class="rombel-content">
                        <Button
                            @click="loadAvailableRombel"
                            :disabled="loadingRombel"
                            :loading="loadingRombel"
                            label="Refresh Rombel"
                            icon="pi pi-refresh"
                            severity="secondary"
                            size="small"
                            class="refresh-rombel-btn"
                        />

                        <div v-if="availableRombel.length > 0" class="rombel-list">
                            <div
                                v-for="rombel in availableRombel"
                                :key="rombel"
                                class="rombel-chip">
                                {{ rombel }}
                            </div>
                        </div>

                        <div v-else-if="!loadingRombel" class="no-rombel">
                            <i class="pi pi-info-circle"></i>
                            <p>Belum ada data rombel</p>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- Import Results Card -->
            <Card v-if="importResults" class="results-card">
                <template #title>
                    <div class="card-title">
                        <i class="pi pi-check-circle"></i>
                        Hasil Import
                    </div>
                </template>
                <template #content>
                    <div class="results-content">
                        <div class="results-summary">
                            <div class="result-item success">
                                <i class="pi pi-check"></i>
                                <div>
                                    <strong>{{ importResults.imported_count }}</strong>
                                    <span>Berhasil diimport</span>
                                </div>
                            </div>
                            <div class="result-item skipped">
                                <i class="pi pi-exclamation-triangle"></i>
                                <div>
                                    <strong>{{ importResults.skipped_count }}</strong>
                                    <span>Dilewati/Error</span>
                                </div>
                            </div>
                        </div>

                        <div v-if="importResults.errors && importResults.errors.length > 0" class="errors-section">
                            <h4>Detail Error</h4>
                            <div class="errors-list">
                                <div
                                    v-for="(error, index) in importResults.errors.slice(0, 10)"
                                    :key="index"
                                    class="error-item">
                                    <span class="error-row">Baris {{ error.row }}:</span>
                                    <span class="error-message">{{ Object.values(error.errors).join(', ') }}</span>
                                </div>
                                <div
                                    v-if="importResults.errors.length > 10"
                                    class="more-errors">
                                    ... dan {{ importResults.errors.length - 10 }} error lainnya
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </div>

        <!-- Loading Overlay -->
        <div v-if="importing" class="loading-overlay">
            <div class="loading-content">
                <ProgressSpinner />
                <h3>Mengimport Users...</h3>
                <p>Mohon tunggu, sedang memproses file</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useToast } from 'primevue/usetoast'
import api from '../api/axios'

const toast = useToast()

// Data
const selectedFile = ref(null)
const importing = ref(false)
const downloading = ref(false)
const loadingRombel = ref(false)
const isDragOver = ref(false)
const availableRombel = ref([])
const importResults = ref(null)

// Methods
const handleFileSelect = (event) => {
    const file = event.target.files[0]
    if (file) {
        processFile(file)
    }
}

const handleDrop = (event) => {
    isDragOver.value = false
    const file = event.dataTransfer.files[0]
    if (file) {
        processFile(file)
    }
}

const processFile = (file) => {
    // Validate file type
    const allowedTypes = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-excel', // .xls
        'text/csv', // .csv
        'text/plain' // .txt (csv)
    ]

    if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv|txt)$/i)) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Format file tidak didukung. Gunakan Excel (.xlsx, .xls) atau CSV (.csv)',
            life: 5000
        })
        return
    }

    // Validate file size (10MB)
    if (file.size > 10 * 1024 * 1024) {
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Ukuran file terlalu besar. Maksimal 10MB',
            life: 5000
        })
        return
    }

    selectedFile.value = file
    importResults.value = null // Clear previous results
}

const removeFile = () => {
    selectedFile.value = null
    importResults.value = null
    if (document.querySelector('.file-input')) {
        document.querySelector('.file-input').value = ''
    }
}

const formatFileSize = (bytes) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

const importUsers = async () => {
    if (!selectedFile.value) {
        toast.add({
            severity: 'warn',
            summary: 'Validasi',
            detail: 'Silakan pilih file terlebih dahulu',
            life: 3000
        })
        return
    }

    importing.value = true

    try {
        const formData = new FormData()
        formData.append('file', selectedFile.value)

        const response = await api.post('/users/import', formData, {
            headers: {
                'Content-Type': 'multipart/form-data'
            }
        })

        if (response.data.success) {
            importResults.value = response.data.data

            toast.add({
                severity: 'success',
                summary: 'Import Berhasil',
                detail: `${response.data.data.imported_count} users berhasil diimport`,
                life: 5000
            })

            // Clear file after successful import
            selectedFile.value = null

            // Trigger refresh in parent components if needed
            window.dispatchEvent(new CustomEvent('users-imported'))
        } else {
            throw new Error(response.data.message)
        }

    } catch (error) {
        console.error('Import error:', error)

        let errorMessage = 'Gagal import users'

        if (error.response?.data?.errors) {
            // Handle validation errors
            const errors = error.response.data.errors
            errorMessage = Object.values(errors).flat().join(', ')
        } else if (error.response?.data?.message) {
            errorMessage = error.response.data.message
        } else if (error.message) {
            errorMessage = error.message
        }

        toast.add({
            severity: 'error',
            summary: 'Import Gagal',
            detail: errorMessage,
            life: 5000
        })
    } finally {
        importing.value = false
    }
}

const downloadTemplate = async () => {
    downloading.value = true

    try {
        const response = await api.get('/users/import/template', {
            responseType: 'blob'
        })

        const url = window.URL.createObjectURL(new Blob([response.data]))
        const link = document.createElement('a')
        link.href = url
        link.setAttribute('download', 'template_import_users_enhanced.csv')
        document.body.appendChild(link)
        link.click()
        document.body.removeChild(link)
        window.URL.revokeObjectURL(url)

        toast.add({
            severity: 'success',
            summary: 'Download Berhasil',
            detail: 'Template berhasil diunduh',
            life: 3000
        })

    } catch (error) {
        console.error('Download template error:', error)
        toast.add({
            severity: 'error',
            summary: 'Download Gagal',
            detail: 'Gagal mengunduh template',
            life: 3000
        })
    } finally {
        downloading.value = false
    }
}

const loadAvailableRombel = async () => {
    loadingRombel.value = true

    try {
        const response = await api.get('/users/import/rombel')

        if (response.data.success) {
            availableRombel.value = response.data.data.available_rombel
        } else {
            throw new Error(response.data.message)
        }

    } catch (error) {
        console.error('Load rombel error:', error)
        toast.add({
            severity: 'error',
            summary: 'Error',
            detail: 'Gagal memuat data rombel',
            life: 3000
        })
    } finally {
        loadingRombel.value = false
    }
}

// Lifecycle
onMounted(() => {
    loadAvailableRombel()
})
</script>

<style scoped>
.user-import-page {
    min-height: 100vh;
    background: #396349;
    background-attachment: fixed;
    padding: 1.5rem;
    position: relative;
    overflow-x: hidden;
}

/* Animated background overlay */
.user-import-page::before {
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

.back-btn {
    background: rgba(255, 255, 255, 0.2) !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    color: white !important;
    backdrop-filter: blur(10px) saturate(150%) !important;
}

.back-btn:hover {
    background: rgba(255, 255, 255, 0.3) !important;
    border-color: rgba(255, 255, 255, 0.6) !important;
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
.import-form-card,
.template-card,
.instructions-card,
.rombel-card,
.results-card {
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

.import-form-card::before,
.template-card::before,
.instructions-card::before,
.rombel-card::before,
.results-card::before {
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

.import-form-card:hover,
.template-card:hover,
.instructions-card:hover,
.rombel-card:hover,
.results-card:hover {
    transform: translateY(-4px);
    box-shadow:
        0 12px 40px rgba(0, 0, 0, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.4),
        0 1px 3px rgba(0, 0, 0, 0.12);
}

.import-form-card:hover::before,
.template-card:hover::before,
.instructions-card:hover::before,
.rombel-card:hover::before,
.results-card:hover::before {
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

/* Upload Area */
.upload-area {
    border: 2px dashed rgba(255, 255, 255, 0.3);
    border-radius: 15px;
    padding: 3rem 2rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.05);
    position: relative;
    overflow: hidden;
}

.upload-area:hover {
    border-color: rgba(16, 185, 129, 0.6);
    background: rgba(16, 185, 129, 0.1);
}

.upload-area.drag-over {
    border-color: #10b981;
    background: rgba(16, 185, 129, 0.15);
    transform: scale(1.02);
}

.upload-area.has-file {
    border-color: rgba(34, 197, 94, 0.6);
    background: rgba(34, 197, 94, 0.1);
}

.upload-placeholder i {
    font-size: 3rem;
    color: rgba(16, 185, 129, 0.8);
    margin-bottom: 1rem;
}

.upload-placeholder h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: white;
    margin: 0 0 0.5rem 0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.upload-placeholder p {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0 0 1rem 0;
}

.file-info {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
}

.selected-file {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.selected-file i {
    font-size: 2rem;
    color: rgba(34, 197, 94, 0.9);
}

.file-details {
    flex: 1;
    text-align: left;
}

.file-details h4 {
    font-size: 1rem;
    font-weight: 600;
    color: white;
    margin: 0 0 0.25rem 0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.file-details p {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
}

.remove-file-btn {
    color: rgba(255, 255, 255, 0.8) !important;
}

.remove-file-btn:hover {
    color: #ef4444 !important;
}

/* Import Actions */
.import-actions {
    margin-top: 2rem;
    display: flex;
    justify-content: center;
}

.import-btn {
    padding: 1rem 2rem;
    font-weight: 600;
    border-radius: 12px;
    border: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    backdrop-filter: blur(10px) saturate(150%);
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.import-btn::before {
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

.import-btn:hover::before {
    opacity: 1;
}

.import-btn {
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    border: 1px solid rgba(16, 185, 129, 0.4);
    box-shadow:
        0 4px 16px rgba(16, 185, 129, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.import-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-3px);
    box-shadow:
        0 8px 25px rgba(16, 185, 129, 0.5),
        inset 0 1px 0 rgba(255, 255, 255, 0.3),
        0 0 20px rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.6);
}

/* Template Content */
.template-content {
    text-align: center;
}

.template-description {
    font-size: 1rem;
    color: rgba(255, 255, 255, 0.9);
    margin: 0 0 1.5rem 0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
}

.template-btn {
    padding: 1rem 1.5rem;
    font-weight: 600;
    border-radius: 10px;
    background: linear-gradient(135deg, #22c55e, #16a34a);
    color: white;
    border: 1px solid rgba(34, 197, 94, 0.4);
    box-shadow:
        0 4px 16px rgba(34, 197, 94, 0.3),
        inset 0 1px 0 rgba(255, 255, 255, 0.2);
}

.template-btn:hover:not(:disabled) {
    background: linear-gradient(135deg, #16a34a, #15803d);
    transform: translateY(-2px);
    box-shadow:
        0 6px 20px rgba(34, 197, 94, 0.4),
        inset 0 1px 0 rgba(255, 255, 255, 0.3);
    border-color: rgba(34, 197, 94, 0.6);
}

/* Instructions */
.instruction-section {
    margin-bottom: 1.5rem;
}

.instruction-section:last-child {
    margin-bottom: 0;
}

.instruction-section h4 {
    font-size: 1rem;
    font-weight: 600;
    color: white;
    margin: 0 0 0.75rem 0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.instruction-section ul {
    margin: 0;
    padding-left: 1.5rem;
}

.instruction-section li {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.9);
    margin-bottom: 0.5rem;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.instruction-section li strong {
    color: rgba(16, 185, 129, 0.9);
}

.instruction-section code {
    background: rgba(255, 255, 255, 0.1);
    padding: 0.125rem 0.375rem;
    border-radius: 4px;
    font-family: monospace;
    font-size: 0.875rem;
    color: rgba(16, 185, 129, 0.9);
}

/* Rombel Content */
.rombel-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.refresh-rombel-btn {
    align-self: flex-start;
}

.rombel-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.rombel-chip {
    background: rgba(16, 185, 129, 0.2);
    border: 1px solid rgba(16, 185, 129, 0.4);
    border-radius: 20px;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    color: white;
    font-weight: 500;
    backdrop-filter: blur(5px);
    transition: all 0.3s ease;
}

.rombel-chip:hover {
    background: rgba(16, 185, 129, 0.3);
    border-color: rgba(16, 185, 129, 0.6);
    transform: translateY(-1px);
}

.no-rombel {
    text-align: center;
    padding: 2rem 1rem;
    color: rgba(255, 255, 255, 0.6);
}

.no-rombel i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.no-rombel p {
    font-size: 0.875rem;
    margin: 0;
}

/* Results */
.results-summary {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.result-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

.result-item.success {
    background: rgba(16, 185, 129, 0.2);
    border: 1px solid rgba(16, 185, 129, 0.4);
}

.result-item.success i {
    color: #10b981;
    font-size: 1.25rem;
}

.result-item.skipped {
    background: rgba(251, 191, 36, 0.2);
    border: 1px solid rgba(251, 191, 36, 0.4);
}

.result-item.skipped i {
    color: #fbbf24;
    font-size: 1.25rem;
}

.result-item strong {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    display: block;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.result-item span {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.9);
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
}

.errors-section h4 {
    font-size: 1rem;
    font-weight: 600;
    color: white;
    margin: 0 0 1rem 0;
    text-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.errors-list {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
    padding: 1rem;
    border: 1px solid rgba(255, 255, 255, 0.1);
    max-height: 300px;
    overflow-y: auto;
}

.error-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 0.75rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.error-item:last-child {
    border-bottom: none;
}

.error-row {
    font-size: 0.875rem;
    font-weight: 600;
    color: rgba(251, 191, 36, 0.9);
}

.error-message {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.8);
}

.more-errors {
    text-align: center;
    padding: 0.75rem;
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.7);
    font-style: italic;
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

    .results-summary {
        grid-template-columns: 1fr 1fr;
    }
}

@media (min-width: 1024px) {
    .user-import-page {
        padding: 2rem;
    }

    .main-grid {
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
    }

    .import-form-card {
        grid-row: span 2;
    }

    .instructions-card {
        grid-column: 2;
        grid-row: 1;
    }

    .rombel-card {
        grid-column: 2;
        grid-row: 2;
    }

    .template-card {
        grid-column: 1 / -1;
    }

    .results-card {
        grid-column: 1 / -1;
    }
}
</style>