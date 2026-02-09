<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';
?>

<link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css' rel='stylesheet' />
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js'></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/locales/th.js"></script>

<?php require_once '../../includes/sidebar.php'; ?>

<div id="page-content-wrapper">
    <nav class="main-navbar">
        
        <span class="fw-bold fs-5 text-dark">ปฏิทินงาน (Work Calendar)</span>
        <div class="ms-auto text-muted small"><i class="bi bi-calendar3 me-1"></i> ตารางงาน (Schedule)</div>
    </nav>

    <div class="main-content-scroll">
        <div class="container-fluid p-0">
            
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    
                    <div class="d-flex flex-wrap gap-2 mb-3 small border-bottom pb-2">
                        <span class="badge bg-warning text-dark">● งานใหม่ (New)</span>
                        <span class="badge bg-primary">● กำลังซ่อม (Assigned)</span>
                        <span class="badge bg-danger">● รออะไหล่ (Pending)</span>
                        <span class="badge bg-success">● เสร็จสิ้น (Resolved)</span>
                        <span class="badge bg-purple text-white" style="background-color: #6f42c1;">● กำหนดคืน (Return Due)</span>
                    </div>

                    <div id='calendar'></div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white py-2">
                <h6 class="modal-title fw-bold" id="modalTitle">รายละเอียด (Details)</h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <div id="modalStatus" class="mb-2"></div>
                <h6 class="fw-bold mb-1" id="modalDesc"></h6>
                <p class="text-muted small mb-3" id="modalWho"></p>
                <div class="d-grid">
                    <a href="#" id="btnLink" class="btn btn-sm btn-outline-primary">ไปที่รายการนี้ (Go to Item)</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h6 class="modal-title fw-bold"><i class="bi bi-plus-circle me-1"></i> เพิ่มรายการ (Add New)</h6>
                <button type="button" class="btn-close btn-close-white btn-sm" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-center small text-muted mb-3">วันที่เลือก (Selected Date): <strong id="selectedDateText" class="text-dark"></strong></p>
                <div class="d-grid gap-2">
                    <button onclick="goToPage('../helpdesk/index.php')" class="btn btn-sm btn-outline-primary text-start">
                        <i class="bi bi-ticket-perforated me-2"></i> แจ้งซ่อม (New Ticket)
                    </button>
                    <button onclick="goToPage('../pm/index.php')" class="btn btn-sm btn-outline-warning text-dark text-start">
                        <i class="bi bi-calendar-check me-2"></i> แผน PM (New PM Plan)
                    </button>
                    <button onclick="goToPage('../borrow/index.php')" class="btn btn-sm btn-outline-info text-dark text-start">
                        <i class="bi bi-arrow-left-right me-2"></i> ยืม-คืน (Borrow/Return)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* CSS ปรับแต่งปฏิทินให้สวยงามและ Compact */
    .fc-header-toolbar { margin-bottom: 10px !important; }
    .fc-toolbar-title { font-size: 1.1rem !important; font-weight: bold; }
    .fc-button { font-size: 0.75rem !important; padding: 0.2rem 0.5rem !important; }
    .fc-col-header-cell-cushion { font-size: 0.85rem; padding: 4px 0 !important; text-decoration: none; color: #555; }
    .fc-daygrid-day-number { font-size: 0.8rem; padding: 2px 4px !important; text-decoration: none; color: #333; }
    .fc-event { 
        font-size: 0.75rem !important; 
        padding: 2px 4px !important; 
        margin-bottom: 2px !important; 
        border-radius: 3px;
        cursor: pointer;
        transition: transform 0.1s;
    }
    .fc-event:hover { transform: scale(1.02); }
    .fc-daygrid-day-frame { min-height: 90px !important; cursor: pointer; } /* เพิ่ม cursor ให้รู้ว่าคลิกได้ */
    .fc-daygrid-day:hover { background-color: rgba(0,0,0,0.02); } /* Highlight วันที่เอาเมาส์ชี้ */
</style>

<script>
    var calendar;
    var actionModal;
    var selectedDateStr = '';

    document.addEventListener('DOMContentLoaded', function() {
        var calendarEl = document.getElementById('calendar');
        var eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
        actionModal = new bootstrap.Modal(document.getElementById('actionModal'));

        calendar = new FullCalendar.Calendar(calendarEl, {
            locale: 'th',
            initialView: 'dayGridMonth',
            height: 'auto',
            contentHeight: 650,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,listWeek'
            },
            events: 'fetch_events.php',
            
            // คลิกที่ Event เพื่อดูรายละเอียด
            eventClick: function(info) {
                var props = info.event.extendedProps;
                
                document.getElementById('modalTitle').innerText = (props.type === 'ticket') ? 'Ticket Info' : 'Borrow Info';
                document.getElementById('modalDesc').innerText = props.detail;
                document.getElementById('modalWho').innerText = 'โดย (By): ' + props.who;
                document.getElementById('modalStatus').innerHTML = `<span class="badge" style="background-color:${info.event.backgroundColor}">${props.status}</span>`;

                var btnLink = document.getElementById('btnLink');
                if (props.type === 'ticket') {
                    btnLink.href = '../helpdesk/index.php'; 
                } else {
                    btnLink.href = '../borrow/index.php';
                }

                eventModal.show();
            },

            // คลิกที่วันที่ว่างๆ เพื่อเพิ่มรายการใหม่
            dateClick: function(info) {
                selectedDateStr = info.dateStr; // เก็บวันที่ที่คลิกไว้ (YYYY-MM-DD)
                
                // แปลงวันที่เป็นรูปแบบไทยสวยๆ
                var dateObj = new Date(info.dateStr);
                var options = { year: 'numeric', month: 'long', day: 'numeric' };
                document.getElementById('selectedDateText').innerText = dateObj.toLocaleDateString('th-TH', options);
                
                actionModal.show();
            }
        });

        calendar.render();
        
        // แก้ไข Bug Sidebar Toggle แล้ว Calendar ไม่ Resize
        document.getElementById('menu-toggle').addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('sidebar-wrapper').classList.toggle('active');
            setTimeout(function() { calendar.updateSize(); }, 300);
        });
    });

    // ฟังก์ชันไปหน้าต่างๆ (อนาคตสามารถส่งค่า ?date=... ไปด้วยได้)
    function goToPage(url) {
        // ตัวอย่างการส่งค่าวันที่ไป (ถ้าหน้าปลายทางรองรับ)
        // window.location.href = url + '?prefill_date=' + selectedDateStr;
        
        // ตอนนี้ลิงก์ไปหน้าปกติก่อน
        window.location.href = url;
    }
</script>

<?php require_once '../../includes/footer.php'; ?>