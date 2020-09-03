<div class="modal fade" id="setvmrpw" role="dialog" aria-labelledby="setvmrpwLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="setpwLabel">Set VM Racks Password</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      {!! Form::open(['route' => ['users.setvmrpassword'], 'method'=>'PUT']) !!}
      <div class="modal-body">
	      <div class="container-fluid">
	      	<div class="row">
		      	<div class="col-12">
		      		<div class="card mb-3">
		      			<div class="card-body">
		      				<strong>User hhkapp exires in {{ $hhkappExp }} days</strong><br><br>
		      				To set hhkapp password, set it in VM Racks PMP first (be sure to check "Update remote resource"), then paste it here.
		      			</div>
		      		</div>
		      		
			      	<div class="form-row">
			      		<div class="form-group col-md-6">
				      		{!! Form::label('password', 'Password', ['class'=>'sr-only']); !!}
				      		{!! Form::password('password', ['class'=>'form-control', 'placeholder'=>'Password']); !!}
			      		</div>
			      		<div class="form-group col-md-6">
				      		{!! Form::label('password_confirmation', 'Confirm password', ['class'=>'sr-only']); !!}
				      		{!! Form::password('password_confirmation', ['class'=>'form-control', 'placeholder'=>'Confirm password']); !!}
			      		</div>
			      	</div>
		      	</div>
	      	</div>
	      </div>
      </div>
      <div class="modal-footer">
	      {!! Form::submit('Save', ['class'=>'btn btn-success']) !!}
      </div>
      {!! Form::close() !!}
    </div>
  </div>
</div>