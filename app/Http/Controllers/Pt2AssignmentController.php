<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pt2Assignment;
use App\Models\Pt2Lop;
use App\Models\User;
use App\Services\ProjectActivityService;

class Pt2AssignmentController extends Controller
{
    // FUNGSI UNTUK ASSIGN TEKNISI PER LOP
    public function assignTeknisi(Request $request)
    {
        $request->validate([
            'pt2_project_id'   => 'required|exists:pt2_projects,id_pt2_project',
            'pt2_lop_id'       => 'required|exists:pt2_lops,id_pt2_lop',
            'assigned_user_id' => 'required|exists:users,id_user',
        ]);

        $oldAssignment = Pt2Assignment::where('pt2_lop_id', $request->pt2_lop_id)->first();
        $targetUser = User::where('id_user', $request->assigned_user_id)->first();

        // Di PT 2, kita fokus menggunakan Teknisi. 
        // Namun jika role-nya dinamis, logic-nya tetap kita amankan.
        $dataToUpdate = [
            'pt2_project_id' => $request->pt2_project_id,
            'assigned_by'    => auth()->user()->id_user,
        ];
        
        if ($targetUser->role === 'teknisi') {
            $dataToUpdate['teknisi_id'] = $targetUser->id_user;
        } else {
            // Jika suatu saat PT2 butuh waspang
            $dataToUpdate['teknisi_id'] = $targetUser->id_user; 
        }

        // SIMPAN BERDASARKAN LOP_ID (Bukan Project_ID)
        Pt2Assignment::updateOrCreate(
            ['pt2_lop_id' => $request->pt2_lop_id],
            $dataToUpdate
        );

        // LOGGING (Jika ProjectActivityService mendukung ID LOP PT2)
        $roleTitle = ucfirst($targetUser->role); 
        $isReassign = ($oldAssignment && $oldAssignment->teknisi_id);

        ProjectActivityService::log([
            // Catatan: Pastikan tabel project_activities Anda bisa menerima ID string/bigint dari PT2
            'project_id' => $request->pt2_project_id, 
            'lop_id' => $request->pt2_lop_id,
            'target_user_id' => $targetUser->id_user,
            'activity_type' => $isReassign ? 'reassign_'.$targetUser->role.'_pt2' : 'assign_'.$targetUser->role.'_pt2',
            'title' => $isReassign ? "Reassign {$roleTitle} (PT 2)" : "Assign {$roleTitle} (PT 2)",
            'description' => "LOP PT 2 di-assign ke {$roleTitle} " . $targetUser->name,
            'status_before' => $isReassign ? 'assigned' : 'unassigned',
            'status_after' => 'assigned',
            'meta' => [
                "old_teknisi_id" => $oldAssignment ? $oldAssignment->teknisi_id : null,
                "new_teknisi_id" => $targetUser->id_user,
                "new_teknisi_name" => $targetUser->name,
            ],
        ]);

        return back()->with('success', 'Assignment Teknisi ke LOP berhasil disimpan.');
    }

    // FUNGSI UNTUK REMOVE ASSIGN PER LOP
    public function removeAssign($pt2_lop_id)
    {
        $oldAssignment = Pt2Assignment::where('pt2_lop_id', $pt2_lop_id)->first();

        if (!$oldAssignment) {
            return back()->with('error', 'Data assignment tidak ditemukan.');
        }

        $pt2_project_id = $oldAssignment->pt2_project_id;

        // Hapus Assignment
        Pt2Assignment::where('pt2_lop_id', $pt2_lop_id)->delete();

        // Logging Hapus Teknisi
        if ($oldAssignment->teknisi_id) {
            ProjectActivityService::log([
                'project_id' => $pt2_project_id,
                'lop_id' => $pt2_lop_id,
                'target_user_id' => $oldAssignment->teknisi_id,
                'activity_type' => 'remove_teknisi_assignment_pt2',
                'title' => 'Assignment Teknisi Dihapus (PT 2)',
                'description' => 'Assignment Teknisi dihapus dari LOP PT 2.',
                'status_before' => 'assigned',
                'status_after' => 'unassigned',
                'meta' => ['old_teknisi_id' => $oldAssignment->teknisi_id],
            ]);
        }

        return back()->with('success', 'Assignment Teknisi berhasil dihapus dari LOP.');
    }
}