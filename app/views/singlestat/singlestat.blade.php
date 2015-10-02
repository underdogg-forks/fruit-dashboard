@extends('meta.base-user')

@section('pageTitle')
Widget stats
@stop

@section('pageStylesheet')
@stop

@section('pageContent')
<div class="container">
  <div class="row margin-top">
    <div class="col-md-10 col-md-offset-1">
      <div class="panel panel-default panel-transparent">
        <div class="panel-body">

          <h1 class="text-center">
            {{ $widget->descriptor->name }}
          </h1> <!-- /.text-center -->

          <div class="row">

            <!-- Nav tabs -->
            <div class="col-md-12 text-center">
              <ul class="nav nav-pills center-pills" role="tablist">
                @foreach ($widget->resolution() as $resolution=>$value)
                    <li role="presentation"><a href="#singlestat-{{ $resolution }}" aria-controls="singlestat-{{ $resolution }}" role="tab" data-toggle="pill" data-resolution="{{$resolution}}">{{$value}}</a></li>
                @endforeach
              </ul>
            </div> <!-- /.col-md-12 -->


            <!-- Tab panes -->
              <div class="tab-content">
                @foreach ($widget->resolution() as $resolution=>$value)
                  <div role="tabpanel" class="tab-pane fade col-md-12" id="singlestat-{{ $resolution }}">
                    {{-- Check Premium feature and disable charts if needed --}}
                    @if (!Auth::user()->subscription->getSubscriptionInfo()['PE'])
                      {{-- Allow the default chart, disable others --}}
                      @if ($resolution != $widget->getSettingsFields()['resolution']['default'])
                        @include('singlestat.singlestat-premium-feature-needed')
                      @else
                        @include('singlestat.singlestat-element')
                      @endif
                    @else
                      @include('singlestat.singlestat-element')
                    @endif
                  </div> <!-- /.col-md-12 -->
                @endforeach
              </div> <!-- /.tab-content -->

          </div> <!-- /.row -->

          <div class="row">
            <div class="col-md-12 text-center">
              <a href="{{ URL::route('dashboard.dashboard') }}?active={{ $widget->dashboard->id }}" class="btn btn-primary">Back to your dashboard</a>
            </div> <!-- /.col-md-12 -->
          </div> <!-- /.row -->

        </div> <!-- /.panel-body -->
      </div> <!-- /.panel -->
    </div> <!-- /.col-md-10 -->

  </div> <!-- /.row -->


  @stop

  @section('pageScripts')

  <!-- FDGeneral* classes -->
  <script type="text/javascript" src="{{ URL::asset('lib/FDCanvas.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('lib/FDChart.js') }}"></script>
  <script type="text/javascript" src="{{ URL::asset('lib/FDChartOptions.js') }}"></script>
  <!-- /FDGeneral* classes -->

  <!-- FDAbstractWidget* classes -->
  <script type="text/javascript" src="{{ URL::asset('lib/widgets/FDHistogramWidget.js') }}"></script>
  <!-- /FDAbstractWidget* classes -->

  <!-- FDWidget* classes -->
  <script type="text/javascript" src="{{ URL::asset('lib/widgets/'.$widget->descriptor->category.'/FD'. Utilities::underscoreToCamelCase($widget->descriptor->type).'Widget.js') }}"></script>
  <!-- /FDWidget* classes -->

  <!-- Init FDChartOptions -->
  <script type="text/javascript">
      new FDChartOptions({data:{page: 'singlestat'}}).init();
  </script>
  <!-- /Init FDChartOptions -->

  <script type="text/javascript">
    @foreach ($widget->resolution() as $resolution=>$value)
      var widgetOptions{{ $resolution }} = {
          general: {
            id:    '{{ $widget->id }}',
            name:  '{{ $widget->name }}',
            type:  '{{ $widget->descriptor->type }}',
            state: '{{ $widget->state }}',
          },
          features: {
            drag:    false,
          },
          urls: {},
          selectors: {
            widget: '#panel-{{ $resolution }}',
            graph:  '#chart-{{ $resolution }}'
          },
          data: {
            page: 'singlestat',
            init: 'widgetData{{ $resolution }}',
          }
      }

      var widgetData{{ $resolution }} = {
        'labels': [@foreach ($widget->getData()['labels'] as $datetime) "{{$datetime}}", @endforeach],
        'datasets': [
        @foreach ($widget->getData()['datasets'] as $dataset)
          {
              'values' : [{{ implode(',', $dataset['values']) }}],
              'name':  "{{ $dataset['name'] }}",
              'color': "{{ $dataset['color'] }}"
          },
        @endforeach
        ]
      }
    @endforeach

    $(document).ready(function () {
      // Show first tab
      $('.nav-pills a:first').tab('show');
      
      // Create graph objects
      @foreach ($widget->resolution() as $resolution=>$value)
        FDWidget{{ $resolution }} = new window['FD{{ Utilities::underscoreToCamelCase($widget->descriptor->type)}}Widget'](widgetOptions{{ $resolution }});
      @endforeach

      // Show graph on change
      $('a[data-toggle="pill"]').on('shown.bs.tab', function (e) {
        window['FDWidget' + $(e.target).data('resolution')].reinit();
      });
    });
  </script>
  @append