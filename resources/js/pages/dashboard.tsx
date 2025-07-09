import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="space-y-8">
                {/* Welcome Header */}
                <div className="bg-gradient-card rounded-2xl p-8 shadow-lg">
                    <h1 className="text-4xl font-bold text-gradient-primary mb-2">
                        Welcome to SmartTax
                    </h1>
                    <p className="text-lg text-gray-600 dark:text-gray-300">
                        Your intelligent tax management dashboard
                    </p>
                </div>

                {/* Stats Cards */}
                <div className="grid auto-rows-min gap-6 md:grid-cols-3">
                    <div className="bg-gradient-card rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</h3>
                                <p className="text-2xl font-bold text-gradient-primary">$124,563</p>
                            </div>
                            <div className="bg-gradient-primary rounded-full p-3">
                                <PlaceholderPattern className="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-card rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">Tax Savings</h3>
                                <p className="text-2xl font-bold text-gradient-secondary">$23,456</p>
                            </div>
                            <div className="bg-gradient-secondary rounded-full p-3">
                                <PlaceholderPattern className="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </div>

                    <div className="bg-gradient-card rounded-xl p-6 shadow-lg hover:shadow-xl transition-all duration-300 hover:scale-105">
                        <div className="flex items-center justify-between">
                            <div>
                                <h3 className="text-sm font-medium text-gray-600 dark:text-gray-400">Active Clients</h3>
                                <p className="text-2xl font-bold text-gradient-accent">1,234</p>
                            </div>
                            <div className="bg-gradient-accent rounded-full p-3">
                                <PlaceholderPattern className="h-6 w-6 text-white" />
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Content Area */}
                <div className="bg-gradient-card rounded-2xl p-8 shadow-lg min-h-96">
                    <h2 className="text-2xl font-bold text-gradient-primary mb-6">Recent Activity</h2>
                    <div className="space-y-4">
                        <div className="flex items-center p-4 bg-gradient-cool rounded-lg">
                            <div className="bg-white rounded-full p-2 mr-4">
                                <PlaceholderPattern className="h-5 w-5 text-blue-600" />
                            </div>
                            <div>
                                <p className="font-medium">New tax filing completed</p>
                                <p className="text-sm text-gray-600">Client: John Doe - 2 hours ago</p>
                            </div>
                        </div>

                        <div className="flex items-center p-4 bg-gradient-warm rounded-lg">
                            <div className="bg-white rounded-full p-2 mr-4">
                                <PlaceholderPattern className="h-5 w-5 text-orange-600" />
                            </div>
                            <div>
                                <p className="font-medium">Payment received</p>
                                <p className="text-sm text-gray-600">Amount: $2,500 - 4 hours ago</p>
                            </div>
                        </div>

                        <div className="flex items-center p-4 bg-gradient-cool rounded-lg">
                            <div className="bg-white rounded-full p-2 mr-4">
                                <PlaceholderPattern className="h-5 w-5 text-green-600" />
                            </div>
                            <div>
                                <p className="font-medium">Document uploaded</p>
                                <p className="text-sm text-gray-600">Client: Jane Smith - 6 hours ago</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
