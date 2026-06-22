<a href="{{ route('transactions.create') }}" {{ $attributes->merge(['class' => 'btn-secondary shrink-0 whitespace-nowrap']) }}>
    <x-ming-icon name="system.add" class="h-4 w-4" />
    <span class="hidden sm:inline">Record Transaction</span>
    <span class="sm:hidden">Record</span>
</a>
