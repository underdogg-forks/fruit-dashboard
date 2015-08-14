@extends('meta.base-user')

  @section('pageTitle')
    Plans and Pricing
  @stop

  @section('pageStylesheet')
  @stop

  @section('pageContent')
  <div class="vertical-center">
    <div class="container">           
      <div class="row">
        <div class="col-md-4">
          <div class="panel panel-default panel-transparent">
            <div class="panel-body text-center">
              <h1>Contribute</h1>
              <p class="lead">
                Free
              </p>
              <ul class="list-group">
                <li class="list-group-item">You host your software</li>
                <li class="list-group-item">Access and customize each functionality</li>
                <li class="list-group-item">Community support</li>
              </ul>
              <p><small>Fork us on </small><span class="fa fa-github"></span><small> GitHub, and create your own instance.</small></p>
            </div> <!-- /.panel-body -->
          </div> <!-- /.panel -->
        </div> <!-- /.col-md-4 -->

        @foreach ($plans as $plan)
        <div class="col-md-4">
          <div class="panel panel-default panel-transparent">
            <div class="panel-body text-center">
              @if(Auth::user()->subscription->plan->id == $plan->id)
              <div class="ribbon"><span>Active</span></div>
              @endif
              <h1>{{ $plan->name }}</h1>
              <p class="lead">
                @if ($plan->amount == 0)
                Free
                @else
                <span class="fa fa-eur"></span>
                {{ $plan->amount }} / month
                @endif
              </p>
              {{ $plan->description }}
              @if ($plan->isFree())
                @if (Auth::user()->subscription->getTrialInfo()['enabled'])
                  @if (Auth::user()->subscription->getTrialInfo()['daysRemaining'] > 0)
                    <p>
                      Your trial ends in 
                      <strong>
                        {{ Auth::user()->subscription->getTrialInfo()['daysRemaining'] }} day(s)
                      </strong>
                      <small class="text-muted">on {{ Auth::user()->subscription->getTrialInfo()['endDate']->format('Y-m-d')  }}.</small>
                    </p>
                  @else
                    <p>
                      Your trial has ended on {{ Auth::user()->subscription->getTrialInfo()['endDate']->format('Y-m-d')  }}. Change your plan to use the premium features.
                    </p>
                  @endif
                @endif
              @endif
            </div> <!-- /.panel-body -->
          </div> <!-- /.panel -->
        </div> <!-- /.col-md-4 -->
        @endforeach

      </div> <!-- /.row -->
      <div class="row">
        <div class="col-md-4">
          <div class="panel panel-default panel-transparent">
            <div class="panel-body">
              <a href="https://github.com/tryfruit/fruit-dashboard/" class="btn btn-success btn-block" onclick="trackAll('lazy', {'en': 'Clicked on contribute plan', 'el': '{{ Auth::user()->email }}', });">Click here to access the repository</a>
            </div> <!-- /.panel-body -->
          </div> <!-- /.panel -->
        </div> <!-- /.col-md-4 -->

        @foreach ($plans as $plan)
          <div class="col-md-4">
            <div class="panel panel-default panel-transparent">
              <div class="panel-body">
                @if(Auth::user()->subscription->plan->id == $plan->id)
                <button class="btn btn-block btn-info">You're currently on this plan. :)</button>
                @else
                <a href="{{ route('payment.subscribe', $plan->id) }}" class="btn btn-success btn-block">Subscribe</a>
                @endif
              </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
          </div> <!-- /.col-md-4 -->
        @endforeach
      </div> <!-- /.row -->
    </div> <!-- /.container -->
  </div> <!-- /.vertical-center -->
  @stop

  @section('pageScripts')
  @stop