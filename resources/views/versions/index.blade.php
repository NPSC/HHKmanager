@extends('layouts.app')

@section('content')
    <div class="row">
	    <div class="col-12">
		    @include("partials/messages")
	        <div class="card">
		        <div class="card-header">
			        Available Versions <a href="#" class="ml-2 mr-2 mr-sm-2 btn btn-success" data-toggle="modal" data-target="#createversion"><i class="fa fa-plus"></i> New Version</a>
		        </div>
	            <div class="card-body">
		            <div class="table-responsive">
			            <table class="table table-bordered" width="100%" cellspacing="0">
				            <thead>
					            <tr>
						            <th>Version</th>
						            <th>Path</th>
						            <th>Type</th>
						            <th>Release Date</th>
						            <th>Enabled</th>
						            <th>Actions</th>
					            </tr>
				            </thead>
				            <tbody>
							@if(count($versions) > 0)
							@foreach($versions as $version)
							<tr>
								<td>{{ $version->name }}</td>
								<td>{{ $version->filepath }}</td>
								<td>@if($version->patch)
										Patch
									@else
										Full
									@endif
								</td>
								<td>{{ $version->release_date->format('M d, Y') }}</td>
								<td id="status">@if($version->trashed())
										No
									@else
										Yes
									@endif
								</td>
								<td>
									<div class="btn-group btn-spacing-btm" role="group">
										<button id="btnGroupDropActions" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
										<div class="dropdown-menu" aria-labelledby="btnGroupDropActions">
											@if($version->release_notes)
											<button data-toggle="modal" data-target="#releasenotes" class="dropdown-item" id="getNotes" data-url="{{ route('versions.json',['id'=>$version->id])}}">
												<i class="fa fa-file" aria-hidden="true"></i> View Release Notes
											</button>
											@endif
											
												<button @if(!$version->trashed()) style="display:none;" @endif class="dropdown-item" id="enable" data-url="{{ route('versions.restore', ['id'=>$version->id])}}">
													<i class="fa fa-check-circle-o" aria-hidden="true"></i> Enable
												</button>
												{!! Form::open(['route' => ['versions.destroy', $version->id], 'method' => 'delete']) !!}
													<input type="submit" @if(!$version->trashed()) style="display:none;" @endif class="dropdown-item" value="Delete" >
												{!! Form::close() !!}
												<button @if($version->trashed()) style="display:none;" @endif class="dropdown-item" id="disable" data-url="{{ route('versions.destroy', ['id'=>$version->id])}}">
													<i class="fa fa-times-circle-o" aria-hidden="true"></i> Disable
												</button>
										</div>
									</div>
								</td>
							</tr>
							@endforeach
							@endif
							@if(count($deletedVersions) > 0)
							@foreach($deletedVersions as $version)
							<tr class="text-muted">
								<td>{{ $version->name }}</td>
								<td>{{ $version->filepath }}</td>
								<td>@if($version->patch)
										Patch
									@else
										Full
									@endif
								</td>
								<td>{{ $version->release_date->format('M d, Y') }}</td>
								<td id="status">No</td>
								<td>
									<div class="btn-group btn-spacing-btm" role="group">
										<button id="btnGroupDropActions" type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
										<div class="dropdown-menu" aria-labelledby="btnGroupDropActions">
											@if($version->release_notes)
											<button data-toggle="modal" data-target="#releasenotes" class="dropdown-item" id="getNotes" data-url="{{ route('versions.json',['id'=>$version->id])}}">
												<i class="fa fa-file" aria-hidden="true"></i> View Release Notes
											</button>
											@endif
											
												<button @if(!$version->trashed()) style="display:none;" @endif class="dropdown-item" id="enable" data-url="{{ route('versions.restore', ['id'=>$version->id])}}">
													<i class="fa fa-check-circle-o" aria-hidden="true"></i> Enable
												</button>
												{!! Form::open(['route' => ['versions.destroy', $version->id], 'method' => 'delete']) !!}
													<input type="submit" @if(!$version->trashed()) style="display:none;" @endif class="dropdown-item" value="Delete" >
												{!! Form::close() !!}
												<button @if($version->trashed()) style="display:none;" @endif class="dropdown-item" id="disable" data-url="{{ route('versions.destroy', ['id'=>$version->id])}}">
													<i class="fa fa-times-circle-o" aria-hidden="true"></i> Disable
												</button>
										</div>
									</div>
								</td>
							</tr>
							@endforeach
							@endif
				            </tbody>
			            </table>
		            </div>
	            </div>
	        </div>
	    </div>
    </div>
    @include('versions.create')
    @include('versions.releasenotes')
@endsection

@section('scripts')
<script type="text/javascript">
	
	$(document).ready(function(){

	$.ajaxSetup({
		headers: {
			'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
  		}
	});

    $(document).on('click', '#disable', function(e){

        e.preventDefault();

        var url = $(this).data('url');
        var tr_obj = $(this).parents("tr");

        $.ajax({
            url: url,
            type: 'DELETE',
            dataType: 'json'
        })
        .done(function(data){
	        console.log(data);
	        
            if(data == true){
	            console.log($(this).parents('tr'));
            	tr_obj.find("#status").html("No");
            	tr_obj.find("#enable").show();
            	tr_obj.find("#disable").hide();
            	tr_obj.find("#delete").show();
            	
            }else{
	            tr_obj.find('#status').html("Yes");
	            tr_obj.find("#enable").hide();
            	tr_obj.find("#disable").show();
            	tr_obj.find("#delete").hide();
            }
        })
        .fail(function(){
	        
        });

    });
    
    $(document).on('click', '#enable', function(e){

        e.preventDefault();

        var url = $(this).data('url');
        var tr_obj = $(this).parents("tr");

        $.ajax({
            url: url,
            type: 'PUT',
            dataType: 'json'
        })
        .done(function(data){
	        console.log(data);
	        
            if(data == true){
	            console.log($(this).parents('tr'));
            	tr_obj.find("#status").html("No");
            	tr_obj.find("#enable").show();
            	tr_obj.find("#disable").hide();
            }else{
	            tr_obj.find('#status').html("Yes");
	            tr_obj.find("#enable").hide();
            	tr_obj.find("#disable").show();
            }
        })
        .fail(function(){
	        
        });

    });

});
	
</script>
@append
