<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\CandidateNote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CandidateNoteController extends Controller
{
    public function store(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('addNote', $candidate);
        $validated = $request->validate(['note' => ['required', 'string', 'min:2', 'max:10000']]);

        $note = CandidateNote::query()->create([
            'candidate_id' => $candidate->getKey(),
            'note' => $validated['note'],
            'created_by' => $request->user()->getKey(),
            'created_at' => now(),
        ]);

        AuditLog::record('candidate.note_added', [
            'entity_type' => Candidate::class,
            'entity_id' => (string) $candidate->getKey(),
            'new_state' => ['note_id' => $note->getKey()],
        ]);

        return back()->with('status', 'Internal candidate note added.');
    }
}
