<template>
    <div class="space-y-6">
      <!-- Header with Actions -->
      <div class="flex justify-between items-center">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">Users</h2>
          <p class="mt-1 text-sm text-gray-500">Manage your system users</p>
        </div>
        <button
          class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
          @click="showCreateModal = true"
        >
          <i class="fas fa-plus mr-2"></i> Add User
        </button>
      </div>

      <!-- Filters and Search -->
      <div class="bg-white p-4 rounded-lg shadow">
        <div class="flex flex-col sm:flex-row gap-4">
          <div class="flex-1">
            <div class="relative">
              <input
                type="text"
                v-model="search"
                placeholder="Search users..."
                class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
              <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
            </div>
          </div>
          <div class="flex gap-4">
            <select
              v-model="roleFilter"
              class="rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">All Roles</option>
              <option value="admin">Admin</option>
              <option value="user">User</option>
              <option value="editor">Editor</option>
            </select>
            <select
              v-model="statusFilter"
              class="rounded-lg border border-gray-300 px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Users Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th
                v-for="header in tableHeaders"
                :key="header"
                class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
              >
                {{ header }}
              </th>
              <th class="relative px-6 py-3">
                <span class="sr-only">Actions</span>
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="user in filteredUsers" :key="user.id">
              <td class="px-6 py-4 whitespace-nowrap">
                <div class="flex items-center">
                  <div class="flex-shrink-0 h-10 w-10">
                    <img
                      :src="user.avatar"
                      :alt="user.name"
                      class="h-10 w-10 rounded-full"
                    />
                  </div>
                  <div class="ml-4">
                    <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                    <div class="text-sm text-gray-500">{{ user.email }}</div>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 whitespace-nowrap">
                <span
                  class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                  :class="{
                    'bg-green-100 text-green-800': user.status === 'active',
                    'bg-red-100 text-red-800': user.status === 'inactive'
                  }"
                >
                  {{ user.status }}
                </span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ user.role }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                {{ user.lastLogin }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                <button
                  class="text-blue-600 hover:text-blue-900 mr-4"
                  @click="editUser(user)"
                >
                  Edit
                </button>
                <button
                  class="text-red-600 hover:text-red-900"
                  @click="deleteUser(user)"
                >
                  Delete
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Confirmation Dialog -->
      <ConfirmDialog
        :is-open="showDeleteConfirm"
        title="Delete User"
        message="Are you sure you want to delete this user? This action cannot be undone."
        @close="showDeleteConfirm = false"
        @confirm="confirmDelete"
      />
    </div>
  </template>

  <script setup>
  import { ref, computed } from 'vue';
  import ConfirmDialog from '@/Components/ConfirmDialog.vue';

  defineOptions({
    layout: null  // Layout will be set globally for admin routes
  })

  const search = ref('');
  const roleFilter = ref('');
  const statusFilter = ref('');
  const showDeleteConfirm = ref(false);
  const showCreateModal = ref(false);
  const selectedUser = ref(null);

  const tableHeaders = ['User', 'Status', 'Role', 'Last Login', ''];

  const users = [
    {
      id: 1,
      name: 'John Doe',
      email: 'john@example.com',
      avatar: '/default-avatar.png',
      status: 'active',
      role: 'admin',
      lastLogin: '2 hours ago'
    },
    {
      id: 2,
      name: 'Jane Smith',
      email: 'jane@example.com',
      avatar: '/default-avatar.png',
      status: 'active',
      role: 'user',
      lastLogin: '1 day ago'
    },
    {
      id: 3,
      name: 'Mike Johnson',
      email: 'mike@example.com',
      avatar: '/default-avatar.png',
      status: 'inactive',
      role: 'editor',
      lastLogin: '1 week ago'
    }
  ];

  const filteredUsers = computed(() => {
    return users.filter(user => {
      const matchesSearch = search.value === '' ||
        user.name.toLowerCase().includes(search.value.toLowerCase()) ||
        user.email.toLowerCase().includes(search.value.toLowerCase());
      const matchesRole = roleFilter.value === '' || user.role === roleFilter.value;
      const matchesStatus = statusFilter.value === '' || user.status === statusFilter.value;
      return matchesSearch && matchesRole && matchesStatus;
    });
  });

  const editUser = (user) => {
    selectedUser.value = user;
    // Add edit logic
  };

  const deleteUser = (user) => {
    selectedUser.value = user;
    showDeleteConfirm.value = true;
  };

  const confirmDelete = () => {
    // Add delete logic
    showDeleteConfirm.value = false;
    selectedUser.value = null;
  };
  </script>
