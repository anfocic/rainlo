import AppLogoIcon from '@/components/app-logo-icon';
import { Link } from '@inertiajs/react';
import { type PropsWithChildren } from 'react';

interface AuthLayoutProps {
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthLayout({ children, title, description }: PropsWithChildren<AuthLayoutProps>) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-gradient-page p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="bg-gradient-card rounded-2xl p-8 shadow-xl">
                    <div className="flex flex-col gap-8">
                        <div className="flex flex-col items-center gap-4">
                            <Link href={route('home')} className="flex flex-col items-center gap-2 font-medium hover-gradient rounded-lg p-2 transition-all duration-200">
                                <div className="mb-1 flex h-12 w-12 items-center justify-center rounded-full bg-gradient-primary">
                                    <AppLogoIcon className="size-8 text-white" />
                                </div>
                                <span className="sr-only">{title}</span>
                            </Link>

                            <div className="space-y-2 text-center">
                                <h1 className="text-2xl font-bold text-gradient-primary">{title}</h1>
                                <p className="text-center text-sm text-gray-600 dark:text-gray-400">{description}</p>
                            </div>
                        </div>
                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
