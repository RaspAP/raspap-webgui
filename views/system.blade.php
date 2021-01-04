@extends('layouts.app')
@section('content')
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-cube mr-2"></i>{{ _("System") }}
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        {!! $status->showMessages(); !!}
        <form role="form" action="system_info" method="POST">
        {!! CSRFTokenFieldTag() !!}
        <ul class="nav nav-tabs" role="tablist">
          <li role="presentation" class="nav-item"><a class="nav-link active" id="basictab" href="#basic" aria-controls="basic" role="tab" data-toggle="tab">{{ _("Basic") }}</a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="languagetab" href="#language" aria-controls="language" role="tab" data-toggle="tab">{{ _("Language") }}</a></li>
          <li role="presentation" class="nav-item"><a class="nav-link" id="advancedtab" href="#advanced" aria-controls="advanced" role="tab" data-toggle="tab">{{ _("Advanced") }}</a></li>
        </ul>
          <!-- Tab panes -->
          <div class="tab-content">
            @include("system.basic")
            @include("system.language")
            @include("system.advanced")
          </div><!-- /.tab-content -->
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
@endsection
