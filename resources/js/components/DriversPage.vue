<template>
    <div class="min-h-screen bg-gray-50 flex flex-col items-center py-12 px-4">
        <div class="w-full max-w-2xl">
            <h1 class="text-2xl font-semibold text-gray-800 text-center mb-8">Drivers</h1>

            <div v-if="loading" class="text-center text-gray-500 py-16">Loading...</div>

            <div v-else-if="drivers.length === 0" class="text-center text-gray-400 py-16">
                No drivers found.
            </div>

            <div v-else class="flex flex-col gap-3">
                <button
                    v-for="driver in drivers"
                    :key="driver.id"
                    @click="$router.push(`/drivers/${driver.id}/orders`)"
                    class="bg-white rounded-xl border border-gray-200 px-6 py-4 flex items-center justify-between hover:border-indigo-300 hover:shadow-sm transition-all text-left"
                >
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-medium text-gray-800">{{ driver.name ?? `Driver #${driver.id}` }}</span>
                        <span class="text-xs text-gray-400">
                            {{ driver.vehicle?.type }} · {{ driver.vehicle?.capacity_kg }} kg · {{ driver.vehicle?.plate_number }}
                        </span>
                    </div>
                    <span
                        class="text-xs px-2 py-0.5 rounded-full"
                        :class="driver.is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'"
                    >
                        {{ driver.is_available ? 'Available' : 'Busy' }}
                    </span>
                </button>

                <div v-if="currentPage < lastPage" class="text-center mt-4">
                    <button
                        @click="loadMore"
                        :disabled="loadingMore"
                        class="text-sm text-indigo-600 hover:underline disabled:opacity-50"
                    >
                        {{ loadingMore ? 'Loading...' : 'Load more' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const drivers = ref([])
const loading = ref(true)
const loadingMore = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)

async function fetchDrivers(page = 1) {
    const res = await fetch(`/api/drivers?page=${page}`)
    return res.json()
}

onMounted(async () => {
    const data = await fetchDrivers(1)
    drivers.value = data.data
    currentPage.value = data.meta.current_page
    lastPage.value = data.meta.last_page
    loading.value = false
})

async function loadMore() {
    loadingMore.value = true
    const data = await fetchDrivers(currentPage.value + 1)
    drivers.value.push(...data.data)
    currentPage.value = data.meta.current_page
    lastPage.value = data.meta.last_page
    loadingMore.value = false
}
</script>
