@extends('layouts.app', ['title' => 'Target — ' . $target->hostname])
@section('content')
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem"><div><h1 style="color:#fff">Target Details</h1><p style="color:var(--text-muted)">{{ $target->normalized_url }}</p></div><a class="btn btn-secondary" href="{{ route('projects.show',$target->project_id) }}">&larr; Project</a></div>
<div class="card"><div class="card-header"><h2 class="card-title">{{ $target->hostname }}</h2><span class="badge badge-info">{{ ucfirst($target->status) }}</span></div>
<table class="meta-table"><tr><td>Input URL</td><td>{{ $target->input_url }}</td></tr><tr><td>Normalized URL</td><td>{{ $target->normalized_url }}</td></tr><tr><td>Recon Results</td><td>{{ $target->reconResults->count() }}</td></tr><tr><td>Scan Runs</td><td>{{ $target->scanRuns->count() }}</td></tr><tr><td>Findings</td><td>{{ $target->findings->count() }}</td></tr></table>
</div>
@endsection
