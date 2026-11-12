<template>
    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white leading-tight">
                        User Management
                    </h2>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Manage system staff, roles, credentials and account access
                    </p>
                </div>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <PlusIcon class="w-5 h-5 mr-1.5" />
                    Add User
                </button>
            </div>
        </template>

        <div class="py-4 space-y-6">
            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Users -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Total Accounts
                            </p>
                            <p class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">
                                {{ summary.total || 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl text-indigo-600 dark:text-indigo-400">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Registered system users
                    </div>
                </div>

                <!-- Active Users -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Active Users
                            </p>
                            <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {{ summary.active || 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-emerald-50 dark:bg-emerald-900/30 rounded-xl text-emerald-600 dark:text-emerald-400">
                            <i class="fas fa-user-check text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Authorized to log in
                    </div>
                </div>

                <!-- Inactive Users -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                Inactive Users
                            </p>
                            <p class="mt-2 text-2xl font-bold text-red-600 dark:text-red-400">
                                {{ summary.inactive || 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-red-50 dark:bg-red-900/30 rounded-xl text-red-600 dark:text-red-400">
                            <i class="fas fa-user-slash text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Suspended accounts
                    </div>
                </div>

                <!-- System Roles -->
                <div class="bg-white dark:bg-gray-800 rounded-xl p-5 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                                System Roles
                            </p>
                            <p class="mt-2 text-2xl font-bold text-purple-600 dark:text-purple-400">
                                {{ summary.roles_count || 0 }}
                            </p>
                        </div>
                        <div class="p-3 bg-purple-50 dark:bg-purple-900/30 rounded-xl text-purple-600 dark:text-purple-400">
                            <i class="fas fa-user-shield text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                        Permission groups
                    </div>
                </div>
            </div>

            <!-- Filters Toolbar -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 p-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <!-- Search Input -->
                    <div class="lg:col-span-6">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Search</label>
                        <div class="relative">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                            <input
                                v-model="filterForm.search"
                                type="text"
                                placeholder="Search by name, username, email or phone..."
                                class="w-full pl-8 pr-3 py-2 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                                @input="debouncedFilter"
                            />
                        </div>
                    </div>

                    <!-- Role Filter -->
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Role</label>
                        <select
                            v-model="filterForm.role_id"
                            @change="applyFilters"
                            class="w-full py-2 px-3 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">All Roles</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">
                                {{ role.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Status Filter -->
                    <div class="lg:col-span-3">
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-300 mb-1">Status</label>
                        <div class="flex items-center gap-2">
                            <select
                                v-model="filterForm.status"
                                @change="applyFilters"
                                class="w-full py-2 px-3 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            >
                                <option value="">All Statuses</option>
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                            <button
                                v-if="hasActiveFilters"
                                @click="resetFilters"
                                title="Reset filters"
                                class="p-2 text-gray-400 hover:text-red-500 dark:hover:text-red-400 transition-colors"
                            >
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Card -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-left">
                        <thead class="bg-gray-50/75 dark:bg-gray-700/50">
                            <tr>
                                <th scope="col" class="py-3.5 pl-4 pr-3 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    User
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Username (Login)
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Contact Info
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Role
                                </th>
                                <th scope="col" class="px-3 py-3.5 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="py-3.5 pl-3 pr-4 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700/60 bg-white dark:bg-gray-800">
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="hover:bg-gray-50/80 dark:hover:bg-gray-750 transition-colors"
                            >
                                <!-- User Name & Avatar -->
                                <td class="whitespace-nowrap py-3.5 pl-4 pr-3 text-xs">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-indigo-500 to-purple-600 text-white flex items-center justify-center font-bold text-xs uppercase shadow-sm">
                                            {{ getInitials(user.name) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-gray-900 dark:text-white flex items-center gap-1.5">
                                                <span>{{ user.name }}</span>
                                                <span v-if="user.id === currentAuthUser?.id" class="text-[10px] bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 px-1.5 py-0.2 rounded font-normal">
                                                    You
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-gray-400 dark:text-gray-500">
                                                Joined {{ formatDate(user.created_at) }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Username -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-xs font-mono">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 text-xs font-semibold">
                                        <i class="fas fa-at text-[10px] mr-1 text-gray-400"></i>
                                        {{ user.username || '—' }}
                                    </span>
                                </td>

                                <!-- Contact Info -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-xs text-gray-600 dark:text-gray-300">
                                    <div class="space-y-0.5">
                                        <div v-if="user.email" class="flex items-center gap-1.5 text-gray-700 dark:text-gray-300">
                                            <i class="far fa-envelope text-gray-400 text-[11px]"></i>
                                            <span>{{ user.email }}</span>
                                        </div>
                                        <div v-if="user.phone" class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400 text-[11px]">
                                            <i class="fas fa-phone text-gray-400 text-[10px]"></i>
                                            <span>{{ user.phone }}</span>
                                        </div>
                                        <div v-if="!user.email && !user.phone" class="text-gray-400">
                                            —
                                        </div>
                                    </div>
                                </td>

                                <!-- Role -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-xs">
                                    <span
                                        :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                                            isRoleSuper(user.role)
                                                ? 'bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'
                                                : isRoleAdmin(user.role)
                                                ? 'bg-purple-50 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300'
                                                : 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'
                                        ]"
                                    >
                                        <i
                                            :class="isRoleSuper(user.role) ? 'fas fa-crown text-[10px] mr-1 text-amber-500' : 'fas fa-shield-alt text-[10px] mr-1'"
                                        ></i>
                                        {{ user.role?.name || 'Staff' }}
                                    </span>
                                </td>

                                <!-- Status -->
                                <td class="whitespace-nowrap px-3 py-3.5 text-center text-xs">
                                    <button
                                        @click="toggleUserStatus(user)"
                                        :disabled="user.id === currentAuthUser?.id || user.id === 1"
                                        :title="user.id === currentAuthUser?.id ? 'Cannot deactivate yourself' : 'Click to toggle status'"
                                        :class="[
                                            'inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium transition cursor-pointer disabled:cursor-not-allowed disabled:opacity-75',
                                            user.status
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300 hover:bg-emerald-100'
                                                : 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300 hover:bg-red-100'
                                        ]"
                                    >
                                        <span class="w-1.5 h-1.5 mr-1.5 rounded-full" :class="user.status ? 'bg-emerald-500' : 'bg-red-500'"></span>
                                        {{ user.status ? 'Active' : 'Inactive' }}
                                    </button>
                                </td>

                                <!-- Actions -->
                                <td class="whitespace-nowrap py-3.5 pl-3 pr-4 text-center text-xs font-medium">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            @click="editUser(user)"
                                            class="p-1.5 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 rounded hover:bg-indigo-50 dark:hover:bg-gray-700 transition"
                                            title="Edit User"
                                        >
                                            <i class="fas fa-edit text-sm"></i>
                                        </button>
                                        <button
                                            v-if="user.id !== currentAuthUser?.id && user.id !== 1"
                                            @click="confirmDelete(user)"
                                            class="p-1.5 text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 rounded hover:bg-red-50 dark:hover:bg-gray-700 transition"
                                            title="Delete User"
                                        >
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="!users.data || users.data.length === 0">
                                <td colspan="6" class="py-12 text-center">
                                    <div class="max-w-sm mx-auto">
                                        <div class="w-12 h-12 rounded-full bg-gray-100 dark:bg-gray-700 flex items-center justify-center mx-auto text-gray-400 text-lg mb-3">
                                            <i class="fas fa-users-slash"></i>
                                        </div>
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">No users found</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                            {{ hasActiveFilters ? 'No users match the search filters.' : 'Click "Add User" to create system accounts.' }}
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="users.links && users.links.length > 3" class="px-4 py-3 border-t border-gray-100 dark:border-gray-700 bg-gray-50/50 dark:bg-gray-800">
                    <Pagination :links="users.links" />
                </div>
            </div>
        </div>

        <!-- Create / Edit User Modal -->
        <Modal :show="showModal" @close="closeModal" max-width="md">
            <div class="p-6">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400">
                            <i :class="isEditing ? 'fas fa-user-edit' : 'fas fa-user-plus'" class="text-sm"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 dark:text-white">
                                {{ isEditing ? 'Edit User Account' : 'Create New User' }}
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                User account details and login credentials
                            </p>
                        </div>
                    </div>
                    <button
                        @click="closeModal"
                        class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 p-1.5 rounded-lg"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <!-- Form -->
                <form @submit.prevent="submitForm" class="mt-4 space-y-4">
                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input
                            v-model="form.name"
                            type="text"
                            required
                            placeholder="e.g. Istiak Ahmed"
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.name" class="text-xs text-red-600 mt-1">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Username (Required for username login) -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Username (For Login) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 font-mono text-xs">@</span>
                            <input
                                v-model="form.username"
                                type="text"
                                required
                                placeholder="username (letters, numbers, dashes)"
                                class="w-full pl-7 pr-3 py-2 text-sm font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                        </div>
                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                            Used to log into the system instead of typing full email.
                        </p>
                        <p v-if="form.errors.username" class="text-xs text-red-600 mt-1">
                            {{ form.errors.username }}
                        </p>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Role / Permission <span class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.role_id"
                            required
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="" disabled>Select role</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">
                                {{ role.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.role_id" class="text-xs text-red-600 mt-1">
                            {{ form.errors.role_id }}
                        </p>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                            Password <span v-if="!isEditing" class="text-red-500">*</span>
                            <span v-else class="text-gray-400 font-normal">(Leave blank to keep unchanged)</span>
                        </label>
                        <input
                            v-model="form.password"
                            type="password"
                            :required="!isEditing"
                            placeholder="••••••••"
                            class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <p v-if="form.errors.password" class="text-xs text-red-600 mt-1">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Email & Phone (Optional) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Email Address
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="name@mail.com"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">
                                {{ form.errors.email }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">
                                Phone Number
                            </label>
                            <input
                                v-model="form.phone"
                                type="text"
                                placeholder="017XXXXXXXX"
                                class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-indigo-500 focus:ring-indigo-500"
                            />
                            <p v-if="form.errors.phone" class="text-xs text-red-600 mt-1">
                                {{ form.errors.phone }}
                            </p>
                        </div>
                    </div>

                    <!-- Active Status Toggle -->
                    <div class="pt-1">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input
                                v-model="form.status"
                                type="checkbox"
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 dark:bg-gray-700"
                            />
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                Active Account (Allow system access)
                            </span>
                        </label>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-4 flex items-center justify-end gap-2.5 border-t border-gray-100 dark:border-gray-700">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-650 transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 rounded-lg shadow-sm transition"
                        >
                            <i v-if="form.processing" class="fas fa-spinner fa-spin mr-1.5"></i>
                            {{ isEditing ? 'Update User' : 'Save User' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- Delete Confirmation Modal -->
        <Modal :show="showDeleteModal" @close="showDeleteModal = false" max-width="sm">
            <div class="p-6 text-center">
                <div class="w-12 h-12 rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-exclamation-triangle text-lg"></i>
                </div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Delete User Account</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Are you sure you want to delete user <span class="font-bold text-gray-700 dark:text-gray-200">{{ userToDelete?.name }} (@{{ userToDelete?.username }})</span>?
                    This action will revoke all system access.
                </p>
                <div class="mt-5 flex justify-center gap-3">
                    <button
                        type="button"
                        @click="showDeleteModal = false"
                        class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="deleteUserConfirmed"
                        :disabled="isDeleting"
                        class="inline-flex items-center px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-lg shadow-sm"
                    >
                        <i v-if="isDeleting" class="fas fa-spinner fa-spin mr-1.5"></i>
                        Confirm Delete
                    </button>
                </div>
            </div>
        </Modal>
    </AdminLayout>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { router, usePage, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Modal from '@/Components/Modal.vue'
import Pagination from '@/Components/Pagination.vue'
import { PlusIcon } from '@heroicons/vue/24/outline'
import { formatDate } from '@/utils'

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
    summary: {
        type: Object,
        default: () => ({
            total: 0,
            active: 0,
            inactive: 0,
            roles_count: 0
        })
    }
})

const page = usePage()
const currentAuthUser = computed(() => page.props.auth?.user)

// Role helpers
const isRoleSuper = (role) => {
    if (!role) return false
    const name = (role.name || '').toLowerCase()
    const slug = (role.slug || '').toLowerCase()
    return name.includes('super') || slug.includes('super')
}

const isRoleAdmin = (role) => {
    if (!role) return false
    return (role.slug || '').toLowerCase() === 'admin'
}

// User avatar initials
const getInitials = (name) => {
    if (!name) return 'U'
    const parts = name.trim().split(' ')
    if (parts.length === 1) return parts[0].substring(0, 2)
    return (parts[0][0] + parts[1][0])
}

// Modal and state
const showModal = ref(false)
const isEditing = ref(false)
const currentUser = ref(null)

const showDeleteModal = ref(false)
const userToDelete = ref(null)
const isDeleting = ref(false)

// Filter state
const filterForm = reactive({
    search: props.filters?.search || '',
    role_id: props.filters?.role_id || '',
    status: props.filters?.status ?? ''
})

const hasActiveFilters = computed(() => {
    return Boolean(
        filterForm.search ||
        filterForm.role_id ||
        (filterForm.status !== '' && filterForm.status !== null)
    )
})

let debounceTimer = null
const debouncedFilter = () => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
        applyFilters()
    }, 350)
}

const applyFilters = () => {
    router.get(route('admin.users.index'), filterForm, {
        preserveState: true,
        preserveScroll: true
    })
}

const resetFilters = () => {
    filterForm.search = ''
    filterForm.role_id = ''
    filterForm.status = ''
    applyFilters()
}

// Clean Form
const form = useForm({
    name: '',
    username: '',
    email: '',
    phone: '',
    role_id: '',
    password: '',
    status: true
})

const openCreateModal = () => {
    isEditing.value = false
    currentUser.value = null
    form.clearErrors()
    form.reset()
    form.name = ''
    form.username = ''
    form.email = ''
    form.phone = ''
    form.role_id = props.roles[0]?.id || ''
    form.password = ''
    form.status = true
    showModal.value = true
}

const editUser = (user) => {
    isEditing.value = true
    currentUser.value = user
    form.clearErrors()
    form.name = user.name
    form.username = user.username || ''
    form.email = user.email || ''
    form.phone = user.phone || ''
    form.role_id = user.role_id
    form.password = ''
    form.status = Boolean(user.status)
    showModal.value = true
}

const closeModal = () => {
    showModal.value = false
    form.reset()
    currentUser.value = null
}

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('admin.users.update', currentUser.value.id), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    } else {
        form.post(route('admin.users.store'), {
            preserveScroll: true,
            onSuccess: () => closeModal()
        })
    }
}

const toggleUserStatus = (user) => {
    if (user.id === currentAuthUser.value?.id || user.id === 1) return
    router.post(route('admin.users.toggle-status', user.id), {}, {
        preserveScroll: true
    })
}

const confirmDelete = (user) => {
    userToDelete.value = user
    showDeleteModal.value = true
}

const deleteUserConfirmed = () => {
    if (!userToDelete.value) return
    isDeleting.value = true

    router.delete(route('admin.users.destroy', userToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false
            userToDelete.value = null
            isDeleting.value = false
        },
        onError: () => {
            isDeleting.value = false
        }
    })
}
</script>

