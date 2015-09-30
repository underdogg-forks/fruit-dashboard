<div class="chart-data">
  <div class="chart-name">
    {{ $widget->getSettings()['name'] }}
  </div> <!-- /.chart-name -->
  <div class="chart-value">
    @if ($widget->state == 'active')
      @if ( ! $widget->hasCumulative())
      {{ Utilities::formatNumber($widget->getLatestValues()['value'], $widget->getFormat()) }}
      @else
      {{ Utilities::formatNumber($widget->getDiff($widget->getSettings()['length'])['value'], $widget->getFormat()) }}
      @endif
    @endif
  </div> <!-- /.chart-value -->
</div> <!-- /.chart-data -->

<div class="chart-diff-data text-center">

  @if ($widget->getDiff()['value'] >= 0)
    <div class="chart-diff text-success">
      <span class="fa fa-arrow-up chart-diff-icon"> </span>
  @else
    <div class="chart-diff text-danger">
      <span class="fa fa-arrow-down chart-diff-icon"> </span>
  @endif
    <span class="chart-diff-value">{{ Utilities::formatNumber($widget->getDiff()['value'], $widget->getFormat()) }}</span>
  </div> <!-- /.chart-diff -->


  <div class="chart-diff-dimension">
    <small>(a {{ rtrim($widget->getSettings()['resolution'], 's') }} ago)</small>
  </div> <!-- /.chart-diff-dimension -->
</div> <!-- /.chart-diff-data -->

<div id="chart-container-{{ $widget->id }}" class="clickable">
  <canvas id="chart-{{ $widget->id }}" class="chart chart-line"></canvas>
</div>

@section('widgetScripts')
<script type="text/javascript">
  // Set chart data
  var widgetData{{ $widget->id }} = {
    'labels': [@foreach ($widget->getData()['labels'] as $datetime) "{{$datetime}}", @endforeach],
     @foreach ($widget->getData()['datasets'] as $dataset)
      'datasets': [{
        'values': [@foreach ($dataset['values'] as $value) {{$value}}, @endforeach],
        'color': '{{ $dataset['color'] }}'
      }]
     @endforeach
  }

  // // Detecting clicks and drags.
  // // Redirect to single stat page on click.
  // var isDragging = false;
  // container
  //   .mousedown(function() {
  //       isDragging = false;
  //   })
  //   .mousemove(function() {
  //       isDragging = true;
  //    })
  //   .mouseup(function() {
  //       var wasDragging = isDragging;
  //       isDragging = false;
  //       if (!wasDragging) {
  //         window.location = "{{ route('widget.singlestat', $widget->id) }}";
  //       }
  //   });
</script>
@append