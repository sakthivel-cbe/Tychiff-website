@extends('layouts.foundation')

@section('title', 'Create JD Draft')

@section('content')
<section class="panel" aria-labelledby="page-title">
    <a class="back-link" href="{{ route('job-descriptions.index', $requirement) }}">← JD versions</a>
    <p class="eyebrow">{{ $requirement->reference }} · {{ $requirement->clientOrganization->name }}</p>
    <h1 id="page-title">Create JD Draft</h1>
    <p class="lede">This draft is linked to the Requirement. Content is not client-approved until Management records the client's decision.</p>
    <form class="form-stack" method="post" action="{{ $action }}">
        @csrf
        @include('job-descriptions._fields')
        <div class="form-links"><button class="button" type="submit">Save manual draft</button><a class="button secondary" href="{{ route('job-descriptions.index', $requirement) }}">Cancel</a></div>
    </form>
</section>
@endsection
