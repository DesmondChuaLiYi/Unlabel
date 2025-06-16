<script setup>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios'; // Add this import

const router = useRouter();

const userData = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  address: '',
  city: '',
  state: '',
  zipCode: '',
  country: '',
  birthDate: '',
  profile_picture: '',
});

const errors = reactive({ form: '' });
const alertType = ref('alert-danger');
const showAlert = ref(false);

const isLoggedIn = ref(false);
const isLoading = ref(true);

const isEditingProfile = ref(false);
const isEditingPassword = ref(false);
const isEditingAddress = ref(false);

const isSubmittingProfile = ref(false);
const isSubmittingAddress = ref(false);
const isSubmittingPassword = ref(false);

const showPassword = ref(false);
const profilePhoto = ref(null);
const removePhoto = ref(false);

const maxDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

onMounted(async () => {
  try {
    const response = await axios.get('/api/check_session.php', { withCredentials: true });
    const data = response.data;
    console.log('Session check response:', data);
    if (data.success) {
      isLoggedIn.value = true;
      Object.keys(data.user).forEach((key) => {
        if (key in userData) userData[key] = data.user[key] || '';
      });
    } else {
      isLoggedIn.value = false;
      router.push('/login');
    }
  } catch (error) {
    console.error('Session check failed:', {
      message: error.message,
      status: error.response?.status,
      data: error.response?.data,
    });
    isLoggedIn.value = false;
    router.push('/login');
  } finally {
    isLoading.value = false;
  }
});

const logout = async () => {
  if (!confirm('Are you sure you want to log out?')) return;
  try {
    const response = await axios.get('/api/logout.php', { withCredentials: true });
    if (response.data.success) {
      localStorage.setItem('logoutSuccess', 'true');
      router.push('/login');
    } else {
      errors.form = 'Logout failed. Please try again.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
      setTimeout(() => (showAlert.value = false), 5000);
    }
  } catch (error) {
    console.error('Logout error:', error);
    errors.form = 'Logout failed. Please try again.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
    setTimeout(() => (showAlert.value = false), 5000);
  }
};

const countries = [
  { code: 'MY', name: 'Malaysia' },
  { code: 'US', name: 'United States' },
  { code: 'CA', name: 'Canada' },
  { code: 'UK', name: 'United Kingdom' },
  { code: 'AU', name: 'Australia' },
  { code: 'JP', name: 'Japan' },
];

const profileForm = reactive({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  birthDate: '',
});

const addressForm = reactive({
  address: '',
  city: '',
  state: '',
  zipCode: '',
  country: '',
});

const passwordForm = reactive({
  currentPassword: '',
  newPassword: '',
  confirmPassword: '',
});

const startEditingProfile = () => {
  profileForm.firstName = userData.firstName || '';
  profileForm.lastName = userData.lastName || '';
  profileForm.email = userData.email || '';
  profileForm.phone = userData.phone || '';
  profileForm.birthDate = userData.birthDate || '';
  removePhoto.value = false;
  isEditingProfile.value = true;
  isEditingPassword.value = false;
};

const startEditingAddress = () => {
  addressForm.address = userData.address || '';
  addressForm.city = userData.city || '';
  addressForm.state = userData.state || '';
  addressForm.zipCode = userData.zipCode || '';
  addressForm.country = userData.country || '';
  isEditingAddress.value = true;
};

const startEditingPassword = () => {
  passwordForm.currentPassword = '';
  passwordForm.newPassword = '';
  passwordForm.confirmPassword = '';
  isEditingPassword.value = true;
};

const cancelEditingProfile = () => {
  isEditingProfile.value = false;
  isEditingPassword.value = false;
  profilePhoto.value = null;
  removePhoto.value = false;
  if (userData.profile_picture) userData.profile_picture = userData.profile_picture;
};

const cancelEditingAddress = () => {
  isEditingAddress.value = false;
};

const saveProfileChanges = async () => {
  try {
    isSubmittingProfile.value = true;
    const formData = new FormData();
    Object.keys(profileForm).forEach((key) => {
      formData.append(key, profileForm[key]);
    });
    if (profilePhoto.value) {
      formData.append('profile_photo', profilePhoto.value);
    }
    if (removePhoto.value) {
      formData.append('remove_photo', 'true');
    }
    if (isEditingPassword.value) {
      Object.keys(passwordForm).forEach((key) => {
        formData.append(key, passwordForm[key]);
      });
    }
    formData.append('action', 'update_profile');

    const response = await axios.post('/api/update_profile.php', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    const data = response.data;
    if (data.success) {
      Object.keys(profileForm).forEach((key) => {
        if (key in userData) userData[key] = profileForm[key];
      });
      userData.profile_picture = data.profile_picture || '';
      if (isEditingPassword.value) {
        errors.form = 'Profile and password updated successfully!';
      } else {
        errors.form = 'Profile updated successfully!';
      }
      alertType.value = 'alert-success';
      showAlert.value = true;
      setTimeout(() => (showAlert.value = false), 5000);
      isEditingProfile.value = false;
      isEditingPassword.value = false;
    } else {
      errors.form = data.error || 'Failed to update profile. Please try again.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
      setTimeout(() => (showAlert.value = false), 5000);
    }
  } catch (error) {
    console.error('Update error:', error);
    errors.form = 'Failed to update profile. Please try again.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
    setTimeout(() => (showAlert.value = false), 5000);
  } finally {
    isSubmittingProfile.value = false;
  }
};

const saveAddressChanges = async () => {
  try {
    isSubmittingAddress.value = true;
    const formData = new FormData();
    formData.append('address', addressForm.address);
    formData.append('city', addressForm.city);
    formData.append('state', addressForm.state);
    formData.append('zipCode', addressForm.zipCode);
    formData.append('country', addressForm.country);
    formData.append('action', 'update_address');

    const response = await axios.post('/api/update_profile.php', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
    });

    const data = response.data;
    if (data.success) {
      userData.address = addressForm.address;
      userData.city = addressForm.city;
      userData.state = addressForm.state;
      userData.zipCode = addressForm.zipCode;
      userData.country = addressForm.country;
      errors.form = 'Address updated successfully!';
      alertType.value = 'alert-success';
      showAlert.value = true;
      setTimeout(() => (showAlert.value = false), 5000);
      isEditingAddress.value = false;
    } else {
      errors.form = data.error || 'Failed to update address. Please try again.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
      setTimeout(() => (showAlert.value = false), 5000);
    }
  } catch (error) {
    console.error('Update error:', error);
    errors.form = 'Failed to update address. Please try again.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
    setTimeout(() => (showAlert.value = false), 5000);
  } finally {
    isSubmittingAddress.value = false;
  }
};

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const handlePhotoUpload = (event) => {
  profilePhoto.value = event.target.files[0];
  const reader = new FileReader();
  reader.onload = (e) => {
    userData.profile_picture = e.target.result;
  };
  reader.readAsDataURL(profilePhoto.value);
  removePhoto.value = false;
};

const removePhotoPreview = () => {
  removePhoto.value = true;
  userData.profile_picture = '';
};

const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const showDeleteModal = ref(false);

const openDeleteModal = () => {
  showDeleteModal.value = true;
};

const deleteAccount = async () => {
  try {
    const response = await axios.post('/api/delete_account.php', {}, { withCredentials: true });
    const data = response.data;
    if (data.success) {
      showDeleteModal.value = false;
      Object.keys(userData).forEach(key => userData[key] = '');
      isLoggedIn.value = false;
      sessionStorage.clear();
      localStorage.setItem('deleteSuccess', 'true');
      router.push('/login');
    } else {
      errors.form = data.error || 'Failed to delete account.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
      setTimeout(() => {
        showAlert.value = false;
      }, 5000);
    }
  } catch (error) {
    console.error('Delete account error:', error);
    errors.form = 'Failed to delete account.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
    setTimeout(() => {
      showAlert.value = false;
    }, 5000);
  }
};
</script>

<template>
  <div class="account-page">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>My Account</h1>
        <button @click="logout" class="btn btn-outline-danger">
          <i class="bi bi-box-arrow-right me-1"></i> Logout
        </button>
      </div>

      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <div v-else-if="!isLoggedIn" class="text-center py-5">
        <p>
          You are not logged in. Please <router-link to="/login">login</router-link> to view your account.
        </p>
      </div>

      <div v-else>
        <div v-if="showAlert && errors.form" :class="['alert', alertType, 'fade', 'show', 'mb-3']" role="alert">
          {{ errors.form }}
        </div>

        <div class="full-width-container">
          <div class="section-container">
            <div class="section-header">
              <h2 class="section-title">Profile Information</h2>
              <button v-if="!isEditingProfile" class="btn btn-outline-dark btn-sm view-all" @click="startEditingProfile">
                <i class="bi bi-pencil me-1"></i> Edit Profile
              </button>
            </div>

            <div v-if="!isEditingProfile" class="card account-card">
              <div class="card-body">
                <div class="row">
                  <div class="col-md-3 text-center mb-4 mb-md-0">
                    <div class="avatar-wrapper">
                      <img v-if="userData.profile_picture" :src="userData.profile_picture" class="profile-img" alt="Profile">
                      <div v-else class="placeholder-img profile-img">profile</div>
                    </div>
                  </div>
                  <div class="col-md-9">
                    <div class="row mb-3">
                      <div class="col-md-6 info-group">
                        <p class="info-label">Name</p>
                        <p class="info-value">{{ userData.firstName }} {{ userData.lastName }}</p>
                      </div>
                      <div class="col-md-6 info-group">
                        <p class="info-label">Email</p>
                        <p class="info-value">{{ userData.email }}</p>
                      </div>
                    </div>
                    <div class="row mb-3">
                      <div class="col-md-6 info-group">
                        <p class="info-label">Phone</p>
                        <p class="info-value">{{ userData.phone || 'Not provided' }}</p>
                      </div>
                      <div class="col-md-6 info-group">
                        <p class="info-label">Birth Date</p>
                        <p class="info-value">{{ userData.birthDate ? formatDate(userData.birthDate) : 'Not provided' }}</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="isEditingProfile" class="card account-card">
              <div class="card-body">
                <form @submit.prevent="saveProfileChanges" novalidate>
                  <div class="row">
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                      <div class="avatar-wrapper">
                        <img v-if="userData.profile_picture && !removePhoto" :src="userData.profile_picture" class="profile-img" alt="Profile">
                        <div v-if="!userData.profile_picture || removePhoto" class="placeholder-img profile-img">profile</div>
                        <div class="mt-3">
                          <label for="profile-photo-upload" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-upload me-1"></i> Upload Photo
                          </label>
                          <input 
                            type="file" 
                            id="profile-photo-upload"
                            class="form-control visually-hidden" 
                            @change="handlePhotoUpload" 
                            accept="image/*"
                          >
                          <button 
                            v-if="userData.profile_picture && !removePhoto" 
                            type="button" 
                            class="btn btn-outline-danger btn-sm ms-2" 
                            @click="removePhotoPreview"
                          >
                            <i class="bi bi-trash me-1"></i> Remove
                          </button>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-9">
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <label for="firstName" class="form-label">First Name</label>
                          <input type="text" class="form-control" id="firstName" v-model="profileForm.firstName" required>
                        </div>
                        <div class="col-md-6">
                          <label for="lastName" class="form-label">Last Name</label>
                          <input type="text" class="form-control" id="lastName" v-model="profileForm.lastName" required>
                        </div>
                      </div>
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <label for="email" class="form-label">Email</label>
                          <input type="email" class="form-control" id="email" v-model="profileForm.email" required disabled>
                        </div>
                        <div class="col-md-6">
                          <label for="phone" class="form-label">Phone</label>
                          <input type="tel" class="form-control" id="phone" v-model="profileForm.phone">
                        </div>
                      </div>
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <label for="birthDate" class="form-label">Birth Date</label>
                          <input type="date" class="form-control" id="birthDate" v-model="profileForm.birthDate" :max="maxDate">
                        </div>
                      </div>
                      <div class="row mb-3">
                        <div class="col-md-6">
                          <button type="button" class="btn btn-outline-dark btn-sm" @click="startEditingPassword">
                            <i class="bi bi-pencil me-1"></i> Change Password
                          </button>
                        </div>
                      </div>
                      <div v-if="isEditingPassword" class="row mb-3">
                        <div class="col-md-6">
                          <label for="currentPassword" class="form-label">Current Password</label>
                          <div class="input-group">
                            <input
                              :type="showPassword ? 'text' : 'password'"
                              class="form-control"
                              id="currentPassword"
                              v-model="passwordForm.currentPassword"
                              required
                            >
                            <button
                              class="btn btn-outline-secondary"
                              type="button"
                              @click="togglePasswordVisibility"
                            >
                              <i :class="showPassword ? 'bi bi-eye-slash' : 'bi bi-eye'"></i>
                            </button>
                          </div>
                        </div>
                      </div>
                      <div v-if="isEditingPassword" class="row mb-3">
                        <div class="col-md-6">
                          <label for="newPassword" class="form-label">New Password</label>
                          <input
                            :type="showPassword ? 'text' : 'password'"
                            class="form-control"
                            id="newPassword"
                            v-model="passwordForm.newPassword"
                            required
                          >
                        </div>
                      </div>
                      <div v-if="isEditingPassword" class="row mb-3">
                        <div class="col-md-6">
                          <label for="confirmPassword" class="form-label">Confirm New Password</label>
                          <input
                            :type="showPassword ? 'text' : 'password'"
                            class="form-control"
                            id="confirmPassword"
                            v-model="passwordForm.confirmPassword"
                            required
                          >
                        </div>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex justify-content-end mt-3">
                    <button v-if="isEditingPassword" type="button" class="btn btn-outline-secondary me-2" @click="isEditingPassword = false">Cancel Password</button>
                    <button type="button" class="btn btn-outline-secondary me-2" @click="cancelEditingProfile">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="isSubmittingProfile">
                      <span v-if="isSubmittingProfile" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                      Save Changes
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="full-width-container bg-light">
          <div class="section-container">
            <div class="section-header">
              <h2 class="section-title">Address Information</h2>
              <button v-if="!isEditingAddress" class="btn btn-outline-dark btn-sm view-all" @click="startEditingAddress">
                <i class="bi bi-pencil me-1"></i> Edit Address
              </button>
            </div>

            <div v-if="!isEditingAddress" class="card account-card">
              <div class="card-body">
                <div class="row mb-3">
                  <div class="col-12 info-group">
                    <p class="info-label">Address</p>
                    <p class="info-value">{{ userData.address || 'Not provided' }}</p>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4 info-group">
                    <p class="info-label">City</p>
                    <p class="info-value">{{ userData.city || 'Not provided' }}</p>
                  </div>
                  <div class="col-md-4 info-group">
                    <p class="info-label">State</p>
                    <p class="info-value">{{ userData.state || 'Not provided' }}</p>
                  </div>
                  <div class="col-md-4 info-group">
                    <p class="info-label">Zip Code</p>
                    <p class="info-value">{{ userData.zipCode || 'Not provided' }}</p>
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-4 info-group">
                    <p class="info-label">Country</p>
                    <p class="info-value">{{ countries.find((c) => c.code === userData.country)?.name || 'Not provided' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="isEditingAddress" class="card account-card">
              <div class="card-body">
                <form @submit.prevent="saveAddressChanges" novalidate>
                  <div class="row mb-3">
                    <div class="col-12">
                      <label for="address" class="form-label">Address</label>
                      <input type="text" class="form-control" id="address" v-model="addressForm.address" required>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label for="city" class="form-label">City</label>
                      <input type="text" class="form-control" id="city" v-model="addressForm.city" required>
                    </div>
                    <div class="col-md-4">
                      <label for="state" class="form-label">State</label>
                      <input type="text" class="form-control" id="state" v-model="addressForm.state" required>
                    </div>
                    <div class="col-md-4">
                      <label for="zipCode" class="form-label">Zip Code</label>
                      <input type="text" class="form-control" id="zipCode" v-model="addressForm.zipCode" required>
                    </div>
                  </div>
                  <div class="row mb-3">
                    <div class="col-md-4">
                      <label for="country" class="form-label">Country</label>
                      <select class="form-select" id="country" v-model="addressForm.country" required>
                        <option v-for="country in countries" :key="country.code" :value="country.code">
                          {{ country.name }}
                        </option>
                      </select>
                    </div>
                  </div>
                  
                  <div class="d-flex justify-content-end mt-3">
                    <button type="button" class="btn btn-outline-secondary me-2" @click="cancelEditingAddress">Cancel</button>
                    <button type="submit" class="btn btn-primary" :disabled="isSubmittingAddress">
                      <span v-if="isSubmittingAddress" class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                      Save Changes
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>

        <div class="full-width-container bg-light">
          <div class="section-container">
            <div class="section-header">
              <h2 class="section-title">Danger Zone</h2>
            </div>
            <div class="card account-card border-danger">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <h5 class="mb-1">Delete Account</h5>
                    <p class="mb-0 text-muted">Permanently delete your account and all associated data.</p>
                  </div>
                  <button class="btn btn-danger" @click="openDeleteModal">
                    <i class="bi bi-trash me-1"></i> Delete Account
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal fade" :class="{ show: showDeleteModal }" tabindex="-1" aria-labelledby="deleteModalLabel" style="display: block;" v-if="showDeleteModal">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Account Deletion</h5>
                <button type="button" class="btn-close" @click="showDeleteModal = false" aria-label="Close"></button>
              </div>
              <div class="modal-body">
                <p>Are you sure you want to delete your account? This action cannot be undone and will remove all your data, including purchase history and cart items.</p>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" @click="showDeleteModal = false">Cancel</button>
                <button type="button" class="btn btn-danger" @click="deleteAccount">Delete Account</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>