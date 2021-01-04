@foreach ($options as $key => $value)
  @if (is_array($value))
    {{-- optgroup --}}
    <optgroup label="{{$key}}"></optgroup>
            @include('components.select_options', ['options' => $value])
    </optgroup>
  @else
    {{-- option --}}
    <option value="{{ $value }}">{{ is_int($key) ? $value : $key }}</option>
  @endif
@endforeach