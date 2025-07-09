import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

interface HomeProps {
    canLogin: boolean;
    canRegister: boolean;
    laravelVersion?: string;
    phpVersion?: string;
}

export default function Home({ canLogin, canRegister, laravelVersion, phpVersion }: HomeProps) {
    const { auth } = usePage<SharedData>().props;

    return (
        <>
            <Head title="Welcome">
                <meta name="description" content="Welcome to our application. Sign in to your account or create a new one to get started." />
            </Head>

            <div className="min-h-screen bg-gradient-page">
                {/* Navigation Header */}
                <header className="relative z-10">
                    <nav className="mx-auto flex max-w-7xl items-center justify-between p-6 lg:px-8">
                        {/* Logo */}
                        <div className="flex lg:flex-1">
                            <Link href="/" className="-m-1.5 p-1.5">
                                <span className="text-2xl font-bold text-gradient-primary">
                                    SmartTax
                                </span>
                            </Link>
                        </div>

                        {/* Auth Navigation */}
                        <div className="flex lg:flex-1 lg:justify-end">
                            {auth.user ? (
                                <div className="flex items-center space-x-4">
                                    <span className="text-sm text-gray-700 dark:text-gray-300">
                                        Welcome back, {auth.user.name}!
                                    </span>
                                    <Link
                                        href={route('dashboard')}
                                        className="btn-gradient-primary rounded-md px-3.5 py-2.5 text-sm font-semibold text-white shadow-lg hover:shadow-xl"
                                    >
                                        Go to Dashboard
                                    </Link>
                                </div>
                            ) : (
                                <div className="flex items-center space-x-4">
                                    {canLogin && (
                                        <Link
                                            href={route('login')}
                                            className="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                        >
                                            Log in
                                        </Link>
                                    )}
                                    {canRegister && (
                                        <Link
                                            href={route('register')}
                                            className="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                        >
                                            Sign up
                                        </Link>
                                    )}
                                </div>
                            )}
                        </div>
                    </nav>
                </header>

                {/* Main Content */}
                <main className="relative isolate px-6 pt-14 lg:px-8">
                    {/* Background decoration */}
                    <div
                        className="absolute inset-x-0 -top-40 -z-10 transform-gpu overflow-hidden blur-3xl sm:-top-80"
                        aria-hidden="true"
                    >
                        <div
                            className="relative left-[calc(50%-11rem)] aspect-[1155/678] w-[36.125rem] -translate-x-1/2 rotate-[30deg] bg-gradient-to-tr from-[#ff80b5] to-[#9089fc] opacity-30 sm:left-[calc(50%-30rem)] sm:w-[72.1875rem]"
                            style={{
                                clipPath:
                                    'polygon(74.1% 44.1%, 100% 61.6%, 97.5% 26.9%, 85.5% 0.1%, 80.7% 2%, 72.5% 32.5%, 60.2% 62.4%, 52.4% 68.1%, 47.5% 58.3%, 45.2% 34.5%, 27.5% 76.7%, 0.1% 64.9%, 17.9% 100%, 27.6% 76.8%, 76.1% 97.7%, 74.1% 44.1%)',
                            }}
                        />
                    </div>

                    <div className="mx-auto max-w-2xl py-32 sm:py-48 lg:py-56">
                        <div className="text-center">
                            {auth.user ? (
                                // Authenticated User Content
                                <>
                                    <h1 className="text-4xl font-bold tracking-tight text-gradient-primary sm:text-6xl">
                                        Welcome back, {auth.user.name}!
                                    </h1>
                                    <p className="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                                        You're successfully logged in. Ready to continue where you left off?
                                    </p>
                                    <div className="mt-10 flex items-center justify-center gap-x-6">
                                        <Link
                                            href={route('dashboard')}
                                            className="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                        >
                                            Go to Dashboard
                                        </Link>
                                        <Link
                                            href={route('profile.edit')}
                                            className="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                        >
                                            Manage Profile <span aria-hidden="true">→</span>
                                        </Link>
                                    </div>
                                </>
                            ) : (
                                // Guest User Content
                                <>
                                    <h1 className="text-4xl font-bold tracking-tight text-gradient-primary sm:text-6xl">
                                        Welcome to SmartTax
                                    </h1>
                                    <p className="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                                        Get started by creating an account or signing in to access your dashboard and manage your data securely.
                                    </p>
                                    <div className="mt-10 flex items-center justify-center gap-x-6">
                                        {canRegister && (
                                            <Link
                                                href={route('register')}
                                                className="rounded-md bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                                            >
                                                Get started
                                            </Link>
                                        )}
                                        {canLogin && (
                                            <Link
                                                href={route('login')}
                                                className="text-sm font-semibold leading-6 text-gray-900 hover:text-indigo-600 dark:text-gray-100 dark:hover:text-indigo-400"
                                            >
                                                Sign in <span aria-hidden="true">→</span>
                                            </Link>
                                        )}
                                    </div>
                                </>
                            )}
                        </div>
                    </div>

                    {/* Features Section for Guests */}
                    {!auth.user && (
                        <div className="mx-auto max-w-7xl px-6 lg:px-8">
                            <div className="mx-auto max-w-2xl lg:text-center">
                                <h2 className="text-base font-semibold leading-7 text-indigo-600 dark:text-indigo-400">
                                    Everything you need
                                </h2>
                                <p className="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl dark:text-white">
                                    Built with modern technology
                                </p>
                                <p className="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-300">
                                    Our application is built with Laravel and React, providing you with a fast, secure, and modern experience.
                                </p>
                            </div>
                            <div className="mx-auto mt-16 max-w-2xl sm:mt-20 lg:mt-24 lg:max-w-4xl">
                                <dl className="grid max-w-xl grid-cols-1 gap-x-8 gap-y-10 lg:max-w-none lg:grid-cols-2 lg:gap-y-16">
                                    <div className="relative pl-16">
                                        <dt className="text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                            <div className="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600">
                                                <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.623 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                                </svg>
                                            </div>
                                            Secure Authentication
                                        </dt>
                                        <dd className="mt-2 text-base leading-7 text-gray-600 dark:text-gray-300">
                                            Your data is protected with industry-standard security measures and encrypted authentication.
                                        </dd>
                                    </div>
                                    <div className="relative pl-16">
                                        <dt className="text-base font-semibold leading-7 text-gray-900 dark:text-white">
                                            <div className="absolute left-0 top-0 flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-600">
                                                <svg className="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" strokeWidth="1.5" stroke="currentColor">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                                </svg>
                                            </div>
                                            Lightning Fast
                                        </dt>
                                        <dd className="mt-2 text-base leading-7 text-gray-600 dark:text-gray-300">
                                            Built with modern technologies for optimal performance and user experience.
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    )}

                    {/* Footer with version info */}
                    {(laravelVersion || phpVersion) && (
                        <footer className="mt-32 border-t border-gray-200 pt-8 dark:border-gray-700">
                            <div className="mx-auto max-w-7xl px-6 lg:px-8">
                                <div className="flex justify-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
                                    {laravelVersion && <span>Laravel v{laravelVersion}</span>}
                                    {phpVersion && <span>PHP v{phpVersion}</span>}
                                </div>
                            </div>
                        </footer>
                    )}
                </main>
            </div>
        </>
    );
}
