<div class="form-grid">
    <label class="field span-all"><span>Job title</span><input name="title" maxlength="180" required value="{{ old('title', $jd['title'] ?? '') }}"></label>
    <label class="field span-all"><span>Summary</span><textarea name="summary" rows="3" maxlength="10000">{{ old('summary', $jd['summary'] ?? '') }}</textarea></label>
    <label class="field span-all"><span>Responsibilities</span><textarea name="responsibilities" rows="6" maxlength="30000">{{ old('responsibilities', $jd['responsibilities'] ?? '') }}</textarea></label>
    <label class="field span-all"><span>Required skills</span><textarea name="required_skills" rows="4" maxlength="10000">{{ old('required_skills', $jd['required_skills'] ?? '') }}</textarea></label>
    <label class="field span-all"><span>Preferred skills</span><textarea name="preferred_skills" rows="3" maxlength="10000">{{ old('preferred_skills', $jd['preferred_skills'] ?? '') }}</textarea></label>
    <label class="field span-all"><span>Qualification</span><textarea name="qualification" rows="3" maxlength="10000">{{ old('qualification', $jd['qualification'] ?? '') }}</textarea></label>
    <label class="field"><span>Experience</span><input name="experience_description" maxlength="500" value="{{ old('experience_description', $jd['experience_description'] ?? '') }}"></label>
    <label class="field"><span>Location</span><input name="location" maxlength="180" value="{{ old('location', $jd['location'] ?? '') }}"></label>
    <label class="field"><span>Work mode</span><select name="work_mode"><option value="">Not set</option>@foreach (['On-site', 'Hybrid', 'Remote'] as $mode)<option value="{{ $mode }}" @selected(old('work_mode', $jd['work_mode'] ?? '') === $mode)>{{ $mode }}</option>@endforeach</select></label>
    <label class="field"><span>Employment / engagement type</span><input name="employment_type" maxlength="40" value="{{ old('employment_type', $jd['employment_type'] ?? '') }}"></label>
    <label class="field"><span>Compensation information from the Requirement (read-only)</span><input readonly aria-readonly="true" value="{{ $jd['compensation_information'] ?? '' }}"></label>
    <label class="field span-all"><span>Additional approved JD information</span><textarea name="additional_information" rows="3" maxlength="30000">{{ old('additional_information', $jd['additional_information'] ?? '') }}</textarea></label>
</div>
