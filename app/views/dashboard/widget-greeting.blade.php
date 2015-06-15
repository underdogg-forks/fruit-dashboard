<li class="dashboard-widget well no-padding" data-id='{{ $id }}'  data-row="{{ $position['row'] }}" data-col="{{ $position['col'] }}" data-sizex="{{ $position['x'] }}" data-sizey="{{ $position['y'] }}">
	<a class='link-button' href='' data-toggle="modal" data-target='#widget-settings-{{ $id }}'><span class="gs-option-widgets"></span></a>
	<a href="{{ URL::route('connect.deletewidget', $id) }}"><span class="gs-close-widgets"></span></a>
	
	<h3 class='white-text textShadow'>Good <span class='greeting'></span>@if(isset(Auth::user()->name)), {{ Auth::user()->name }}@endif! </h3>
</li>

@section('pageModals')
	<!-- greetings settings -->
	
	@include('settings.widget-settings')

	<!-- /greetings settings -->
@append
