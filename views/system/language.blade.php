<!-- language tab -->  
<div role="tabpanel" class="tab-pane" id="language">
    <h4 class="mt-3">{{ _("Language settings")  }}</h4>
    <div class="row">
      <div class="form-group col-md-6">
        <label for="code">{{ _("Select a language") }}</label>
        @include('components.select', ['name'=>'locale', 'options'=>$arrLocales, 'selected'=>$_SESSION['locale']])
      </div>
    </div>
    <input type="submit" class="btn btn-outline btn-primary" name="SaveLanguage" value="{{ _("Save settings") }}" />
    <a href="{!! $_GET['page'] !!}" class="btn btn-outline btn-primary"><i class="fas fa-sync-alt"></i> {{ _("Refresh")  }}</a>
</div>

