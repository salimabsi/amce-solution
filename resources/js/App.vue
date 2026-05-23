<template>
    <div>
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-2xl mx-auto px-4 flex gap-6 h-12 items-center">
                <RouterLink
                    to="/"
                    class="text-sm font-medium transition-colors"
                    :class="$route.path === '/' ? 'text-indigo-600' : 'text-gray-500 hover:text-gray-800'"
                >
                    Pending Orders
                </RouterLink>
                <RouterLink
                    to="/drivers"
                    class="text-sm font-medium transition-colors"
                    :class="$route.path.startsWith('/drivers') ? 'text-indigo-600' : 'text-gray-500 hover:text-gray-800'"
                >
                    Drivers
                </RouterLink>
            </div>
        </nav>
        <RouterView />
    </div>
</template>
