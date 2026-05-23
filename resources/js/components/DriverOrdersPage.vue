<template>
    <div class="min-h-screen bg-gray-50 flex flex-col items-center py-12 px-4">
        <div class="w-full max-w-2xl">
            <div class="flex items-center gap-3 mb-8">
                <button @click="$router.back()" class="text-sm text-gray-400 hover:text-gray-600">← Back</button>
                <h1 class="text-2xl font-semibold text-gray-800">Driver #{{ driverId }} Orders</h1>
            </div>

            <div class="flex gap-2 mb-6 flex-wrap">
                <button
                    v-for="opt in statusOptions"
                    :key="opt.value"
                    @click="selectStatus(opt.value)"
                    class="text-xs px-3 py-1.5 rounded-full border transition-colors"
                    :class="selectedStatus === opt.value
                        ? 'bg-indigo-600 text-white border-indigo-600'
                        : 'bg-white text-gray-600 border-gray-200 hover:border-indigo-300'"
                >
                    {{ opt.label }}
                </button>
            </div>

            <div v-if="loading" class="text-center text-gray-500 py-16">Loading...</div>

            <div v-else-if="orders.length === 0" class="text-center text-gray-400 py-16">
                No orders found.
            </div>

            <div v-else class="flex flex-col gap-3">
                <div
                    v-for="order in orders"
                    :key="order.id"
                    class="bg-white rounded-xl border border-gray-200 px-6 py-4"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex flex-col gap-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium text-gray-800">#{{ order.id }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">{{ order.priority }}</span>
                                <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">{{ order.type }}</span>
                            </div>
                            <span class="text-xs text-gray-400">{{ order.weight_kg }} kg</span>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full" :class="statusClass(order.status)">
                            {{ order.status }}
                        </span>
                    </div>
                </div>

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
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'

const route = useRoute()
const driverId = route.params.id

const orders = ref([])
const loading = ref(true)
const loadingMore = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const selectedStatus = ref(null)

const statusOptions = [
    { label: 'All', value: null },
    { label: 'Assigned', value: 'assigned' },
    { label: 'Being Served', value: 'being_served' },
    { label: 'Completed', value: 'completed' },
    { label: 'Cancelled', value: 'cancelled' },
]

function statusClass(status) {
    const map = {
        pending: 'bg-yellow-100 text-yellow-700',
        assigned: 'bg-blue-100 text-blue-700',
        being_served: 'bg-indigo-100 text-indigo-700',
        completed: 'bg-green-100 text-green-700',
        cancelled: 'bg-red-100 text-red-600',
    }
    return map[status] ?? 'bg-gray-100 text-gray-600'
}

async function fetchOrders(page = 1) {
    const params = new URLSearchParams({ page })
    if (selectedStatus.value) params.set('status', selectedStatus.value)
    const res = await fetch(`/api/drivers/${driverId}/orders?${params}`)
    return res.json()
}

async function load() {
    loading.value = true
    const data = await fetchOrders(1)
    orders.value = data.data ?? []
    currentPage.value = data.meta?.current_page ?? 1
    lastPage.value = data.meta?.last_page ?? 1
    loading.value = false
}

async function loadMore() {
    loadingMore.value = true
    const data = await fetchOrders(currentPage.value + 1)
    orders.value.push(...(data.data ?? []))
    currentPage.value = data.meta?.current_page ?? currentPage.value
    lastPage.value = data.meta?.last_page ?? lastPage.value
    loadingMore.value = false
}

function selectStatus(value) {
    selectedStatus.value = value
}

watch(selectedStatus, load)
onMounted(load)
</script>
