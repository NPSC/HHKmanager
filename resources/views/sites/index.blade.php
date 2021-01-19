@extends('layouts.app')
@section('site-title')
	<div class="navbar-brand text-center">{{ $mode }} Sites</div>
	<div class="nav-link dropdown">
		<button class="btn btn-sm btn-success dropdown-toggle" type="button" id="addMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-plus"></i></button>
		<div class="dropdown-menu" aria-labelledby="addMenuButton">
			<button class="dropdown-item" data-toggle="modal" data-target="#createsite">New Site</button>
    		<button class="dropdown-item" data-toggle="modal" data-target="#importsite">Add Existing Site</button>
  		</div>
	</div>
@endsection
@section('content')
@include("partials/messages")
	@if($mode == "Other")
		<div class="alert alert-danger"><i class="fa fa-exclamation-triangle"></i> The following sites have issues that need resolving, usually updating the site URL will fix issues <i class="fa fa-exclamation-triangle"></i></div>
	@endif
    <div class="row" id="sites-content">
	    <div class="col-12">
		    <table class="table table-striped" id="sitesTable">
			    <thead>
				    <th>ID</th>
				    <th>Actions</th>
				    <th>Site Name</th>
				    <th>City</th>
				    <th>Version</th>
				    <th>Price Model</th>
				    <th>Rooms</th>
				    <th>Accessed</th>
				    <th>User</th>
			    </thead>
			    <tbody>
			    @foreach($sites as $site)
			    @if(!$site->config)
			        <tr class="bg-danger text-white">
			    @else
			    	<tr>
				@endif
				    	<td>{{ $site->id }}</td>
				    	<td>
					    	<div class="btn-group ml-2">
					            <button class="btn btn-sm btn-primary dropdown-toggle" type="button" id="actionsButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Actions</button>
					            <div class="dropdown-menu" aria-labelledby="actionsButton">
						            <button data-toggle="modal" data-target="#editsite" class="dropdown-item" data-url="{{ route('sites.show', ['id'=>$site->id]) }}">Edit</button>
						            <button data-toggle="modal" data-target="#siteconfig" class="dropdown-item" id="getConfig" data-url="{{ route('sites.getConfig',['id'=>$site->id])}}">Config</button>
						            <button data-toggle="modal" data-target="#sitecomments" class="dropdown-item" id="getComments" data-url="{{ route('sites.getComments',['id'=>$site->id])}}">Comments</button>   
						        	<button data-toggle="modal" data-target="#upgradesite" class="dropdown-item" id="upgrade" data-site_id="{{ $site->id }}" data-site_name="{{ $site->siteName }}">Upgrade</button>
						        	@if($site->mode == "demo")
						        		<a href="{{ route('sites.setLive', $site) }}" class="dropdown-item">Set Live</a>
						        	@endif
						        	<button data-toggle="modal" data-target="#deletesite" class="dropdown-item" id="delete" data-site_id="{{ $site->id }}" data-site_name="{{ $site->siteName }}">Remove</button>
						        	<!--<a href="{{ route('sites.destroy', $site) }}" class="dropdown-item">Delete Site</a>-->
					            </div>
				            </div>
				    	</td>
				    	<td>
					    	@if($mode == "Live")
					    		<a href="https://{{ $site->url }}.hospitalityhousekeeper.net" target="_blank">{!! $site->siteName !!}</a>
					    	@elseif($mode == "Demo")
					    		<a href="https://hospitalityhousekeeper.net/{{ $site->url }}" target="_blank">{!! $site->siteName !!}</a>
					    	@else
					    		{!! $site->siteName !!}
					    	@endif
					    </td>
				    	<td>
						{{ $site->city }}
				    	</td>
				    	<td>
					    	@if($site->version)
					    		{{ $site->version }}
					    	@endif
					    </td>
				    	<td>{{ $site->priceModel }}</td>
				    	<td>{{ $site->roomCount }}</td>
				    	<td>
					    	@if($site->lastAccessed)
					    		{{ $site->lastAccessed->Access_Date->setTimezone('America/Chicago')->format('M j, Y g:i a') }}
					    	@endif
				    	</td>
				    	<td>
					    	@if($site->lastAccessed)
					    		{{ $site->lastAccessed->Username }}
					    	@endif
				    	</td>
			    	</tr>
		        @endforeach
			    </tbody>
		    </table>
	    </div>
    </div>
    @include('sites.create')
    @include('sites.import')
    @include('sites.upgrade')
    @include('sites.config')
    @include('users.setpw')
    @include('sites.comments')
    @include('sites.edit')

@endsection

@section('scripts')
	<script type="text/javascript">
		$(document).ready(function(){
		    var resetpw = "<?php echo Auth::user()->temp_password ?>";
		    if(resetpw){
			    $('#setpw').modal('show');
		    }
		    
			$('#sitesTable').DataTable({
				"pageLength": 50
			});

	    });
	</script>
@append