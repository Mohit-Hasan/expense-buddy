@php
    $reportNav = [
        ['route' => 'reports.detailed', 'label' => 'Detailed Analytics', 'icon' => 'business.chart-bar', 'active' => 'reports.detailed'],
        ['route' => 'reports.income-vs-expense', 'label' => 'Income vs Expense', 'icon' => 'business.chart', 'active' => 'reports.income-vs-expense'],
        ['route' => 'reports.categorized', 'label' => 'Categorized', 'icon' => 'business.chart-pie', 'active' => 'reports.categorized'],
    ];
@endphp

<x-section-nav :items="$reportNav" />
