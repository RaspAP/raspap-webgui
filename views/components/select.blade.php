{{--
 * Display a selector field for a form. Arguments are:
 *
 * @param string $name:     Field name
 * @param array  $options:  Array of options
 * @param string $selected: Selected option (optional) if $options is an associative array this should be the key
 * @param string $id:       the id of the select (optional)
 * @param string $event:    onChange event (optional)
 * @param string $disabled  (optional)

 function SelectorOptions($name, $options, $selected = null, $id = null, $event = null, $disabled = null)
--}}
<?php
  $optIsAssoc = array_keys($options) !== range(0, count($options) - 1);
?>

<select class="form-control" name="{{$name}}"
    @if(isset($id))
        id="{!! $id !!}"
    @endif
    @if (isset($event))
        onChange="{!! $event !!}()"
    @endif
    >
    @foreach ($options as $opt => $label)
        <?php
          $key = $optIsAssoc ? $opt : $label;
        ?>

        <option value="{{$key}}"

          @if (isset($selected) && $key == $selected)
              selected="selected"
          @endif
          @if(isset($disabled) && $key == $disabled)
            disabled
          @endif
        >{{$label}}</option>
    @endforeach
</select>
