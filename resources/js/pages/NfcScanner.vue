<template>
  <div class="nfc-scanner">
    <div class="page-header">
      <h1 class="page-title">Scan NFC Kehadiran Sholat</h1>
    </div>

    <Card>
      <template #content>
        <!-- Form pemilihan tanggal dan waktu sholat -->
        <div class="selection-form">
          <div class="field">
            <label for="prayerDate">Tanggal Sholat</label>
            <Calendar 
              id="prayerDate" 
              v-model="prayerDate" 
              dateFormat="yy-mm-dd" 
              :showIcon="true"
              :maxDate="new Date()"
            />
          </div>

          <div class="field">
            <label for="prayerType">Jenis Sholat</label>
            <Dropdown 
              id="prayerType" 
              v-model="prayerType" 
              :options="prayerTypes" 
              optionLabel="label" 
              optionValue="value"
              placeholder="Pilih jenis sholat"
            />
          </div>

          <div class="field">
            <label>&nbsp;</label>
            <Button 
              :label="isScanning ? 'Hentikan Scan' : 'Mulai Scan'" 
              :icon="isScanning ? 'pi pi-stop' : 'pi pi-play'" 
              @click="toggleScan"
              :class="isScanning ? 'p-button-danger' : 'p-button-success'"
            />
          </div>
        </div>

        <!-- Status scan -->
        <div v-if="isScanning" class="scan-status">
          <div class="status-indicator">
            <i class="pi pi-wifi status-icon"></i>
            <span>Menunggu kartu NFC...</span>
          </div>
        </div>

        <!-- Kartu yang terdeteksi -->
        <div v-if="detectedCard" class="detected-card">
          <h3>Kartu Terdeteksi</h3>
          <div class="card-info">
            <i class="pi pi-user card-icon"></i>
            <div class="card-details">
              <p class="card-url">{{ detectedCard.url }}</p>
              <p class="card-status">Memproses data pengguna...</p>
            </div>
          </div>
        </div>

        <!-- Informasi pengguna -->
        <div v-if="userInfo" class="user-info">
          <h3>Informasi Pengguna</h3>
          <div class="user-card">
            <div class="user-avatar">
              <i class="pi pi-user"></i>
            </div>
            <div class="user-details">
              <h4>{{ userInfo.name }}</h4>
              <p>{{ userInfo.email }}</p>
            </div>
          </div>
          
          <div class="action-buttons">
            <Button 
              label="Sholat" 
              icon="pi pi-check" 
              @click="confirmAttendance('sholat')"
              class="p-button-success"
            />
            <Button
              label="Berhalangan"
              icon="pi pi-times"
              @click="confirmAttendance('halangan')"
              class="p-button-warning"
            />
          </div>
        </div>

        <!-- Riwayat scan terbaru -->
        <div v-if="scanHistory.length > 0" class="scan-history">
          <h3>Riwayat Scan Terbaru</h3>
          <DataView :value="scanHistory" :paginator="true" :rows="5">
            <template #list="slotProps">
              <div class="history-item" v-for="(item, index) in slotProps.items" :key="index">
                <div class="history-content">
                  <div class="history-user">
                    <i class="pi pi-user"></i>
                    <span>{{ item.userName }}</span>
                  </div>
                  <div class="history-status" :class="item.status">
                    {{ item.statusLabel }}
                  </div>
                  <div class="history-time">
                    {{ item.time }}
                  </div>
                </div>
              </div>
            </template>
          </DataView>
        </div>
      </template>
    </Card>

    <!-- Dialog konfirmasi -->
    <Dialog
      v-model:visible="showConfirmDialog"
      :style="{width: '450px'}"
      header="Konfirmasi Kehadiran"
      :modal="true"
    >
      <div class="confirmation-content">
        <i class="pi pi-exclamation-triangle"></i>
        <span>Apakah {{ currentUserName }} melakukan sholat pada {{ formatDate(prayerDate) }}?</span>
      </div>
      <template #footer>
        <Button label="Batal" icon="pi pi-times" @click="showConfirmDialog = false" class="p-button-text"/>
        <Button label="Berhalangan" icon="pi pi-times" @click="recordAttendance('halangan')" class="p-button-warning"/>
        <Button label="Sholat" icon="pi pi-check" @click="recordAttendance('sholat')" class="p-button-success"/>
      </template>
    </Dialog>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '../api/axios';

// State variables
const toast = useToast();
const isScanning = ref(false);
const prayerDate = ref(new Date());
const prayerType = ref(null);
const detectedCard = ref(null);
const userInfo = ref(null);
const showConfirmDialog = ref(false);
const currentUserName = ref('');
const scanHistory = ref([]);

// Prayer types options
const prayerTypes = [
  { label: 'Dhuhur', value: 'dhuhur' },
  { label: 'Dhuha', value: 'dhuha' }
];

// Mock NFC scanning simulation
let scanInterval = null;

// Toggle scan function
const toggleScan = () => {
  if (isScanning.value) {
    stopScan();
  } else {
    startScan();
  }
};

// Start scanning
const startScan = () => {
  if (!prayerDate.value || !prayerType.value) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Peringatan', 
      detail: 'Harap pilih tanggal dan jenis sholat terlebih dahulu', 
      life: 3000 
    });
    return;
  }

  isScanning.value = true;
  toast.add({ 
    severity: 'info', 
    summary: 'Scan Dimulai', 
    detail: 'Menunggu kartu NFC...', 
    life: 3000 
  });

  readNFC()

  // Simulate NFC scanning
  // scanInterval = setInterval(() => {
  //   // Randomly detect a card (in real implementation, this would be triggered by NFC events)
  //   if (Math.random() > 0.7 && !detectedCard.value) {
  //     detectCard();
  //   }
  // }, 3000);
};

// Stop scanning
const stopScan = () => {
  isScanning.value = false;
  clearInterval(scanInterval);
  detectedCard.value = null;
  userInfo.value = null;
  toast.add({ 
    severity: 'info', 
    summary: 'Scan Dihentikan', 
    detail: 'Scan NFC telah dihentikan', 
    life: 3000 
  });
};

// Detect NFC card
const detectCard = () => {
  // In a real implementation, this would be triggered by NFC events
  // and would contain the actual URL from the NFC tag
  // For now, we'll use mock data with public URLs
  const mockUrls = [
    `${window.location.origin}/u/1`,
    `${window.location.origin}/u/2`
  ];
  
  const randomUrl = mockUrls[Math.floor(Math.random() * mockUrls.length)];
  
  detectedCard.value = {
    url: randomUrl,
    timestamp: new Date()
  };
  
  // Parse user ID from public URL (in real implementation)
  const userId = randomUrl.split('/').pop();
  
  // Fetch user info
  fetchUserInfo(userId);
};

// In a real implementation, you would use the NFC API:
const readNFC = async () => {
  if (!('NDEFReader' in window)) {
    toast.add({
      severity: 'warn',
      summary: 'Peringatan',
      detail: 'NFC tidak didukung di browser ini',
      life: 5000
    });
    return;
  }

  try {
    const ndef = new NDEFReader();
    await ndef.scan();
    
    ndef.addEventListener("reading", ({ message, serialNumber }) => {
      // Process the NFC data
      if (message.records.length > 0) {
        const record = message.records[0];
        const url = new TextDecoder().decode(record.data);
        
        // Update detected card
        detectedCard.value = {
          url: url,
          timestamp: new Date()
        };
        
        // Parse user ID from public URL
        const userId = url.split('/').pop();
        
        // Fetch user info
        fetchUserInfo(userId);
      }
    });
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Gagal membaca NFC: ' + error.message,
      life: 3000
    });
  }
};

// Fetch user info from URL
const fetchUserInfo = async (userId) => {
  try {
    // In a real implementation, this would fetch from the actual URL
    // For now, we'll get user info from our API
    const response = await api.get(`/users/${userId}`);
    userInfo.value = response.data.data || response.data;
    
    // Show confirmation dialog
    currentUserName.value = userInfo.value.name;
    showConfirmDialog.value = true;
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Gagal mengambil informasi pengguna',
      life: 3000
    });
  }
};

// Confirm attendance
const confirmAttendance = (status) => {
  // In a real implementation, this would directly record the attendance
  // For now, we'll show the confirmation dialog
  showConfirmDialog.value = true;
};

// Record attendance
const recordAttendance = async (status) => {
  try {
    showConfirmDialog.value = false;
    
    // Make API call to record attendance
    const response = await api.post('/prayer-records', {
      user_id: userInfo.value.id,
      prayer_type: prayerType.value,
      date: formatDateForAPI(prayerDate.value),
      status: status,
      notes: `Dicatat melalui scan NFC pada ${new Date().toLocaleString('id-ID')}`
    });
    
    // Add to scan history
    scanHistory.value.unshift({
      userName: userInfo.value.name,
      status: status,
      statusLabel: getStatusLabel(status),
      time: new Date().toLocaleTimeString()
    });
    
    // Reset for next scan
    detectedCard.value = null;
    userInfo.value = null;
    
    toast.add({
      severity: 'success',
      summary: 'Berhasil',
      detail: `Kehadiran ${userInfo.value?.name || 'pengguna'} telah dicatat sebagai ${getStatusLabel(status)}`,
      life: 3000
    });
  } catch (error) {
    toast.add({
      severity: 'error',
      summary: 'Error',
      detail: 'Gagal mencatat kehadiran: ' + (error.response?.data?.message || error.message),
      life: 3000
    });
  }
};

// Format date for API
const formatDateForAPI = (date) => {
  if (!date) return null;
  const d = new Date(date);
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
};

// Get status label
const getStatusLabel = (status) => {
  switch (status) {
    case 'sholat': return 'Sholat';
    case 'tidak_sholat': return 'Tidak Sholat';
    case 'halangan': return 'Halangan';
    default: return status;
  }
};

// Format date
const formatDate = (date) => {
  return date.toLocaleDateString('id-ID', {
    day: 'numeric',
    month: 'long',
    year: 'numeric'
  });
};

// Lifecycle hooks
onMounted(() => {
  // Check if NFC is supported in this browser
  if ('NDEFReader' in window) {
    console.log('NFC is supported');
  } else {
    console.log('NFC is not supported in this browser');
    toast.add({ 
      severity: 'warn', 
      summary: 'Peringatan', 
      detail: 'NFC tidak didukung di browser ini. Menggunakan simulasi.', 
      life: 5000 
    });
  }
});

onUnmounted(() => {
  if (scanInterval) {
    clearInterval(scanInterval);
  }
});
</script>

<style scoped>
.nfc-scanner {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.page-title {
  font-size: 1.5rem;
  color: white;
  margin: 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.selection-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
  margin-bottom: 1rem;
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

.scan-status {
  margin: 1.5rem 0;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  text-align: center;
}

.status-indicator {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.1rem;
}

.status-icon {
  font-size: 1.5rem;
  color: #10b981;
  animation: pulse 1.5s infinite;
}

@keyframes pulse {
  0% { opacity: 0.5; }
  50% { opacity: 1; }
  100% { opacity: 0.5; }
}

.detected-card {
  margin: 1.5rem 0;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.12);
  border-radius: 16px;
  border: 1px solid rgba(255, 255, 255, 0.25);
}

.card-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-top: 0.75rem;
}

.card-icon {
  font-size: 2rem;
  color: #10b981;
}

.card-url {
  color: rgba(255, 255, 255, 0.9);
  word-break: break-all;
  margin: 0;
}

.card-status {
  color: rgba(255, 255, 255, 0.7);
  margin: 0.25rem 0 0;
  font-size: 0.9rem;
}

.user-info {
  margin: 1.5rem 0;
}

.user-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  margin-bottom: 1.5rem;
}

.user-avatar {
  width: 50px;
  height: 50px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.user-details h4 {
  margin: 0 0 0.25rem;
  color: white;
}

.user-details p {
  margin: 0;
  color: rgba(255, 255, 255, 0.8);
}

.action-buttons {
  display: flex;
  gap: 1rem;
  justify-content: center;
}

.scan-history {
  margin-top: 1.5rem;
}

.history-item {
  padding: 1rem;
  margin-bottom: 0.75rem;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.history-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.history-user {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: white;
}

.history-status {
  padding: 0.25rem 0.75rem;
  border-radius: 1rem;
  font-size: 0.8rem;
  font-weight: 500;
}

.history-status.sholat {
  background: rgba(34, 197, 94, 0.2);
  color: #22c55e;
}

.history-status.halangan {
  background: rgba(245, 158, 11, 0.2);
  color: #f59e0b;
}

.history-status.tidak_sholat {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
}

.history-time {
  color: rgba(255, 255, 255, 0.7);
  font-size: 0.85rem;
}

.confirmation-content {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 0;
}

.confirmation-content i {
  font-size: 2rem;
  color: #f59e0b;
}

.confirmation-content span {
  color: white;
  font-size: 1.1rem;
}

/* Tablet and up */
@media (min-width: 768px) {
  .selection-form {
    flex-direction: row;
    align-items: end;
    gap: 1.5rem;
  }
  
  .field {
    flex: 1;
  }
  
  .action-buttons {
    justify-content: flex-start;
  }
  
  .history-content {
    flex-wrap: nowrap;
  }
}
</style>