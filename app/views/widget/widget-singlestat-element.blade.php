<div class="chart-container">
  <div class="panel fill panel-default panel-transparent">
    <div class="panel-heading">
      <h3 class="panel-title">
        @if (($widget->getSettings()['resolution'] == $resolution) && ($widget->state != 'hidden'))
        <span
         class="drop-shadow z-top pull-right"
         data-toggle="tooltip"
         data-placement="left"
         title="This chart is currently pinned to the dashboard">
         <span class="label label-success label-as-badge valign-middle">
          <span class="icon fa fa-tag">
          </span>
          </span>
        </span>
        @else
        <a href="{{ route('widget.pin-to-dashboard', array($widget->id, $resolution)) }}"
         class="drop-shadow z-top no-underline pull-right"
         data-toggle="tooltip"
         data-placement="left"
         title="Pin this chart to the dashboard">
         <span class="label label-info label-as-badge valign-middle">
           <span class="icon fa fa-thumb-tack">
           </span>
         </span>
        </a>
        @endif
        {{ $value }} statistics
      </h3>
    </div>

    <div class="panel-body no-padding"  id="{{ $resolution }}-chart-container">
      <canvas id="{{ $resolution }}-chart" class='img-responsive canvas-auto' width="300" height="300"></canvas>
    </div> <!-- /.panel-body -->

  </div> <!-- /.panel -->
</div> <!-- /.chart-container -->
<div class="panel fill panel-default panel-transparent">
  <div class="panel-heading">
    <h3 class="panel-title">{{ $value }} data history</h3>
  </div>
  <div class="panel-body">
    <div class="row">
      <div class="col-sm-3">
        <div class="panel panel-default panel-transparent">
          <div class="panel-body text-center">
            {{-- 5 years ago --}}
            {{-- 6 months ago --}}
            {{-- 12 weeks ago --}}
            {{-- 30 days ago --}}
            <h3>70</h3>
            <div class="text-success">
              <span class="fa fa-arrow-up"> </span>
              {{-- compared to current value in percent --}}
              1200%
            </div> <!-- /.text-success -->
            <p><small>30 days ago</small></p>
          </div> <!-- /.panel-body -->
        </div> <!-- /.panel -->
      </div> <!-- /.col-sm-3 -->
      <div class="col-sm-3">
        <div class="panel panel-default panel-transparent">
          <div class="panel-body text-center">
            {{-- 3 years ago --}}
            {{-- 3 months ago --}}
            {{-- 4 weeks ago --}}
            {{-- 7 days ago --}}
            <h3>2999</h3>
            <div class="text-success">
              <span class="fa fa-arrow-up"> </span>
              {{-- compared to current value in percent --}}
              120%
            </div> <!-- /.text-success -->
            <p><small>7 days ago</small></p>
          </div> <!-- /.panel-body -->
        </div> <!-- /.panel -->
      </div> <!-- /.col-sm-3 -->
      <div class="col-sm-3">
        <div class="panel panel-default panel-transparent">
          <div class="panel-body text-center">
            {{-- 1 year ago --}}
            {{-- 1 month ago --}}
            {{-- 1 week ago --}}
            {{-- 1 day ago --}}
            <h3>3876</h3>
            <div class="text-success">
              <span class="fa fa-arrow-up"> </span>
              13%
            </div> <!-- /.text-success -->
            <p><small>1 day ago</small></p>
          </div> <!-- /.panel-body -->
        </div> <!-- /.panel -->
      </div> <!-- /.col-sm-3 -->
      <div class="col-sm-3">
        <div class="panel panel-default panel-transparent">
          <div class="panel-body text-center">
            <h3 class="text-primary">5760</h3>
            <div class="text-success">
              <span class="fa fa-check"> </span>
            </div> <!-- /.text-success -->
            <p><small>Current value</small></p>
          </div> <!-- /.panel-body -->
        </div> <!-- /.panel -->
      </div> <!-- /.col-sm-3 -->
    </div> <!-- /.row -->
  </div> <!-- /.panel-body -->
</div> <!-- /.panel -->