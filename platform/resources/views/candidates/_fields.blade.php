<div class="form-grid">
    <label class="field"><span>Full name</span><input name="full_name" maxlength="180" required value="{{ old('full_name', $candidate['full_name'] ?? '') }}"></label>
    <label class="field"><span>Email</span><input type="email" name="email" maxlength="254" value="{{ old('email', $candidate['email'] ?? '') }}" autocomplete="off"></label>
    <label class="field"><span>Mobile</span><input type="tel" name="mobile" maxlength="40" value="{{ old('mobile', $candidate['mobile'] ?? '') }}" autocomplete="off"></label>
    <label class="field"><span>Alternate contact</span><input name="alternate_contact" maxlength="100" value="{{ old('alternate_contact', $candidate['alternate_contact'] ?? '') }}"></label>
    <label class="field"><span>Location</span><input name="location" maxlength="180" value="{{ old('location', $candidate['location'] ?? '') }}"></label>
    <label class="field"><span>Current / most recent designation</span><input name="current_designation" maxlength="180" value="{{ old('current_designation', $candidate['current_designation'] ?? '') }}"></label>
    <label class="field"><span>Total experience (years)</span><input type="number" min="0" max="80" step="0.25" name="total_experience_years" value="{{ old('total_experience_years', $candidate['total_experience_years'] ?? '') }}"></label>
    <label class="field"><span>Relevant experience (years)</span><input type="number" min="0" max="80" step="0.25" name="relevant_experience_years" value="{{ old('relevant_experience_years', $candidate['relevant_experience_years'] ?? '') }}"></label>
    <label class="field span-all"><span>Primary skills</span><textarea name="primary_skills" rows="3" maxlength="10000">{{ old('primary_skills', $candidate['primary_skills'] ?? '') }}</textarea></label>
    <label class="field span-all"><span>Secondary skills</span><textarea name="secondary_skills" rows="3" maxlength="10000">{{ old('secondary_skills', $candidate['secondary_skills'] ?? '') }}</textarea></label>
    <label class="field"><span>Highest qualification</span><input name="highest_qualification" maxlength="180" value="{{ old('highest_qualification', $candidate['highest_qualification'] ?? '') }}"></label>
    <label class="field"><span>Current / last employer</span><input name="current_employer" maxlength="180" value="{{ old('current_employer', $candidate['current_employer'] ?? '') }}"></label>
    <label class="field"><span>Current CTC (include currency/unit if known)</span><input name="current_ctc" maxlength="100" value="{{ old('current_ctc', $candidate['current_ctc'] ?? '') }}"></label>
    <label class="field"><span>Expected CTC (include currency/unit if known)</span><input name="expected_ctc" maxlength="100" value="{{ old('expected_ctc', $candidate['expected_ctc'] ?? '') }}"></label>
    <label class="field"><span>Notice period (days)</span><input type="number" min="0" max="3650" name="notice_period_days" value="{{ old('notice_period_days', $candidate['notice_period_days'] ?? '') }}"></label>
    <label class="field"><span>Preferred location</span><input name="preferred_location" maxlength="180" value="{{ old('preferred_location', $candidate['preferred_location'] ?? '') }}"></label>
    <label class="field"><span>Preferred work mode</span><select name="preferred_work_mode"><option value="">Not specified</option>@foreach (['On-site', 'Hybrid', 'Remote', 'Flexible'] as $mode)<option value="{{ $mode }}" @selected(old('preferred_work_mode', $candidate['preferred_work_mode'] ?? '') === $mode)>{{ $mode }}</option>@endforeach</select></label>
    <label class="field"><span>Availability</span><input name="availability" maxlength="64" placeholder="For example: immediately, 30 days" value="{{ old('availability', $candidate['availability'] ?? '') }}"></label>
    <label class="field"><span>Candidate source</span><select name="source" required>@foreach (\App\Models\Candidate::SOURCES as $source)<option value="{{ $source }}" @selected(old('source', $candidate['source'] ?? '') === $source)>{{ $source }}</option>@endforeach</select></label>
    <label class="field span-all"><span>Source details</span><input name="source_details" maxlength="180" value="{{ old('source_details', $candidate['source_details'] ?? '') }}"></label>
    <label class="field span-all"><span>Profile summary</span><textarea name="profile_summary" rows="5" maxlength="30000">{{ old('profile_summary', $candidate['profile_summary'] ?? '') }}</textarea></label>
</div>
<p class="notice info">Provide at least one contact method. Candidate access is limited to the creating Recruiter until access is granted by Recruitment Manager or Management.</p>
<p class="notice warning">The current published privacy notice does not yet cover candidate workflows. Use test data only until TYCHIFF approves and publishes candidate-specific privacy and retention information.</p>
