<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ApprovalCenterService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApprovalCenterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-09-25 09:00:00');
        $this->createSchema();
        $this->seedQueue();
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_all_authenticated_roles_can_open_read_only_monitoring(): void
    {
        $response = $this->actingAs(User::findOrFail(3))
            ->get(route('approval-center.index'));

        $response->assertOk()
            ->assertSee('Pusat Approval')
            ->assertSee('LOP ADMIN A')
            ->assertSee('LOP ADMIN B')
            ->assertSee('LOP PT2 ADMIN A')
            ->assertSee('Monitoring saja')
            ->assertDontSee('Inbox Saya');
    }

    public function test_monitoring_page_renders_with_every_role_layout(): void
    {
        foreach ([1, 3, 5, 6, 7, 8, 9, 10, 11, 12] as $userId) {
            $this->actingAs(User::findOrFail($userId))
                ->get(route('approval-center.index', ['scope' => 'all']))
                ->assertOk()
                ->assertSee('Pusat Approval');
        }
    }

    public function test_approval_center_uses_clean_table_layout_without_gradient(): void
    {
        $page = file_get_contents(resource_path('views/approval-center/index.blade.php'));
        $popup = file_get_contents(resource_path('views/approval-center/partials/login-alert.blade.php'));

        $this->assertStringContainsString('<table', $page);
        $this->assertStringContainsString('rounded-lg', $page);
        $this->assertStringNotContainsString('gradient', $page.$popup);
        $this->assertStringNotContainsString('rounded-2xl', $page.$popup);
        $this->assertStringNotContainsString('rounded-3xl', $page.$popup);
    }

    public function test_admin_inbox_is_limited_to_projects_assigned_by_that_admin(): void
    {
        $response = $this->actingAs(User::findOrFail(1))
            ->get(route('approval-center.index', ['scope' => 'mine']));

        $response->assertOk()
            ->assertSee('Inbox Saya')
            ->assertSee('LOP ADMIN A')
            ->assertSee('LOP PT2 ADMIN A')
            ->assertDontSee('LOP ADMIN B')
            ->assertSee('Review');
    }

    public function test_active_pt2_inbox_is_accessible_and_isolated_by_assigning_admin(): void
    {
        DB::table('pt2_projects')->insert([
            ['id_pt2_project' => 31, 'pid' => 'PT2-B', 'project_name' => 'PT2 Project Admin B', 'created_at' => now(), 'updated_at' => now()],
            ['id_pt2_project' => 32, 'pid' => 'PT2-DONE', 'project_name' => 'PT2 Project Selesai', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('pt2_lops')->insert([
            ['id_pt2_lop' => 301, 'pt2_project_id' => 31, 'lop_name' => 'LOP PT2 ADMIN B', 'branch' => 'MATARAM', 'sto' => 'MTR', 'status_progress' => 'instalasi', 'sdi_approval_status' => null, 'is_golive' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['id_pt2_lop' => 302, 'pt2_project_id' => 32, 'lop_name' => 'LOP PT2 SUDAH GOLIVE', 'branch' => 'KUPANG', 'sto' => 'KPN', 'status_progress' => 'golive', 'sdi_approval_status' => 'approved', 'is_golive' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('pt2_assignments')->insert([
            ['pt2_project_id' => 31, 'pt2_lop_id' => 301, 'assigned_by' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['pt2_project_id' => 32, 'pt2_lop_id' => 302, 'assigned_by' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs(User::findOrFail(1))
            ->get(route('admin.inbox.pt2'))
            ->assertOk()
            ->assertSee('Inbox PT 2 Admin')
            ->assertSee('LOP PT2 ADMIN A')
            ->assertDontSee('LOP PT2 ADMIN B')
            ->assertDontSee('LOP PT2 SUDAH GOLIVE');

        $this->get(route('admin.inbox.pt2', ['search' => 'SOE']))
            ->assertOk()
            ->assertSee('LOP PT2 ADMIN A');
    }

    public function test_personal_snapshot_counts_only_live_pending_evidence(): void
    {
        $snapshot = app(ApprovalCenterService::class)->loginSnapshot(1);

        $this->assertSame(2, $snapshot['lop_count']);
        $this->assertSame(2, $snapshot['evidence_count']);
        $this->assertSame(1, $snapshot['urgent_count']);
    }

    public function test_admin_login_flashes_personal_approval_popup_but_pm_login_does_not(): void
    {
        $this->post('/login', ['username' => 'admin-a', 'password' => 'secret'])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHas('admin_approval_alert', fn (array $alert) => $alert['lop_count'] === 2
                && $alert['evidence_count'] === 2);

        auth()->logout();

        $this->post('/login', ['username' => 'pm-monitor', 'password' => 'secret'])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionMissing('admin_approval_alert');
    }

    private function createSchema(): void
    {
        foreach (['mancores_pt2', 'dismantles_pt2', 'surveys_pt2', 'pt2_evidences', 'pt2_assignments', 'pt2_lops', 'pt2_projects', 'evidences', 'boq_items', 'pro_assign', 'lops', 'projects', 'users', 'roles'] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('roles', function (Blueprint $table): void {
            $table->id('id_roles');
            $table->string('code');
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id('id_user');
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('projects', function (Blueprint $table): void {
            $table->id('id_project');
            $table->string('pid')->nullable();
            $table->string('pid_sap')->nullable();
            $table->string('project_name');
            $table->string('program')->nullable();
            $table->string('branch')->nullable();
            $table->string('sto')->nullable();
            $table->timestamps();
        });
        Schema::create('lops', function (Blueprint $table): void {
            $table->id('id_lop');
            $table->unsignedBigInteger('project_id');
            $table->string('lop_name');
            $table->string('program_sap')->nullable();
            $table->string('branch')->nullable();
            $table->string('sto')->nullable();
            $table->timestamps();
        });
        Schema::create('pro_assign', function (Blueprint $table): void {
            $table->id('id_proassign');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('waspang_id')->nullable();
            $table->unsignedBigInteger('teknisi_id')->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->timestamps();
        });
        Schema::create('boq_items', function (Blueprint $table): void {
            $table->id('id_boq');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('lop_id')->nullable();
            $table->string('designator')->nullable();
        });
        Schema::create('evidences', function (Blueprint $table): void {
            $table->id('id_evidence');
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('boq_item_id')->nullable();
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('stage');
            $table->string('evidence_type');
            $table->string('file_path')->nullable();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('pt2_projects', function (Blueprint $table): void {
            $table->id('id_pt2_project');
            $table->string('pid')->nullable();
            $table->string('pid_sap')->nullable();
            $table->string('project_name');
            $table->timestamps();
        });
        Schema::create('pt2_lops', function (Blueprint $table): void {
            $table->id('id_pt2_lop');
            $table->unsignedBigInteger('pt2_project_id');
            $table->string('lop_name');
            $table->string('branch')->nullable();
            $table->string('sto')->nullable();
            $table->string('id_ihld')->nullable();
            $table->string('mitra_name')->nullable();
            $table->string('status_progress')->nullable();
            $table->string('sdi_approval_status')->nullable();
            $table->boolean('is_golive')->nullable()->default(false);
            $table->timestamps();
        });
        Schema::create('pt2_assignments', function (Blueprint $table): void {
            $table->id('id_pt2_assignment');
            $table->unsignedBigInteger('pt2_project_id');
            $table->unsignedBigInteger('pt2_lop_id');
            $table->unsignedBigInteger('teknisi_id')->nullable();
            $table->unsignedBigInteger('assigned_by');
            $table->timestamps();
        });
        Schema::create('pt2_evidences', function (Blueprint $table): void {
            $table->id('id_pt2_evidence');
            $table->unsignedBigInteger('pt2_project_id');
            $table->unsignedBigInteger('pt2_lop_id');
            $table->unsignedBigInteger('uploaded_by')->nullable();
            $table->string('stage');
            $table->string('evidence_type');
            $table->string('file_path')->nullable();
            $table->string('status');
            $table->timestamps();
        });
        foreach (['surveys_pt2', 'mancores_pt2', 'dismantles_pt2'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) use ($tableName): void {
                $primaryKey = match ($tableName) {
                    'surveys_pt2' => 'id_survey_pt2',
                    'mancores_pt2' => 'id_mancore_pt2',
                    default => 'id_dismantle_pt2',
                };
                $table->id($primaryKey);
                $table->unsignedBigInteger('pt2_project_id')->nullable();
                $table->unsignedBigInteger('pt2_lop_id');
                $table->timestamps();
            });
        }
    }

    private function seedQueue(): void
    {
        DB::table('roles')->insert([
            ['id_roles' => 1, 'code' => 'admin', 'name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['id_roles' => 2, 'code' => 'pm', 'name' => 'PM', 'created_at' => now(), 'updated_at' => now()],
            ['id_roles' => 3, 'code' => 'tif', 'name' => 'TIF', 'created_at' => now(), 'updated_at' => now()],
            ['id_roles' => 4, 'code' => 'officer', 'name' => 'Officer', 'created_at' => now(), 'updated_at' => now()],
            ['id_roles' => 5, 'code' => 'superadmin', 'name' => 'Superadmin', 'created_at' => now(), 'updated_at' => now()],
            ['id_roles' => 6, 'code' => 'super_tif', 'name' => 'Super TIF', 'created_at' => now(), 'updated_at' => now()],
            ['id_roles' => 7, 'code' => 'sdi', 'name' => 'SDI', 'created_at' => now(), 'updated_at' => now()],
            ['id_roles' => 8, 'code' => 'waspang', 'name' => 'Waspang', 'created_at' => now(), 'updated_at' => now()],
            ['id_roles' => 9, 'code' => 'teknisi', 'name' => 'Teknisi', 'created_at' => now(), 'updated_at' => now()],
            ['id_roles' => 10, 'code' => 'sdi_surveyor', 'name' => 'SDI Surveyor', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('users')->insert([
            ['id_user' => 1, 'name' => 'Admin A', 'username' => 'admin-a', 'password' => Hash::make('secret'), 'role_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 2, 'name' => 'Admin B', 'username' => 'admin-b', 'password' => Hash::make('secret'), 'role_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 3, 'name' => 'PM Monitor', 'username' => 'pm-monitor', 'password' => Hash::make('secret'), 'role_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 4, 'name' => 'Uploader', 'username' => 'uploader', 'password' => Hash::make('secret'), 'role_id' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 5, 'name' => 'TIF Monitor', 'username' => 'tif-monitor', 'password' => Hash::make('secret'), 'role_id' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 6, 'name' => 'Officer Monitor', 'username' => 'officer-monitor', 'password' => Hash::make('secret'), 'role_id' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 7, 'name' => 'Superadmin Monitor', 'username' => 'superadmin-monitor', 'password' => Hash::make('secret'), 'role_id' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 8, 'name' => 'Super TIF Monitor', 'username' => 'super-tif-monitor', 'password' => Hash::make('secret'), 'role_id' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 9, 'name' => 'SDI Monitor', 'username' => 'sdi-monitor', 'password' => Hash::make('secret'), 'role_id' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 10, 'name' => 'Waspang Monitor', 'username' => 'waspang-monitor', 'password' => Hash::make('secret'), 'role_id' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 11, 'name' => 'Teknisi Monitor', 'username' => 'teknisi-monitor', 'password' => Hash::make('secret'), 'role_id' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['id_user' => 12, 'name' => 'Surveyor Monitor', 'username' => 'surveyor-monitor', 'password' => Hash::make('secret'), 'role_id' => 10, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('projects')->insert([
            ['id_project' => 10, 'pid' => 'PID-A', 'project_name' => 'Project Admin A', 'program' => 'OSP', 'created_at' => now(), 'updated_at' => now()],
            ['id_project' => 20, 'pid' => 'PID-B', 'project_name' => 'Project Admin B', 'program' => 'HEM', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('lops')->insert([
            ['id_lop' => 100, 'project_id' => 10, 'lop_name' => 'LOP ADMIN A', 'branch' => 'KUPANG', 'sto' => 'KPN', 'created_at' => now(), 'updated_at' => now()],
            ['id_lop' => 200, 'project_id' => 20, 'lop_name' => 'LOP ADMIN B', 'branch' => 'MATARAM', 'sto' => 'MTR', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('pro_assign')->insert([
            ['project_id' => 10, 'assigned_by' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['project_id' => 20, 'assigned_by' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('evidences')->insert([
            ['project_id' => 10, 'uploaded_by' => 4, 'stage' => 'pengukuran', 'evidence_type' => 'file_sor', 'status' => 'pending', 'created_at' => now()->subHours(50), 'updated_at' => now()],
            ['project_id' => 10, 'uploaded_by' => 4, 'stage' => 'persiapan', 'evidence_type' => 'barang_tiba', 'status' => 'approved', 'created_at' => now()->subHours(60), 'updated_at' => now()],
            ['project_id' => 20, 'uploaded_by' => 4, 'stage' => 'finishing', 'evidence_type' => 'final_boq', 'status' => 'pending', 'created_at' => now()->subHours(2), 'updated_at' => now()],
        ]);
        DB::table('pt2_projects')->insert(['id_pt2_project' => 30, 'pid' => 'PT2-A', 'project_name' => 'PT2 Project A', 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pt2_lops')->insert(['id_pt2_lop' => 300, 'pt2_project_id' => 30, 'lop_name' => 'LOP PT2 ADMIN A', 'branch' => 'KUPANG', 'sto' => 'SOE', 'status_progress' => 'instalasi', 'is_golive' => 0, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pt2_assignments')->insert(['pt2_project_id' => 30, 'pt2_lop_id' => 300, 'assigned_by' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('pt2_evidences')->insert(['pt2_project_id' => 30, 'pt2_lop_id' => 300, 'uploaded_by' => 4, 'stage' => 'instalasi', 'evidence_type' => 'progress', 'status' => 'pending', 'created_at' => now()->subHours(30), 'updated_at' => now()]);
    }
}
