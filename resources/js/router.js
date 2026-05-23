import { createRouter, createWebHistory } from 'vue-router'
import PendingOrders from './components/PendingOrders.vue'
import DriversPage from './components/DriversPage.vue'
import DriverOrdersPage from './components/DriverOrdersPage.vue'

export default createRouter({
    history: createWebHistory(),
    routes: [
        { path: '/', component: PendingOrders },
        { path: '/drivers', component: DriversPage },
        { path: '/drivers/:id/orders', component: DriverOrdersPage },
    ],
})
