</div>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {

        // ---------------------------------------------------
        // 2. ตั้งค่า DataTables
        // ---------------------------------------------------
        $('.datatable').each(function() {
            $(this).DataTable({
                "language": {
                    "sSearch": "ค้นหา:",
                    "sLengthMenu": "แสดง _MENU_ รายการ",
                    "sInfo": "แสดง _START_ ถึง _END_ จาก _TOTAL_ รายการ",
                    "oPaginate": {
                        "sNext": "ถัดไป",
                        "sPrevious": "ก่อนหน้า"
                    }
                },
                "pageLength": 25,
                "responsive": true,
                "stateSave": true, // จำค่าหน้าล่าสุด
                "initComplete": function() {
                    var filterDiv = $(this.api().table().container()).find('.dataTables_filter');
                    if (filterDiv.find('.dt-reset-btn').length === 0) {
                        filterDiv.append('<button class="btn btn-sm btn-outline-danger ms-2 dt-reset-btn" type="button"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>');
                    }
                }
            });
        });

        // ปุ่ม Reset DataTables
        $(document).on('click', '.dt-reset-btn', function() {
            var table = $('.datatable').DataTable();
            table.state.clear();
            table.search('').columns().search('').draw();
            window.location.href = window.location.pathname;
        });

        // Select2
        $('.select2').select2({
            theme: 'bootstrap-5',
            width: '100%'
        });
        $(document).on('shown.bs.modal', function(e) {
            $(e.target).find('.select2').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $(e.target)
            });
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // ฟังก์ชัน Popup ยืนยันการลบ (ใช้ร่วมกันทุกหน้า)
    function confirmDelete(url, title = 'ยืนยันการลบ?') {
        Swal.fire({
            title: title,
            text: "ข้อมูลจะถูกลบออกจากระบบและไม่สามารถกู้คืนได้",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33', // สีแดงสำหรับปุ่มลบ
            cancelButtonColor: '#6c757d', // สีเทาสำหรับยกเลิก
            confirmButtonText: '<i class="bi bi-trash"></i> ยืนยันลบ',
            cancelButtonText: 'ยกเลิก',
            reverseButtons: true, // สลับปุ่มให้ยกเลิกอยู่ซ้าย (ป้องกันกดผิด)
            customClass: {
                popup: 'rounded-4 shadow-sm border-0' // ปรับขอบมนให้เหมือนรูปตัวอย่าง
            }
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        });
    }
</script>
</body>

</html>