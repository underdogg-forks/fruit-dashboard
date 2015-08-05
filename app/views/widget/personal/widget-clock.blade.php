@if ($widget->getSettings()['clock_type'] == 'digital')

	<div id="digital-clock">
    <h3 id="digitTime" class="no-margin-top has-margin-vertical-sm text-white drop-shadow text-center truncate">{{ $widget['currentTime'] }}
    </h3>    
  </div> <!-- /#digital-clock -->
  

@else

  <canvas id="analog-clock"></canvas>

@endif

@section('widgetScripts')

 <!-- script for clock -->
 <script type="text/javascript">
  $(document).ready(function() {
    
    @if ($widget->getSettings()['clock_type'] == 'digital')
      
      function startTime() {
        var today = new Date();
        var h = today.getHours();
        var m = today.getMinutes();
        m = checkTime(m);
        h = checkTime(h);
        $('#digitTime').html(h + ':' + m);
        var t = setTimeout(function(){startTime()},500);
      }

      function checkTime(i) {
        if (i<10){i = "0" + i};  // add zero in front of numbers < 10
        return i;
      }

      startTime();

      $('#digitTime').hide();
      
      // fit the clock on page load
      $('#digitTime').fitText(0.3, {
        'minFontSize': 35
      });

      // bind fittext to a resize event
      $('#digitTime').bind('resize', function(e){
        $('#digitTime').fitText(0.3, {
          'minFontSize': 35
        });
      });

      $('#digitTime').fadeIn(2000);  

  @else
    var canvas = document.getElementById("analog-clock");
    var ctx = canvas.getContext("2d");
    var radius = canvas.height / 2;
    ctx.translate(radius, radius);
    radius = radius * 0.50
    setInterval(drawClock, 1000);

    function drawNumbers(ctx, radius) {
      var ang;
      var num;
      ctx.font = radius*0.15 + "px arial";
      ctx.textBaseline="middle";
      ctx.textAlign="center";
      for(num= 1; num < 13; num++){
        ang = num * Math.PI / 6;
        ctx.rotate(ang);
        ctx.translate(0, -radius*0.85);
        ctx.rotate(-ang);
        ctx.fillText(num.toString(), 0, 0);
        ctx.rotate(ang);
        ctx.translate(0, radius*0.85);
        ctx.rotate(-ang);
      }
    }
    function drawFace(ctx, radius) {
      var grad;

      ctx.beginPath();
      ctx.arc(0, 0, radius, 0, 2*Math.PI);
      ctx.fillStyle = 'white';
      ctx.fill();

      grad = ctx.createRadialGradient(0,0,radius*0.95, 0,0,radius*1.05);
      grad.addColorStop(0, '#333');
      grad.addColorStop(0.5, 'white');
      grad.addColorStop(1, '#333');
      ctx.strokeStyle = grad;
      ctx.lineWidth = radius*0.1;
      ctx.stroke();

      ctx.beginPath();
      ctx.arc(0, 0, radius*0.1, 0, 2*Math.PI);
      ctx.fillStyle = '#333';
      ctx.fill();
    }
    function drawTime(ctx, radius){
    var now = new Date();
    var hour = now.getHours();
    var minute = now.getMinutes();
    var second = now.getSeconds();
    //hour
    hour=hour%12;
    hour=(hour*Math.PI/6)+(minute*Math.PI/(6*60))+(second*Math.PI/(360*60));
    drawHand(ctx, hour, radius*0.5, radius*0.07);
    //minute
    minute=(minute*Math.PI/30)+(second*Math.PI/(30*60));
    drawHand(ctx, minute, radius*0.8, radius*0.07);
    // second
    second=(second*Math.PI/30);
    drawHand(ctx, second, radius*0.9, radius*0.02);
  }

  function drawHand(ctx, pos, length, width) {
    ctx.beginPath();
    ctx.lineWidth = width;
    ctx.lineCap = "round";
    ctx.moveTo(0,0);
    ctx.rotate(pos);
    ctx.lineTo(0, -length);
    ctx.stroke();
    ctx.rotate(-pos);
  }
    function drawClock() {
      drawFace(ctx, radius);
      drawNumbers(ctx, radius);
      drawTime(ctx, radius);
    }
  @endif
  });
 </script>
 <!-- /script for clock -->

@append