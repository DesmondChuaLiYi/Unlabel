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

// Alert system
const errors = reactive({
  form: '',
});
const alertType = ref('alert-danger');
const showAlert = ref(false);

// Check if user is logged in
const isLoggedIn = ref(false);
const isLoading = ref(true);

// Edit modes
const isEditingProfile = ref(false);
const isEditingPassword = ref(false); // Toggle for password fields within profile edit
const isEditingAddress = ref(false); // Manage address edit mode

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
    console.log('Mounting component, checking login state');
    const loginSuccess = localStorage.getItem('loginSuccess');
    if (loginSuccess) {
      errors.form = 'Login successful! Welcome to your account.';
      alertType.value = 'alert-success';
      showAlert.value = true;
      setTimeout(() => {
        showAlert.value = false;
        localStorage.removeItem('loginSuccess');
      }, 5000);
    }
    
    const response = await fetch('/api/get_profile.php');
    const data = await response.json();

    if (data.error) {
      isLoggedIn.value = false;
      router.push('/login');
    } else {
      isLoggedIn.value = true;
      Object.keys(data.user).forEach((key) => {
        if (key in userData) {
          userData[key] = data.user[key] || '';
        }
      });
      console.log('User data fetched:', userData);
    }
  } catch (error) {
    console.error('Failed to fetch profile:', error);
    isLoggedIn.value = false;
  } finally {
    isLoading.value = false;
  }
});

// Logout function with confirmation
// Inside <script setup>
const logout = async () => {
  console.log('Logout initiated');
  if (!confirm('Are you sure you want to log out?')) {
    console.log('Logout canceled by user');
    return;
  }
  
  try {
    const response = await fetch('/api/logout.php');
    const data = await response.json();

    if (data.success) {
      console.log('Logout successful, redirecting to login');
      localStorage.setItem('logoutSuccess', 'true'); // Set logout success flag
      router.push('/login');
    } else {
      errors.form = 'Logout failed. Please try again.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
      setTimeout(() => {
        showAlert.value = false;
      }, 5000);
    }
  } catch (error) {
    console.error('Logout error:', error);
    errors.form = 'Logout failed. Please try again.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
    setTimeout(() => {
      showAlert.value = false;
    }, 5000);
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
  console.log('Starting profile edit');
  profileForm.firstName = userData.firstName || '';
  profileForm.lastName = userData.lastName || '';
  profileForm.email = userData.email || '';
  profileForm.phone = userData.phone || '';
  profileForm.birthDate = userData.birthDate || '';
  removePhoto.value = false;
  isEditingProfile.value = true;
  isEditingPassword.value = false; // Reset password edit state
};

// Start editing address
const startEditingAddress = () => {
  console.log('Starting address edit');
  addressForm.address = userData.address || '';
  addressForm.city = userData.city || '';
  addressForm.state = userData.state || '';
  addressForm.zipCode = userData.zipCode || '';
  addressForm.country = userData.country || '';
  isEditingAddress.value = true;
};

// Start editing password within profile
const startEditingPassword = () => {
  console.log('Starting password edit within profile');
  passwordForm.currentPassword = '';
  passwordForm.newPassword = '';
  passwordForm.confirmPassword = '';
  isEditingPassword.value = true;
};

// Cancel editing functions
const cancelEditingProfile = () => {
  console.log('Canceling profile edit');
  isEditingProfile.value = false;
  isEditingPassword.value = false;
  profilePhoto.value = null;
  removePhoto.value = false;
  if (userData.profile_picture) userData.profile_picture = userData.profile_picture; // Restore original if canceled
};

const cancelEditingAddress = () => {
  console.log('Canceling address edit');
  isEditingAddress.value = false;
};

// Save profile changes
const saveProfileChanges = async () => {
  console.log('Saving profile changes');
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
    formData.append('action', 'update_profile'); // Indicate profile update

    const response = await fetch('/api/update_profile.php', {
      method: 'POST',
      body: formData,
    });

    const text = await response.text();
    console.log('Raw response:', text);
    const data = text ? JSON.parse(text) : {};

    if (data.success) {
      Object.keys(profileForm).forEach((key) => {
        if (key in userData) {
          userData[key] = profileForm[key];
        }
      });
      userData.profile_picture = data.profile_picture || '';
      
      if (isEditingPassword.value) {
        errors.form = 'Profile and password updated successfully!';
      } else {
        errors.form = 'Profile updated successfully!';
      }
      alertType.value = 'alert-success';
      showAlert.value = true;
      setTimeout(() => {
        showAlert.value = false;
      }, 5000);
      isEditingProfile.value = false;
      isEditingPassword.value = false;
    } else {
      errors.form = data.error || 'Failed to update profile. Please try again.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
      setTimeout(() => {
        showAlert.value = false;
      }, 5000);
    }
  } catch (error) {
    console.error('Update error:', error);
    errors.form = 'Failed to update profile. Please try again.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
    setTimeout(() => {
      showAlert.value = false;
    }, 5000);
  } finally {
    isSubmittingProfile.value = false;
  }
};

// Save address changes
const saveAddressChanges = async () => {
  console.log('Saving address changes');
  try {
    isSubmittingAddress.value = true;
    const formData = new FormData();
    
    // Only append address-related fields
    formData.append('address', addressForm.address);
    formData.append('city', addressForm.city);
    formData.append('state', addressForm.state);
    formData.append('zipCode', addressForm.zipCode);
    formData.append('country', addressForm.country);
    formData.append('action', 'update_address'); // Indicate address update

    const response = await fetch('/api/update_profile.php', {
      method: 'POST',
      body: formData,
    });

    const text = await response.text();
    console.log('Raw response:', text);
    // Extract JSON from response, ignoring preceding HTML
    const jsonStart = text.indexOf('{');
    const data = jsonStart !== -1 ? JSON.parse(text.slice(jsonStart)) : {};

    if (data.success) {
      // Update only address fields in userData, preserving other data
      userData.address = addressForm.address;
      userData.city = addressForm.city;
      userData.state = addressForm.state;
      userData.zipCode = addressForm.zipCode;
      userData.country = addressForm.country;
      
      errors.form = 'Address updated successfully!';
      alertType.value = 'alert-success';
      showAlert.value = true;
      setTimeout(() => {
        showAlert.value = false;
      }, 5000);
      isEditingAddress.value = false;
    } else {
      errors.form = data.error || 'Failed to update address. Please try again.';
      alertType.value = 'alert-danger';
      showAlert.value = true;
      setTimeout(() => {
        showAlert.value = false;
      }, 5000);
    }
  } catch (error) {
    console.error('Update error:', error);
    errors.form = 'Failed to update address. Please try again.';
    alertType.value = 'alert-danger';
    showAlert.value = true;
    setTimeout(() => {
      showAlert.value = false;
    }, 5000);
  } finally {
    isSubmittingAddress.value = false;
  }
};

const togglePasswordVisibility = () => {
  console.log('Toggling password visibility');
  showPassword.value = !showPassword.value;
};

const handlePhotoUpload = (event) => {
  console.log('Handling photo upload:', event.target.files);
  profilePhoto.value = event.target.files[0];
  const reader = new FileReader();
  reader.onload = (e) => {
    userData.profile_picture = e.target.result; // Update preview immediately
  };
  reader.readAsDataURL(profilePhoto.value);
  removePhoto.value = false;
};

// Remove profile picture preview
const removePhotoPreview = () => {
  console.log('Removing profile picture preview');
  removePhoto.value = true;
  userData.profile_picture = ''; // Clear immediately for preview
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

// Delete account modal state
const showDeleteModal = ref(false);

// Open delete modal
const openDeleteModal = () => {
  console.log('Opening delete modal');
  showDeleteModal.value = true;
};

// Delete account
const deleteAccount = async () => {
  console.log('Deleting account');
  try {
    const response = await fetch('/api/delete_account.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
    });
    const data = await response.json();
    console.log('Delete response:', data); // Debug response
    if (data.success) {
      console.log('Account deletion successful');
      showDeleteModal.value = false;
      // Clear user data and redirect
      Object.keys(userData).forEach(key => userData[key] = '');
      isLoggedIn.value = false;
      sessionStorage.clear(); // Clear session storage
      localStorage.setItem('deleteSuccess', 'true'); // Set delete success flag
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
        <div v-if="showAlert && errors.form" :class="['alert', alertType, 'fade', 'show', 'mb-3']" role="alert">
          {{ errors.form }}
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

        <!-- Danger Zone Section -->
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

        <!-- Delete Confirmation Modal -->
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