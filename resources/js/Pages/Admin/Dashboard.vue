<template>
    <div class="space-y-6">
      <!-- Stats Cards -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div v-for="stat in stats" :key="stat.name" class="bg-white rounded-lg shadow p-6">
          <div class="flex items-center">
            <div class="p-3 rounded-full" :class="stat.bgColor">
              <i :class="['fas', stat.icon, stat.iconColor, 'text-2xl']"></i>
            </div>
            <div class="ml-4">
              <h3 class="text-sm font-medium text-gray-500">{{ stat.name }}</h3>
              <p class="text-2xl font-semibold text-gray-900">{{ stat.value }}</p>
              <p class="text-sm text-gray-600">
                <span :class="stat.changeColor">{{ stat.change }}</span>
                vs last month
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity -->
      <div class="bg-white rounded-lg shadow">
        <div class="p-6">
          <h2 class="text-lg font-medium text-gray-900">Recent Activity</h2>
          <div class="mt-6 space-y-4">
            <div v-for="activity in recentActivity" :key="activity.id" class="flex items-start">
              <div class="p-2 rounded-full" :class="activity.bgColor">
                <i :class="['fas', activity.icon, activity.iconColor, 'text-lg']"></i>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-900">{{ activity.title }}</p>
                <p class="text-sm text-gray-500">{{ activity.time }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </template>

  <script setup>
  import { ref } from 'vue';

  defineOptions({
    layout: null  // Layout will be set globally for admin routes
  })

  const stats = [
    {
      name: 'Total Revenue',
      value: '$45,231.89',
      change: '+20.1%',
      icon: 'fa-dollar-sign',
      bgColor: 'bg-green-100',
      iconColor: 'text-green-600',
      changeColor: 'text-green-600'
    },
    {
      name: 'Active Users',
      value: '2,338',
      change: '+15.3%',
      icon: 'fa-users',
      bgColor: 'bg-blue-100',
      iconColor: 'text-blue-600',
      changeColor: 'text-blue-600'
    },
    {
      name: 'New Orders',
      value: '182',
      change: '-3.2%',
      icon: 'fa-shopping-cart',
      bgColor: 'bg-yellow-100',
      iconColor: 'text-yellow-600',
      changeColor: 'text-red-600'
    },
    {
      name: 'Support Tickets',
      value: '28',
      change: '+8.7%',
      icon: 'fa-ticket-alt',
      bgColor: 'bg-purple-100',
      iconColor: 'text-purple-600',
      changeColor: 'text-green-600'
    }
  ];

  const recentActivity = [
    {
      id: 1,
      title: 'New order #1234 received from John Doe',
      time: '5 minutes ago',
      icon: 'fa-shopping-cart',
      bgColor: 'bg-blue-100',
      iconColor: 'text-blue-600'
    },
    {
      id: 2,
      title: 'User Sarah Smith updated their profile',
      time: '2 hours ago',
      icon: 'fa-user',
      bgColor: 'bg-green-100',
      iconColor: 'text-green-600'
    },
    {
      id: 3,
      title: 'Support ticket #5678 resolved',
      time: '4 hours ago',
      icon: 'fa-check-circle',
      bgColor: 'bg-yellow-100',
      iconColor: 'text-yellow-600'
    }
  ];
  </script>
