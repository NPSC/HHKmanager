@extends('layouts.app')

@section('content')
@include("partials/messages")
	<h1 class="mb-4">Demo Sites 
		<button class="ml-2 mr-2 mr-sm-2 btn btn-success" data-toggle="modal" data-target="#createsite"><i class="fa fa-plus"></i> New Site</button>
		<button class="ml-2 mr-2 mr-sm-2 btn btn-success" data-toggle="modal" data-target="#importsite"><i class="fa fa-plus"></i> Import Existing Site</button>
	</h1>
    <div class="row">
	    @foreach($sites as $site)
		    @if($site->mode == 'demo')
	        <div class="col-md-3 mb-4">
	            <div class="card">
	                <div class="card-body">
	                    <h3>{{ $site->name }}</h3>
	                    <p>{!! $site->config['site']['Site_Name'] !!}<br>
		                    @if($site->city)
								{{ $site->city->City }}, {{ $site->city->State }}
							@else
								City Not Found
							@endif
	                    <br><strong>Version:</strong> {{ $site->config['code']['Version'] }} build {{ $site->config['code']['Build'] }}
	                    <br><strong>Price Model:</strong> {{ $site->priceModel }}
	                    <br><strong>Rooms:</strong> {{ $site->roomCount }}
	                    <br><strong>Accessed:</strong> {{ $site->lastAccessed->timestamp->setTimezone('America/Chicago')->format('M j, Y g:i a') }}<br>
		                   <strong>By:</strong> {{ $site->lastAccessed->Source_Code }}
	                </div>
	                <div class="card-footer text-right">
		                @if($site->url)
		                	<a href="https://hospitalityhousekeeper.net/{{ $site->url }}" target="_blank" class="btn btn-sm btn-primary">Visit Site</a>
		                @endif
		                <div class="btn-group ml-2">
			                <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="actionsButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Actions</button>
			                <div class="dropdown-menu" aria-labelledby="actionsButton">
				                <button data-toggle="modal" data-target="#siteconfig" class="dropdown-item" id="getConfig" data-url="{{ route('sites.getConfig',['id'=>$site->id])}}">Config</button>
				                <button data-toggle="modal" data-target="#sitecomments" class="dropdown-item" id="getComments" data-url="{{ route('sites.getComments',['id'=>$site->id])}}">Comments</button>
				                <button disabled data-toggle="modal" data-target="#upgradesite" class="dropdown-item" id="upgrade" data-site_id="{{ $site->id }}">Upgrade</button>
			                </div>
		                </div>
	                </div>
	            </div>
	        </div>
	        @endif
        @endforeach
    </div>
    @include('sites.create')
    @include('sites.import')
    @include('sites.upgrade')
    @include('sites.config')
    @include('users.setpw')
    @include('sites.comments')

@endsection

@section('scripts')
	<script type="text/javascript">
		$(document).ready(function(){
		    var resetpw = "<?php echo Auth::user()->temp_password ?>";
		    if(resetpw){
			    $('#setpw').modal('show');
		    }
	    });
	</script>
@append