<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import '../assets/css/account.css';

const router = useRouter();

// User data state - will be populated from API
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
  profile_picture: '', // For storing profile picture from database
});

// Check if user is logged in
const isLoggedIn = ref(false);
const isLoading = ref(true);
const errors = ref('');
const alertType = ref('alert-success');
const showAlert = ref(false);

// Separate edit modes for each section
const isEditingProfile = ref(false);
const isEditingAddress = ref(false);
const isEditingPassword = ref(false);

// Submission states
const isSubmittingProfile = ref(false);
const isSubmittingAddress = ref(false);
const isSubmittingPassword = ref(false);

// Other state variables
const showPassword = ref(false);
const profilePhoto = ref(null);
const removePhoto = ref(false);

// Maximum date for birth date (today)
const maxDate = computed(() => {
  const today = new Date();
  return today.toISOString().split('T')[0];
});

// Fetch user profile data on component mount
onMounted(async () => {
  try {
    // Check for login success message
    const loginSuccess = localStorage.getItem('loginSuccess');
    if (loginSuccess) {
      errors.value = 'Login successful! Welcome to your account.';
      alertType.value = 'alert-success';
      showAlert.value = true;
      localStorage.removeItem('loginSuccess');
    }
    
    const response = await fetch('/api/get_profile.php');
    const data = await response.json();

    if (data.error) {
      isLoggedIn.value = false;
      router.push('/login');
    } else {
      isLoggedIn.value = true;
      // Update userData with fetched data
      Object.keys(data.user).forEach((key) => {
        if (key in userData) {
          userData[key] = data.user[key] || '';
        }
      });
    }
  } catch (error) {
    console.error('Failed to fetch profile:', error);
    isLoggedIn.value = false;
  } finally {
    isLoading.value = false;
  }
});

// Logout function with confirmation
const logout = async () => {
  // Show confirmation dialog
  if (!confirm('Are you sure you want to log out?')) {
    return; // User canceled logout
  }
  
  try {
    const response = await fetch('/api/logout.php');
    const data = await response.json();

    if (data.success) {
      router.push('/login'); // Immediate redirect without delay
    } else {
      errors.value = 'Logout failed. Please try again.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
    }
  } catch (error) {
    console.error('Logout error:', error);
    errors.value = 'Logout failed. Please try again.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
  }
};

// Countries list for dropdown
const countries = [
  { code: 'MY', name: 'Malaysia' },
  { code: 'US', name: 'United States' },
  { code: 'CA', name: 'Canada' },
  { code: 'UK', name: 'United Kingdom' },
  { code: 'AU', name: 'Australia' },
  { code: 'JP', name: 'Japan' },
];

// Forms for editing each section
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

// Start editing profile
const startEditingProfile = () => {
  profileForm.firstName = userData.firstName || '';
  profileForm.lastName = userData.lastName || '';
  profileForm.email = userData.email || '';
  profileForm.phone = userData.phone || '';
  profileForm.birthDate = userData.birthDate || '';
  removePhoto.value = false;
  isEditingProfile.value = true;
};

// Start editing address
const startEditingAddress = () => {
  addressForm.address = userData.address || '';
  addressForm.city = userData.city || '';
  addressForm.state = userData.state || '';
  addressForm.zipCode = userData.zipCode || '';
  addressForm.country = userData.country || '';
  isEditingAddress.value = true;
};

// Start editing password
const startEditingPassword = () => {
  passwordForm.currentPassword = '';
  passwordForm.newPassword = '';
  passwordForm.confirmPassword = '';
  isEditingPassword.value = true;
};

// Cancel editing functions
const cancelEditingProfile = () => {
  isEditingProfile.value = false;
  profilePhoto.value = null;
  removePhoto.value = false;
};

const cancelEditingAddress = () => {
  isEditingAddress.value = false;
};

const cancelEditingPassword = () => {
  isEditingPassword.value = false;
};

// Save profile changes
const saveProfileChanges = async () => {
  try {
    isSubmittingProfile.value = true;
    const formData = new FormData();
    
    // Add profile form data
    Object.keys(profileForm).forEach((key) => {
      formData.append(key, profileForm[key]);
    });
    
    if (profilePhoto.value) {
      formData.append('profile_photo', profilePhoto.value);
    }
    if (removePhoto.value) {
      formData.append('remove_photo', 'true');
    }

    const response = await fetch('/api/update_profile.php', {
      method: 'POST',
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      // Update userData with new values
      Object.keys(profileForm).forEach((key) => {
        if (key in userData) {
          userData[key] = profileForm[key];
        }
      });
      userData.profile_picture = data.profile_picture || '';
      
      errors.value = 'Profile updated successfully!';
      alertType.value = 'alert-success';
      showAlert.value = true;
      isEditingProfile.value = false;
    } else {
      errors.value = data.error || 'Failed to update profile. Please try again.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
    }
  } catch (error) {
    console.error('Update error:', error);
    errors.value = 'Failed to update profile. Please try again.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
  } finally {
    isSubmittingProfile.value = false;
  }
};

// Save address changes
const saveAddressChanges = async () => {
  try {
    isSubmittingAddress.value = true;
    const formData = new FormData();
    
    // Add address form data
    Object.keys(addressForm).forEach((key) => {
      formData.append(key, addressForm[key]);
    });

    const response = await fetch('/api/update_profile.php', {
      method: 'POST',
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      // Update userData with new values
      Object.keys(addressForm).forEach((key) => {
        if (key in userData) {
          userData[key] = addressForm[key];
        }
      });
      
      errors.value = 'Address updated successfully!';
      alertType.value = 'alert-success';
      showAlert.value = true;
      isEditingAddress.value = false;
    } else {
      errors.value = data.error || 'Failed to update address. Please try again.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
    }
  } catch (error) {
    console.error('Update error:', error);
    errors.value = 'Failed to update address. Please try again.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
  } finally {
    isSubmittingAddress.value = false;
  }
};

// Save password changes
const savePasswordChanges = async () => {
  try {
    isSubmittingPassword.value = true;
    const formData = new FormData();
    
    // Add password form data
    Object.keys(passwordForm).forEach((key) => {
      formData.append(key, passwordForm[key]);
    });

    const response = await fetch('/api/update_profile.php', {
      method: 'POST',
      body: formData,
    });

    const data = await response.json();

    if (data.success) {
      errors.value = 'Password updated successfully!';
      alertType.value = 'alert-success';
      showAlert.value = true;
      isEditingPassword.value = false;
    } else {
      errors.value = data.error || 'Failed to update password. Please try again.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
    }
  } catch (error) {
    console.error('Update error:', error);
    errors.value = 'Failed to update password. Please try again.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
  } finally {
    isSubmittingPassword.value = false;
  }
};

const togglePasswordVisibility = () => {
  showPassword.value = !showPassword.value;
};

const handlePhotoUpload = (event) => {
  profilePhoto.value = event.target.files[0];
  // Preview the image
  const reader = new FileReader();
  reader.onload = (e) => {
    userData.profile_picture = e.target.result; // Base64 for preview
  };
  reader.readAsDataURL(profilePhoto.value);
  removePhoto.value = false; // Reset remove flag when uploading new photo
};

// Add formatDate function
const formatDate = (dateString) => {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
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

      <!-- Loading state -->
      <div v-if="isLoading" class="text-center py-5">
        <div class="spinner-border" role="status">
          <span class="visually-hidden">Loading...</span>
        </div>
      </div>

      <!-- Not logged in state -->
      <div v-else-if="!isLoggedIn" class="text-center py-5">
        <p>
          You are not logged in. Please <router-link to="/login">login</router-link> to view your account.
        </p>
      </div>

      <!-- Logged in state -->
      <div v-else>
        <div v-if="showAlert && errors.value" :class="['alert', alertType]" class="mb-3">
          {{ errors.value }}
        </div>

        <!-- Profile Section -->
        <div class="full-width-container">
          <div class="section-container">
            <div class="section-header">
              <h2 class="section-title">Profile Information</h2>
              <button v-if="!isEditingProfile" class="btn btn-outline-dark btn-sm view-all" @click="startEditingProfile">
                <i class="bi bi-pencil me-1"></i> Edit Profile
              </button>
            </div>

            <!-- View Mode -->
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

            <!-- Edit Mode -->
            <div v-else class="card account-card">
              <div class="card-body">
                <form @submit.prevent="saveProfileChanges" novalidate>
                  <div class="row">
                    <div class="col-md-3 text-center mb-4 mb-md-0">
                      <!-- Replace the avatar-wrapper div in edit mode with this improved version -->
                      <div class="avatar-wrapper">
                        <img v-if="userData.profile_picture" :src="userData.profile_picture" class="profile-img" alt="Profile">
                        <div v-else class="placeholder-img profile-img">profile</div>
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
                            v-if="userData.profile_picture" 
                            type="button" 
                            class="btn btn-outline-danger btn-sm ms-2" 
                            @click="removePhoto = true"
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
                    </div>
                  </div>

                  <div class="d-flex justify-content-end mt-3">
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

        <!-- Address Section -->
        <div class="full-width-container bg-light">
          <div class="section-container">
            <div class="section-header">
              <h2 class="section-title">Address Information</h2>
              <button v-if="!isEditingAddress" class="btn btn-outline-dark btn-sm view-all" @click="startEditingAddress">
                <i class="bi bi-pencil me-1"></i> Edit Address
              </button>
            </div>

            <!-- View Mode -->
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

            <!-- Edit Mode (Address) -->
            <div v-else class="card account-card">
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

        <!-- Security Section -->
        <div class="full-width-container bg-light">
          <div class="section-container">
            <div class="section-header">
              <h2 class="section-title">Security</h2>
              <button v-if="!isEditingPassword" class="btn btn-outline-dark btn-sm view-all" @click="startEditingPassword">
                <i class="bi bi-pencil me-1"></i> Change Password
              </button>
            </div>

            <!-- View Mode -->
            <div v-if="!isEditingPassword" class="card account-card">
              <div class="card-body">
                <p class="mb-0">Password: ••••••••</p>
              </div>
            </div>

            <!-- Edit Mode (Password) -->
            <div v-else class="card account-card">
              <div class="card-body">
                <form @submit.prevent="savePasswordChanges" novalidate>
                  <div class="row mb-3">
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
                  <div class="row mb-3">
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
                  <div class="row mb-3">
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
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>