<template>
  <div class="nfc-writer">
    <div class="page-header">
      <h1 class="page-title">Write NFC Tag</h1>
    </div>

    <Card>
      <template #content>
        <div class="writer-content">
          <div class="info-section">
            <h3>Informasi Write NFC</h3>
            <p>Gunakan halaman ini untuk menulis URL ke tag NFC. Pastikan perangkat Anda mendukung Web NFC API.</p>
          </div>

          <div class="user-selection">
            <h3>Pilih Pengguna</h3>
            <div class="field">
              <label for="userSelect">Pengguna</label>
              <Dropdown 
                id="userSelect"
                v-model="selectedUser" 
                :options="users" 
                optionLabel="name" 
                optionValue="id"
                placeholder="Pilih pengguna"
                :loading="loadingUsers"
                @change="onUserSelect"
              />
            </div>
          </div>

          <div v-if="selectedUser" class="url-preview">
            <h3>URL yang akan ditulis</h3>
            <div class="url-display">
              <p>{{ userUrl }}</p>
              <Button 
                icon="pi pi-copy" 
                @click="copyUrl" 
                class="p-button-outlined"
                label="Salin URL"
              />
            </div>
          </div>

          <div class="write-section">
            <h3>Write ke NFC</h3>
            <div class="write-instructions">
              <p>1. Pastikan NFC pada perangkat Anda aktif</p>
              <p>2. Dekatkan tag NFC ke perangkat</p>
              <p>3. Klik tombol "Write ke NFC" di bawah</p>
            </div>
            
            <Button 
              :label="isWriting ? 'Menulis ke NFC...' : 'Write ke NFC'" 
              :icon="isWriting ? 'pi pi-spin pi-spinner' : 'pi pi-qrcode'" 
              @click="writeToNFC"
              :disabled="!selectedUser || isWriting || !nfcSupported"
              :class="{ 'p-button-success': !isWriting }"
            />
            
            <div v-if="!nfcSupported" class="nfc-not-supported">
              <i class="pi pi-exclamation-triangle"></i>
              <span>Web NFC API tidak didukung di browser ini</span>
            </div>
          </div>

          <div v-if="writeResult" class="write-result" :class="writeResult.type">
            <i :class="writeResult.icon"></i>
            <span>{{ writeResult.message }}</span>
          </div>
        </div>
      </template>
    </Card>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from 'primevue/usetoast';
import api from '../api/axios';

// State variables
const toast = useToast();
const users = ref([]);
const selectedUser = ref(null);
const userUrl = ref('');
const loadingUsers = ref(false);
const isWriting = ref(false);
const nfcSupported = ref(false);
const writeResult = ref(null);

// Check NFC support
onMounted(() => {
  if ('NDEFReader' in window) {
    nfcSupported.value = true;
    console.log('Web NFC API is supported');
  } else {
    nfcSupported.value = false;
    console.log('Web NFC API is not supported');
    toast.add({ 
      severity: 'warn', 
      summary: 'Peringatan', 
      detail: 'Web NFC API tidak didukung di browser ini. Fitur write NFC tidak akan berfungsi.', 
      life: 5000 
    });
  }
});

// Fetch users
const fetchUsers = async () => {
  loadingUsers.value = true;
  try {
    const response = await api.get('/users');
    users.value = response.data.data || response.data;
  } catch (error) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Gagal mengambil daftar pengguna', 
      life: 3000 
    });
  } finally {
    loadingUsers.value = false;
  }
};

// Handle user selection
const onUserSelect = () => {
  if (selectedUser.value) {
    // Generate URL for the selected user
    userUrl.value = `${window.location.origin}/users/${selectedUser.value}`;
  } else {
    userUrl.value = '';
  }
};

// Copy URL to clipboard
const copyUrl = async () => {
  try {
    await navigator.clipboard.writeText(userUrl.value);
    toast.add({ 
      severity: 'success', 
      summary: 'Berhasil', 
      detail: 'URL telah disalin ke clipboard', 
      life: 3000 
    });
  } catch (error) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Gagal menyalin URL ke clipboard', 
      life: 3000 
    });
  }
};

// Write to NFC
const writeToNFC = async () => {
  if (!nfcSupported.value) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Web NFC API tidak didukung di browser ini', 
      life: 3000 
    });
    return;
  }

  if (!selectedUser.value) {
    toast.add({ 
      severity: 'warn', 
      summary: 'Peringatan', 
      detail: 'Harap pilih pengguna terlebih dahulu', 
      life: 3000 
    });
    return;
  }

  isWriting.value = true;
  writeResult.value = null;

  try {
    // Create NDEF record with the user URL
    const ndef = new NDEFReader();
    
    // Write to NFC tag
    await ndef.write({
      records: [{
        recordType: "url",
        data: userUrl.value
      }]
    });
    
    // Success
    writeResult.value = {
      type: 'success',
      icon: 'pi pi-check-circle',
      message: 'Berhasil menulis URL ke tag NFC'
    };
    
    toast.add({ 
      severity: 'success', 
      summary: 'Berhasil', 
      detail: 'URL telah berhasil ditulis ke tag NFC', 
      life: 3000 
    });
  } catch (error) {
    // Error
    writeResult.value = {
      type: 'error',
      icon: 'pi pi-times-circle',
      message: `Gagal menulis ke NFC: ${error.message}`
    };
    
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: `Gagal menulis ke NFC: ${error.message}`, 
      life: 5000 
    });
  } finally {
    isWriting.value = false;
  }
};

// Fetch users on component mount
onMounted(() => {
  fetchUsers();
});
</script>

<style scoped>
.nfc-writer {
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

.writer-content {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.info-section h3,
.user-selection h3,
.url-preview h3,
.write-section h3 {
  color: white;
  margin-bottom: 0.5rem;
}

.info-section p {
  color: rgba(255, 255, 255, 0.8);
  margin: 0.5rem 0;
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

.url-preview {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1rem;
}

.url-display {
  display: flex;
  align-items: center;
  gap: 1rem;
  background: rgba(0, 0, 0, 0.2);
  padding: 0.75rem;
  border-radius: 8px;
  margin-top: 0.5rem;
}

.url-display p {
  color: white;
  margin: 0;
  word-break: break-all;
  flex: 1;
}

.write-instructions {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 12px;
  padding: 1rem;
  margin-bottom: 1rem;
}

.write-instructions p {
  color: rgba(255, 255, 255, 0.8);
  margin: 0.25rem 0;
}

.nfc-not-supported {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding: 0.75rem;
  background: rgba(255, 255, 0, 0.1);
  border-radius: 8px;
  color: #ffeb3b;
}

.write-result {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1rem;
  border-radius: 8px;
  margin-top: 1rem;
}

.write-result.success {
  background: rgba(34, 197, 94, 0.1);
  color: #22c55e;
}

.write-result.error {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
}

/* Tablet and up */
@media (min-width: 768px) {
  .url-display {
    gap: 1.5rem;
  }
  
  .write-instructions {
    padding: 1.25rem;
  }
}
</style>