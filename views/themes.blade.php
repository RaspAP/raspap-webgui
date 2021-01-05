@extends('layouts.app')
@section('content')
<div class="row">
  <div class="col-lg-12">
    <div class="card">
      <div class="card-header">
        <div class="row">
          <div class="col">
            <i class="fas fa-paint-brush mr-2"></i>{{ _("Change Theme") }}
          </div>
        </div><!-- /.row -->
      </div><!-- /.card-header -->
      <div class="card-body">
        <h4>{{ _("Theme settings") }}</h4>
        <div class="row">
          <div class="form-group col-xs-3 col-sm-3">
            <label for="code">{{ _("Select a theme") }}</label>
            @include('components.select', ['name'=>"theme", 'options'=>$themes, 'selected'=>$selectedTheme, 'id'=>"theme-select"])
          </div>
          <div class="col-xs-3 col-sm-3">
            <label for="code">{{ _("Color") }}</label>
            <input class="form-control color-input" value="#d8224c" aria-label="color" />
          </div>
        </div>
        <form action="system_info" method="POST">
            {!! CSRFTokenFieldTag() !!}
          <a href="{!! $_GET['page'] !!}" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> {{ _("Refresh") }}</a>
        </form>
      </div><!-- /.card-body -->
      <div class="card-footer"></div>
    </div><!-- /.card -->
  </div><!-- /.col-lg-12 -->
</div><!-- /.row -->
@endsection

@section('footer_scripts')
    <script src='dist/huebee/huebee.pkgd.min.js'></script>
    <script src='app/js/huebee.js'></script>
@endsection