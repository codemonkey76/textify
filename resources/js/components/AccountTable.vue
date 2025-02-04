<script setup lang="ts">
import { ref, onMounted } from 'vue';
import axios from 'axios';

interface Account {
    id: number;
    email: string;
    destinations: string;
}

const accounts = ref<Account[]>([]);

const fetchAccounts = async () => {
    try {
        const response = await axios.get('/api/accounts');
        accounts.value = response.data;
    } catch (error) {
        console.error('Error fetching accounts:', error);
    }
};

onMounted(fetchAccounts)
</script>
<template>
        <div class="mt-8 flow-root">
          <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
              <table class="min-w-full divide-y divide-gray-700">
                <thead>
                  <tr>
                    <th scope="col" class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-white sm:pl-0">Id</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">Email</th>
                    <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold text-white">Destinations</th>
                    <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-0">
                      <span class="sr-only">Edit</span>
                    </th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-800">
                  <tr v-for="account in accounts" :key="account.id">
                            <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-white sm:pl-0">{{ account.id }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300">{{ account.email }}</td>
                            <td class="whitespace-nowrap px-3 py-4 text-sm text-gray-300">{{account.destinations }}</td>
                            <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-0">
                                <a href="#" class="text-indigo-400 hover:text-indigo-300">Edit<span class="sr-only">, {{ account.email }}</span></a>
                            </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
</template>
