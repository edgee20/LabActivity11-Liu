<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameTag('Announcement', 'Libre');
        $this->renameTag('Events', 'Schedule');
    }

    public function down(): void
    {
        $this->renameTag('Libre', 'Announcement');
        $this->renameTag('Schedule', 'Events');
    }

    private function renameTag(string $oldName, string $newName): void
    {
        if (! DB::table('tags')->where('name', $newName)->exists()) {
            DB::table('tags')->where('name', $oldName)->update(['name' => $newName]);
        }
    }
};
