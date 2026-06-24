<?php

declare(strict_types=1);

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Permission::query()
            ->where('slug', 'menu.lending.contacts')
            ->update([
                'slug' => 'menu.contacts',
                'name' => 'Contacts',
                'group' => 'Main Menu',
            ]);
    }

    public function down(): void
    {
        Permission::query()
            ->where('slug', 'menu.contacts')
            ->update([
                'slug' => 'menu.lending.contacts',
                'name' => 'Lending Contacts',
                'group' => 'Lending',
            ]);
    }
};
