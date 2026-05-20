@extends('layouts.vali')

@section('title', 'Campaign Calendar')
@section('page_icon', 'fa-calendar')

@section('content')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<style>
    .fc-event {
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 3px;
    }
</style>

<div class="row">
    <div class="col-md-12">
        <div class="tile">
            <div class="d-flex justify-content-between mb-3">
                <h3 class="tile-title">Campaign Calendar</h3>
                <div>
                    <a href="{{ route('campaigns.index') }}" class="btn btn-secondary mr-2"><i class="fa fa-list"></i> List View</a>
                    <a href="{{ route('campaigns.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> New Campaign</a>
                </div>
            </div>
            
            <div id='calendar'></div>
        </div>
    </div>
</div>

<!-- Event Details Modal -->
<div class="modal fade" id="eventModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="eventTitle">Campaign Details</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p><strong>Status:</strong> <span id="eventStatus" class="badge"></span></p>
        <p><strong>Target Service:</strong> <span id="eventService"></span></p>
        <p><strong>Target Location:</strong> <span id="eventLocation"></span></p>
        <hr>
        <p><strong>Message:</strong></p>
        <div class="alert alert-info" id="eventMessage" style="white-space: pre-wrap;"></div>
      </div>
      <div class="modal-footer">
        <a href="#" id="editEventBtn" class="btn btn-primary">Edit Campaign</a>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listMonth'
        },
        events: '/campaigns/events',
        eventClick: function(info) {
            $('#eventTitle').text(info.event.title);
            
            let status = info.event.extendedProps.status;
            let badgeClass = 'badge-info';
            if (status === 'Completed') badgeClass = 'badge-success';
            if (status === 'Processing') badgeClass = 'badge-warning';
            if (status === 'Cancelled') badgeClass = 'badge-danger';
            
            $('#eventStatus').text(status).removeClass().addClass('badge ' + badgeClass);
            $('#eventService').text(info.event.extendedProps.target_service);
            $('#eventLocation').text(info.event.extendedProps.target_location);
            $('#eventMessage').text(info.event.extendedProps.message);
            
            $('#editEventBtn').attr('href', '/campaigns/' + info.event.id + '/edit');
            
            $('#eventModal').modal('show');
        }
    });
    calendar.render();
});
</script>
@endpush
