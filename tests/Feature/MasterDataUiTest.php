<?php

namespace Tests\Feature;

use Tests\TestCase;

class MasterDataUiTest extends TestCase
{
    public function test_pid_and_boq_pages_use_icon_actions_and_consistent_rounded_ui(): void
    {
        $pid = file_get_contents(resource_path('views/admin/import/data-pid.blade.php'));
        $pidModals = file_get_contents(resource_path('views/admin/import/partials/pid-modals.blade.php'));
        $boq = file_get_contents(resource_path('views/admin/import/data-boq.blade.php'));
        $views = $pid.$pidModals.$boq;

        $this->assertStringContainsString('data-tooltip="Lihat detail"', $pid);
        $this->assertStringContainsString('data-tooltip="Edit data"', $pid);
        $this->assertStringContainsString('data-tooltip="Hapus data"', $pid);
        $this->assertStringContainsString('data-tooltip="Lihat detail BOQ"', $boq);
        $this->assertStringContainsString('data-tooltip="Hapus designator"', $boq);

        $this->assertDoesNotMatchRegularExpression(
            '/rounded-(?:full|xl|2xl|3xl|\[[^\]]+\])/',
            $views
        );
        $this->assertStringNotContainsString('bg-gradient', $pidModals.$boq);
        $this->assertStringContainsString('dark:bg-slate-900', $pidModals);
        $this->assertStringContainsString('dark:bg-slate-900', $boq);
    }

    public function test_master_designator_menu_is_guarded_for_superadmin_only(): void
    {
        foreach (['sidebar.blade.php', 'sidebar-mobile.blade.php'] as $file) {
            $sidebar = file_get_contents(resource_path('views/admin/components/'.$file));
            $guardPosition = strpos($sidebar, "@if(auth()->user()->role === 'superadmin')");
            $menuPosition = strpos($sidebar, '<span>Master Designator</span>');

            $this->assertNotFalse($guardPosition);
            $this->assertNotFalse($menuPosition);
            $this->assertLessThan($menuPosition, $guardPosition);
        }
    }
}
