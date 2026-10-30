@extends('layouts.foundation')

@section('title', 'Requirement intake')

@section('content')
<section class="panel" aria-labelledby="page-title">
    <p class="eyebrow">Management intake</p>
    <h1 id="page-title">Receive a requirement</h1>
    <p class="lede">The intake will be sent to a Recruitment Manager for review. Recruitment cannot begin before approval and activation.</p>

    <form class="form-stack" method="post" action="{{ route('requirements.store') }}">
        @csrf
        <div class="form-grid">
            <label class="field"><span>Client organization</span>
                <select name="client_organization_id" id="client_organization_id" data-client-select>
                    <option value="">Add a client organization</option>
                    @foreach ($clients as $client)<option value="{{ $client->id }}" @selected(old('client_organization_id') == $client->id)>{{ $client->name }}</option>@endforeach
                </select>
            </label>
            <label class="field" data-new-client><span>New client organization name</span><input name="client_organization_name" maxlength="180" value="{{ old('client_organization_name') }}"></label>
            <label class="field"><span>Requirement title</span><input name="title" maxlength="180" required value="{{ old('title') }}"></label>
            <label class="field"><span>Job / position title</span><input name="job_title" maxlength="180" required value="{{ old('job_title') }}"></label>
            <label class="field"><span>Number of openings</span><input type="number" min="1" name="openings" required value="{{ old('openings', 1) }}"></label>
            <label class="field"><span>Employment / engagement type</span><input name="employment_type" maxlength="40" placeholder="Full-time, contract, etc." required value="{{ old('employment_type') }}"></label>
            <label class="field"><span>Location</span><input name="location" maxlength="180" required value="{{ old('location') }}"></label>
            <label class="field"><span>Work mode</span><select name="work_mode" required>@foreach (['On-site', 'Hybrid', 'Remote'] as $mode)<option @selected(old('work_mode') === $mode)>{{ $mode }}</option>@endforeach</select></label>
            <label class="field"><span>Salary / CTC information</span><input name="salary_ctc" maxlength="180" value="{{ old('salary_ctc') }}"></label>
            <label class="field"><span>Minimum experience (years)</span><input type="number" min="0" step="0.25" name="min_experience_years" value="{{ old('min_experience_years') }}"></label>
            <label class="field"><span>Maximum experience (years)</span><input type="number" min="0" step="0.25" name="max_experience_years" value="{{ old('max_experience_years') }}"></label>
            <label class="field"><span>Client-provided target date</span><input type="date" name="target_date" value="{{ old('target_date') }}"></label>
            <label class="field"><span>Primary TYCHIFF SPOC</span><select name="primary_spoc_id"><option value="">Not assigned</option>@foreach ($spocs as $spoc)<option value="{{ $spoc->id }}" @selected(old('primary_spoc_id') == $spoc->id)>{{ $spoc->name }}</option>@endforeach</select></label>
            <label class="field"><span>Intake source</span><select name="intake_source" required>@foreach (['Management discussion', 'Client RFP', 'Internal intake'] as $source)<option @selected(old('intake_source') === $source)>{{ $source }}</option>@endforeach</select></label>
            <label class="field span-all"><span>Source reference / intake notes</span><textarea name="source_reference" rows="3" maxlength="2000">{{ old('source_reference') }}</textarea></label>
            <label class="field span-all"><span>Required skills</span><textarea name="required_skills" rows="3" maxlength="10000">{{ old('required_skills') }}</textarea></label>
            <label class="field span-all"><span>Qualification</span><textarea name="qualification" rows="3" maxlength="10000">{{ old('qualification') }}</textarea></label>
            <label class="field span-all"><span>Job description / details supplied</span><textarea name="job_description" rows="6" maxlength="50000">{{ old('job_description') }}</textarea></label>
        </div>
        <p class="notice info">Priority starts at Normal and is set by the Recruitment Manager. This intake does not share candidate data or start recruitment.</p>
        <div class="form-links"><button class="button" type="submit">Send for review</button><a class="button secondary" href="{{ route('requirements.index') }}">Cancel</a></div>
    </form>
</section>
@endsection
