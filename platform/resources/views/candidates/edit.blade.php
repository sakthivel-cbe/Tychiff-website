@extends('layouts.foundation')

@section('title', 'Edit Candidate')

@section('content')
<section class="panel" aria-labelledby="page-title">
    <a class="back-link" href="{{ route('candidates.show', $candidate) }}">← {{ $candidate->reference }}</a>
    <p class="eyebrow">Candidate profile</p>
    <h1 id="page-title">Edit {{ $candidate->full_name }}</h1>
    <form class="form-stack" method="post" action="{{ route('candidates.update', $candidate) }}">@csrf @method('patch')
        @include('candidates._fields')
        <div class="form-links"><button class="button" type="submit">Save changes</button><a class="button secondary" href="{{ route('candidates.show', $candidate) }}">Cancel</a></div>
    </form>
</section>
@endsection
