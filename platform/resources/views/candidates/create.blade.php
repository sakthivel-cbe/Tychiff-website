@extends('layouts.foundation')

@section('title', 'Create Candidate')

@section('content')
<section class="panel" aria-labelledby="page-title">
    <a class="back-link" href="{{ route('candidates.index') }}">← Candidates</a>
    <p class="eyebrow">Recruitment database</p>
    <h1 id="page-title">Create candidate profile</h1>
    <p class="lede">TYCHIFF maintains one shared candidate record. If matching contact details already exist, no second profile will be created.</p>
    <form class="form-stack" method="post" action="{{ route('candidates.store') }}">@csrf
        @include('candidates._fields')
        <div class="form-links"><button class="button" type="submit">Save candidate profile</button><a class="button secondary" href="{{ route('candidates.index') }}">Cancel</a></div>
    </form>
</section>
@endsection
