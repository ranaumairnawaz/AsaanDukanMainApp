<template>
    <AdminLayout>
        <Head title="WhatsApp Broadcast" />

        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 font-serif">WhatsApp Broadcast</h2>
                    <p class="mt-1 text-sm text-gray-500">Send bulk WhatsApp messages to merchants based on their status.</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow border border-gray-200">
                <form @submit.prevent="submit" class="p-6">
                    <!-- Filters -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Target Audience</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            <label class="relative flex items-start p-4 cursor-pointer border rounded-lg hover:bg-gray-50 transition-colors" :class="{'border-primary bg-primary/5': form.filter === 'all'}">
                                <div class="flex items-center h-5">
                                    <input type="radio" v-model="form.filter" value="all" class="h-4 w-4 text-primary border-gray-300 focus:ring-primary" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <span class="font-medium text-gray-900 block">All Merchants</span>
                                    <span class="text-gray-500">Every store with a WhatsApp number.</span>
                                </div>
                            </label>
                            
                            <label class="relative flex items-start p-4 cursor-pointer border rounded-lg hover:bg-gray-50 transition-colors" :class="{'border-primary bg-primary/5': form.filter === 'active'}">
                                <div class="flex items-center h-5">
                                    <input type="radio" v-model="form.filter" value="active" class="h-4 w-4 text-primary border-gray-300 focus:ring-primary" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <span class="font-medium text-gray-900 block">Active Stores</span>
                                    <span class="text-gray-500">Only stores with an active subscription.</span>
                                </div>
                            </label>
                            
                            <label class="relative flex items-start p-4 cursor-pointer border rounded-lg hover:bg-gray-50 transition-colors" :class="{'border-primary bg-primary/5': form.filter === 'trial'}">
                                <div class="flex items-center h-5">
                                    <input type="radio" v-model="form.filter" value="trial" class="h-4 w-4 text-primary border-gray-300 focus:ring-primary" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <span class="font-medium text-gray-900 block">Trial Users</span>
                                    <span class="text-gray-500">Merchants currently on trial.</span>
                                </div>
                            </label>
                            
                            <label class="relative flex items-start p-4 cursor-pointer border rounded-lg hover:bg-gray-50 transition-colors" :class="{'border-primary bg-primary/5': form.filter === 'suspended'}">
                                <div class="flex items-center h-5">
                                    <input type="radio" v-model="form.filter" value="suspended" class="h-4 w-4 text-primary border-gray-300 focus:ring-primary" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <span class="font-medium text-gray-900 block">Suspended Stores</span>
                                    <span class="text-gray-500">Merchants with suspended accounts.</span>
                                </div>
                            </label>

                            <label class="relative flex items-start p-4 cursor-pointer border rounded-lg hover:bg-gray-50 transition-colors" :class="{'border-primary bg-primary/5': form.filter === 'expiring_5_days'}">
                                <div class="flex items-center h-5">
                                    <input type="radio" v-model="form.filter" value="expiring_5_days" class="h-4 w-4 text-primary border-gray-300 focus:ring-primary" />
                                </div>
                                <div class="ml-3 text-sm">
                                    <span class="font-medium text-gray-900 block">Expiring Soon</span>
                                    <span class="text-gray-500">Active stores expiring in ≤ 5 days.</span>
                                </div>
                            </label>
                        </div>
                        <p v-if="form.errors.filter" class="mt-2 text-sm text-red-600">{{ form.errors.filter }}</p>
                    </div>

                    <!-- Message -->
                    <div class="mb-6">
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message Content</label>
                        <textarea
                            id="message"
                            v-model="form.message"
                            rows="6"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary sm:text-sm"
                            placeholder="Enter the message you want to broadcast..."
                        ></textarea>
                        <p v-if="form.errors.message" class="mt-2 text-sm text-red-600">{{ form.errors.message }}</p>
                        <p class="mt-2 text-sm text-gray-500">
                            Note: The message will be queued and sent via WhatsApp to all matching merchants. Use plain text formatting (*bold*, _italic_, ~strikethrough~).
                        </p>
                    </div>

                    <!-- Submit -->
                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary hover:bg-primary/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50"
                        >
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span v-else class="mr-2">📢</span>
                            Broadcast Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const form = useForm({
    filter: 'all',
    message: ''
});

const submit = () => {
    form.post(route('admin.broadcast.send'), {
        preserveScroll: true,
        onSuccess: (page) => {
            if (page.props.flash.success) {
                form.reset('message');
            }
        },
    });
};
</script>
