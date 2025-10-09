<template>
  <div class="profile">
    <div class="page-header">
      <h1 class="page-title">Edit Profil</h1>
    </div>

    <Card>
      <template #content>
        <form @submit.prevent="updateProfile" class="profile-form">
          <div class="profile-header">
            <div class="profile-photo">
              <div class="photo-preview" @click="selectPhoto">
                <img 
                  v-if="profileData.photo" 
                  :src="profileData.photo" 
                  :alt="profileData.name"
                  class="photo"
                />
                <div v-else class="photo-placeholder">
                  <i class="pi pi-user"></i>
                </div>
                <div class="photo-overlay">
                  <i class="pi pi-pencil"></i>
                </div>
              </div>
              <input 
                ref="photoInput"
                type="file" 
                accept="image/*" 
                @change="handlePhotoChange"
                class="photo-input"
              />
              <Button 
                v-if="profileData.photo" 
                icon="pi pi-trash" 
                @click="removePhoto"
                class="remove-photo-btn"
                severity="danger"
                text
              />
            </div>
          </div>

          <div class="form-group">
            <div class="field">
              <label for="name">Nama Lengkap *</label>
              <InputText 
                id="name" 
                v-model="profileData.name" 
                placeholder="Masukkan nama lengkap"
                :class="{ 'p-invalid': errors.name }"
              />
              <small v-if="errors.name" class="p-error">{{ errors.name }}</small>
            </div>

            <div class="field">
              <label for="email">Email *</label>
              <InputText 
                id="email" 
                v-model="profileData.email" 
                type="email" 
                placeholder="Masukkan email"
                :class="{ 'p-invalid': errors.email }"
              />
              <small v-if="errors.email" class="p-error">{{ errors.email }}</small>
            </div>

            <div class="field">
              <label for="phone">Nomor HP</label>
              <InputText 
                id="phone" 
                v-model="profileData.phone" 
                placeholder="Masukkan nomor HP"
                :class="{ 'p-invalid': errors.phone }"
              />
              <small v-if="errors.phone" class="p-error">{{ errors.phone }}</small>
            </div>

            <div class="field">
              <label for="nisn">NISN</label>
              <InputText 
                id="nisn" 
                v-model="profileData.nisn" 
                placeholder="Masukkan NISN"
                :class="{ 'p-invalid': errors.nisn }"
              />
              <small v-if="errors.nisn" class="p-error">{{ errors.nisn }}</small>
            </div>

            <div class="field">
              <label for="current_password">Password Saat Ini (untuk mengganti password)</label>
              <Password 
                id="current_password" 
                v-model="profileData.current_password" 
                placeholder="Masukkan password saat ini"
                :feedback="false"
                toggleMask
                :class="{ 'p-invalid': errors.current_password }"
              />
              <small v-if="errors.current_password" class="p-error">{{ errors.current_password }}</small>
            </div>

            <div class="field">
              <label for="password">Password Baru</label>
              <Password 
                id="password" 
                v-model="profileData.password" 
                placeholder="Masukkan password baru"
                :feedback="true"
                toggleMask
                :class="{ 'p-invalid': errors.password }"
              />
              <small v-if="errors.password" class="p-error">{{ errors.password }}</small>
            </div>

            <div class="field">
              <label for="password_confirmation">Konfirmasi Password Baru</label>
              <Password 
                id="password_confirmation" 
                v-model="profileData.password_confirmation" 
                placeholder="Konfirmasi password baru"
                :feedback="false"
                toggleMask
                :class="{ 'p-invalid': errors.password_confirmation }"
              />
              <small v-if="errors.password_confirmation" class="p-error">{{ errors.password_confirmation }}</small>
            </div>
          </div>

          <div class="form-actions">
            <Button 
              type="submit" 
              label="Simpan Perubahan" 
              icon="pi pi-check" 
              :loading="loading"
              class="p-button-success"
            />
          </div>
        </form>
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
const loading = ref(false);
const errors = ref({});
const photoInput = ref(null);

// Profile data
const profileData = ref({
  name: '',
  email: '',
  phone: '',
  nisn: '',
  photo: null,
  current_password: '',
  password: '',
  password_confirmation: ''
});

// Select photo
const selectPhoto = () => {
  photoInput.value.click();
};

// Handle photo change
const handlePhotoChange = (event) => {
  const file = event.target.files[0];
  if (file) {
    // Validate file type
    if (!file.type.startsWith('image/')) {
      toast.add({ 
        severity: 'error', 
        summary: 'Error', 
        detail: 'File harus berupa gambar', 
        life: 3000 
      });
      return;
    }

    // Validate file size (max 2MB)
    if (file.size > 2 * 1024 * 1024) {
      toast.add({ 
        severity: 'error', 
        summary: 'Error', 
        detail: 'Ukuran file maksimal 2MB', 
        life: 3000 
      });
      return;
    }

    // Create preview URL
    const reader = new FileReader();
    reader.onload = (e) => {
      profileData.value.photo = e.target.result;
    };
    reader.readAsDataURL(file);
  }
};

// Remove photo
const removePhoto = () => {
  profileData.value.photo = null;
  photoInput.value.value = '';
};

// Fetch profile data
const fetchProfile = async () => {
  try {
    const response = await api.get('/profile');
    const user = response.data.user;
    
    profileData.value = {
      name: user.name,
      email: user.email,
      phone: user.phone || '',
      nisn: user.nisn || '',
      photo: user.photo ? `/storage/${user.photo}` : null,
      current_password: '',
      password: '',
      password_confirmation: ''
    };
  } catch (error) {
    toast.add({ 
      severity: 'error', 
      summary: 'Error', 
      detail: 'Gagal mengambil data profil', 
      life: 3000 
    });
  }
};

// Update profile
const updateProfile = async () => {
  errors.value = {};
  loading.value = true;

  try {
    // Prepare form data
    const formData = new FormData();
    formData.append('name', profileData.value.name);
    formData.append('email', profileData.value.email);
    formData.append('phone', profileData.value.phone || '');
    formData.append('nisn', profileData.value.nisn || '');
    
    // Add photo if exists
    if (photoInput.value.files[0]) {
      formData.append('photo', photoInput.value.files[0]);
    }
    
    // Add password fields if provided
    if (profileData.value.current_password) {
      formData.append('current_password', profileData.value.current_password);
    }
    if (profileData.value.password) {
      formData.append('password', profileData.value.password);
      formData.append('password_confirmation', profileData.value.password_confirmation);
    }

    // Add _method parameter for PUT request workaround
    formData.append('_method', 'PUT');
    
    // Make API call using POST instead of PUT for multipart/form-data compatibility
    const response = await api.post('/profile', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    });

    toast.add({ 
      severity: 'success', 
      summary: 'Berhasil', 
      detail: 'Profil berhasil diperbarui', 
      life: 3000 
    });

    // Update profile data with response
    const user = response.data.user;
    profileData.value.photo = user.photo ? `/storage/${user.photo}` : null;
  } catch (error) {
    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
    } else {
      toast.add({ 
        severity: 'error', 
        summary: 'Error', 
        detail: error.response?.data?.message || 'Gagal memperbarui profil', 
        life: 3000 
      });
    }
  } finally {
    loading.value = false;
  }
};

// Fetch profile on component mount
onMounted(() => {
  fetchProfile();
});
</script>

<style scoped>
.profile {
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

.profile-header {
  display: flex;
  justify-content: center;
  margin-bottom: 2rem;
}

.profile-photo {
  position: relative;
  display: inline-block;
}

.photo-preview {
  width: 150px;
  height: 150px;
  border-radius: 50%;
  overflow: hidden;
  cursor: pointer;
  position: relative;
  border: 3px solid rgba(255, 255, 255, 0.3);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.photo-placeholder {
  width: 100%;
  height: 100%;
  background: rgba(16, 185, 129, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
}

.photo-placeholder i {
  font-size: 3rem;
  color: rgba(255, 255, 255, 0.7);
}

.photo-overlay {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 0.3s;
}

.photo-preview:hover .photo-overlay {
  opacity: 1;
}

.photo-overlay i {
  font-size: 2rem;
  color: white;
}

.photo-input {
  display: none;
}

.remove-photo-btn {
  position: absolute;
  bottom: -10px;
  right: -10px;
  width: 30px;
  height: 30px;
  min-width: 30px;
  padding: 0;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
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

.form-actions {
  display: flex;
  justify-content: flex-end;
  margin-top: 2rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.2);
}

/* Tablet and up */
@media (min-width: 768px) {
  .page-title {
    font-size: 1.75rem;
  }
  
  .photo-preview {
    width: 180px;
    height: 180px;
  }
  
  .form-group {
    gap: 1.75rem;
  }
  
  .field label {
    font-size: 1rem;
  }
}

/* Desktop and up */
@media (min-width: 1024px) {
  .page-title {
    font-size: 2rem;
  }
  
  .photo-preview {
    width: 200px;
    height: 200px;
  }
}
</style>