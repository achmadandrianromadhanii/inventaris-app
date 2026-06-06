@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-200 bg-gray-50 focus:bg-white focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/20 rounded-lg shadow-sm transition-all duration-300 dark:bg-gray-800/50 dark:border-gray-700 dark:focus:bg-gray-900 dark:text-gray-100 placeholder:text-gray-400']) }}>
