<div class="modal fade" id="createsite" role="dialog" aria-labelledby="createsiteLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="createsiteLabel">New Site</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      {!! Form::open(['route' => 'sites.store', 'method' => 'post', 'class'=>'form']) !!}
      <div class="modal-body">
	      <div class="container-fluid">
	      	<div class="row">
		      	<div class="col-12">
		      		<div class="form-group">
			      		{!! Form::label('path', 'URL', ['class'=>'sr-only']); !!}
			      		<div class="input-group">
				      		<div class="input-group-prepend">
					      		<div class="input-group-text">hhk.net/demo/</div>
				      		</div>
					  		{!! Form::text('path', null, ['class'=>'form-control', 'placeholder'=>'URL']); !!}
			      		</div>
		      		</div>
		      		<div class="form-group">
			      		{!! Form::label('version', 'Version', ['class'=>'sr-only']); !!}
			      		<select class="form-control{{ $errors->has('version') ? ' form-control-danger' : '' }}  select2-version" name="version">
						<option></option>
						@foreach($versions as $version)
							@if(old('version') == $version->id)
								<option value='{{ $version->id }}' selected>{{ $version->name }}</option>
							@elseif($version->patch == false)
								<option value='{{ $version->id }}'>{{ $version->name }}</option>
							@endif
						@endforeach
					</select>
		      		</div>
		      	</div>
	      	</div>
	      </div>
      </div>
      <div class="modal-footer">
	      {!! Form::submit('Add', ['class'=>'btn btn-success']) !!}
      </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>

@section('scripts')
<script type="text/javascript">
	$(document).ready(function(){
		$(".select2-version").select2({
			placeholder: "Select Version"
		});		
	});
</script>
@append