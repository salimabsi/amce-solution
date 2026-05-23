<template>
    <div class="min-h-screen bg-gray-50 flex flex-col items-center py-12 px-4">
        <div class="w-full max-w-2xl">
            <h1 class="text-2xl font-semibold text-gray-800 text-center mb-8">Pending Orders</h1>

            <div v-if="loading" class="text-center text-gray-500 py-16">Loading...</div>

            <div v-else-if="orders.length === 0" class="text-center text-gray-400 py-16">
                No pending orders.
            </div>

            <div v-else class="flex flex-col gap-3">
                <div
                    v-for="order in orders"
                    :key="order.id"
                    class="bg-white rounded-xl border border-gray-200 px-6 py-4 flex items-center justify-between"
                >
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-gray-800">#{{ order.id }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                                {{ order.priority }}
                            </span>
                            <span class="text-xs px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">
                                {{ order.type }}
                            </span>
                        </div>
                        <span class="text-xs text-gray-400">{{ order.weight_kg }} kg</span>
                    </div>

                    <button
                        @click="assign(order)"
                        :disabled="order.assigning"
                        class="text-sm font-medium px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                    >
                        {{ order.assigning ? 'Assigning...' : 'Assign' }}
                    </button>
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

            <div v-if="message" class="mt-6 text-center text-sm" :class="messageError ? 'text-red-500' : 'text-green-600'">
                {{ message }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

const orders = ref([])
const loading = ref(true)
const loadingMore = ref(false)
const currentPage = ref(1)
const lastPage = ref(1)
const message = ref('')
const messageError = ref(false)

async function fetchOrders(page = 1) {
    const res = await fetch(`/api/orders/pending?page=${page}`)
    const data = await res.json()
    return data
}

onMounted(async () => {
    const data = await fetchOrders(1)
    orders.value = data.data.map(o => ({ ...o, assigning: false }))
    currentPage.value = data.meta.current_page
    lastPage.value = data.meta.last_page
    loading.value = false
})

async function loadMore() {
    loadingMore.value = true
    const data = await fetchOrders(currentPage.value + 1)
    orders.value.push(...data.data.map(o => ({ ...o, assigning: false })))
    currentPage.value = data.meta.current_page
    lastPage.value = data.meta.last_page
    loadingMore.value = false
}

async function assign(order) {
    order.assigning = true
    message.value = ''

    try {
        const res = await fetch(`/api/orders/${order.id}/assign`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        })

        const body = await res.json()

        if (res.ok) {
            orders.value = orders.value.filter(o => o.id !== order.id)
            showMessage(`Order #${order.id} assigned to driver #${body.data.driver_id}`)
        } else {
            showMessage(body.message ?? 'Failed to assign order.', true)
            order.assigning = false
        }
    } catch {
        showMessage('Network error.', true)
        order.assigning = false
    }
}

function showMessage(text, isError = false) {
    message.value = text
    messageError.value = isError
    setTimeout(() => { message.value = '' }, 4000)
}
</script>
