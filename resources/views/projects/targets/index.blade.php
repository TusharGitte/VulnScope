@extends('layouts.app', ['title' => 'Targets — ' . $project->name])
@section('content')
<div class="card-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem;">
  <div><h1 class="card-title">Assessment Targets</h1><p style="color:var(--text-muted)">{{ $project->name }}</p></div>
  <a class="btn btn-primary" href="{{ route('targets.create', $project) }}">+ Add Target</a>
</div>
<div class="card">
@if($targets->isEmpty())<p style="color:var(--text-muted)">No target configured.</p>@else
<div class="table-responsive"><table class="data-table"><thead><tr><th>URL</th><th>Hostname</th><th>Status</th><th></th></tr></thead><tbody>
@foreach($targets as $target)<tr><td>{{ $target->normalized_url }}</td><td><code>{{ $target->hostname }}</code></td><td>{{ ucfirst($target->status) }}</td><td style="text-align:right"><a class="btn btn-secondary btn-sm" href="{{ route('targets.show', [$project,$target]) }}">View</a> <a class="btn btn-secondary btn-sm" href="{{ route('targets.edit', [$project,$target]) }}">Edit</a></td></tr>@endforeach
</tbody></table></div>
@endif
</div>
@endsection
