<!-- if not on dashboard display the home button -->
@if (!Request::is('dashboard'))
    <div class="position-tl drop-shadow z-top">
      <a href="{{ route('dashboard.dashboard') }}" alt="Dashboard" title="Dashboard">
        <span class="fa fa-home fa-2x fa-inverse color-hovered"></span>
      </a>
    </div>
@endif

<!-- add new widget button -->
<div class="position-bl drop-shadow z-top">
  <a href="{{ URL::route('widget.add') }}" alt="New widget" title="New widget">
    <span class="fa fa-plus-circle fa-2x fa-inverse color-hovered"></span>
  </a>
</div>


<!-- dropdown menu icon -->
<div class="btn-group position-tr z-top cursor-pointer">

    <span class="dropdown-icon fa fa-2x fa-cog fa-inverse color-hovered drop-shadow" alt="Settings" title="Settings" data-toggle="dropdown" aria-expanded="true"></span>

    <!-- dropdown menu elements -->
    <ul class="dropdown-menu pull-right" role="menu">
        <li>
            <a href="{{ URL::route('widget.add') }}">
                <span class="fa fa-plus-circle"></span> New Widget
            </a>
        </li>
        <li>
            <a href="{{ URL::route('settings.settings') }}">
                <span class="fa fa-cogs"></span> Settings
            </a>
        </li>
        <li>
            <a href="{{ URL::route('dashboard.manage') }}">
                <span class="fa fa-list"></span> Manage Dashboards
            </a>
        </li>
        @if (Request::is('dashboard'))
            <li>
                <a href="#" onclick="startTour();">
                    <span class="fa fa-question"></span> Take tour
                </a>
            </li>
        @endif
        <li>
            <a href="https://fruitdashboard.uservoice.com/" target="blank">
                <span class="fa fa-bullhorn"></span> Feedback
            </a>
        </li>
        <li>
            <a target="_blank" href="https://github.com/tryfruit/fruit-dashboard/">
                <span class="fa fa-puzzle-piece"></span> Contribute
            </a>
        </li>
        <li>
            <a href="{{ URL::route('payment.plans') }}">
                <span class="fa fa-tag"></span> Plans
            </a>
        </li>
        <li>
            <a href="{{ URL::route('auth.signout') }}">
                <span class="fa fa-sign-out"></span> Sign out
            </a>
        </li>
    </ul>

</div> <!-- /.btn-group -->

<!-- Display the Remaining Days counter -->
@if (Auth::user()->subscription->getSubscriptionInfo()['TD'])
    <a href="{{ route('payment.plans') }}"
       class="position-br drop-shadow z-top no-underline"
       data-toggle="tooltip"
       data-placement="left"
       title=
        "@if (Auth::user()->subscription->getSubscriptionInfo()['TS'] == 'active')
            Your trial period will end on <br> {{ Auth::user()->subscription->getSubscriptionInfo()['trialEndDate']->format('Y-m-d') }} <br> Click here to change your Plan.
        @else
            Your trial period ended on <br> {{ Auth::user()->subscription->getSubscriptionInfo()['trialEndDate']->format('Y-m-d') }} <br> Click here to change your Plan.
        @endif">

        <span class="label @if (Auth::user()->subscription->getSubscriptionInfo()['trialDaysRemaining'] < SiteConstants::getTrialPeriodInDays() / 2) label-danger @else label-warning @endif label-as-badge valign-middle">
            {{ Auth::user()->subscription->getSubscriptionInfo()['trialDaysRemaining'] }}
        </span>
    </a>
@endif

@section('pageScripts')

{{-- Initialize the tooltip for Remaining Days counter --}}
<script type="text/javascript">
    $(function () {
      $('[data-toggle="tooltip"]').tooltip({
        html: true
      })
    })
</script>

@append