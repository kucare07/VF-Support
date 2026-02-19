</div> </div> </div> </div> <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
    // ---------------------------------------------------------
    // 1. DataTables Initialization (เพิ่มส่วนแสดงรายการ/ค้นหา/แบ่งหน้า)
    // ---------------------------------------------------------
    $(document).ready(function() {
        $('.datatable').DataTable({
            "language": {
                "lengthMenu": "แสดง _MENU_ รายการ",
                "zeroRecords": "ไม่พบข้อมูล",
                "info": "แสดงหน้า _PAGE_ จาก _PAGES_",
                "infoEmpty": "ไม่มีข้อมูล",
                "infoFiltered": "(กรองจากทั้งหมด _MAX_ รายการ)",
                "search": "ค้นหา:",
                "paginate": {
                    "first": "หน้าแรก",
                    "last": "หน้าสุดท้าย",
                    "next": "ถัดไป",
                    "previous": "ก่อนหน้า"
                }
            },
            "order": [], // ปิด Default Sort (เพื่อให้เรียงตาม SQL Query ล่าสุดก่อน)
            "columnDefs": [
                { "orderable": false, "targets": 0 },  // ห้ามเรียงคอลัมน์แรก (Checkbox)
                { "orderable": false, "targets": -1 }  // ห้ามเรียงคอลัมน์สุดท้าย (ปุ่มจัดการ)
            ],
            "pageLength": 10, // จำนวนรายการต่อหน้าเริ่มต้น
            "lengthMenu": [[10, 25, 50, -1], [10, 25, 50, "ทั้งหมด"]]
        });
    });

    // ---------------------------------------------------------
    // 2. ฟังก์ชัน Toggle Sidebar
    // ---------------------------------------------------------
    const menuToggle = document.getElementById('menu-toggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.getElementById('sidebar-wrapper').classList.toggle('active');
        });
    }

    // ---------------------------------------------------------
    // 3. ระบบจัดการ Checkbox (Global Functions)
    // ---------------------------------------------------------

    // ฟังก์ชัน: เลือกทั้งหมด / ยกเลิกทั้งหมด
    function toggleAll(source) {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        let checkedCount = 0;
        
        checkboxes.forEach(cb => {
            // เช็คเฉพาะแถวที่มองเห็นอยู่ (เผื่อกรณี Search/Pagination)
            // ถ้าอยากให้เลือกข้ามหน้าได้ ต้องใช้ Logic ของ DataTables API เพิ่มเติม
            cb.checked = source.checked;
            if (cb.checked) checkedCount++;
        });
        
        updateBulkButton(checkedCount);
    }

    // ฟังก์ชัน: เช็คสถานะเมื่อติ๊กเลือกทีละรายการ
    function checkRow() {
        const checkboxes = document.querySelectorAll('.row-checkbox');
        const headerCheck = document.getElementById('checkAll');
        
        // ถ้ามีการติ๊กเลือกอย่างน้อย 1 อัน ให้แสดงปุ่ม
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        
        // ถ้าเลือกครบทุกช่องในหน้านั้น ให้ติ๊กถูกที่หัวตารางด้วย
        if(headerCheck) {
            const visibleCheckboxes = document.querySelectorAll('.row-checkbox'); // เลือกเฉพาะที่เรนเดอร์อยู่
            const allChecked = Array.from(visibleCheckboxes).every(cb => cb.checked);
            headerCheck.checked = (visibleCheckboxes.length > 0 && allChecked);
        }

        updateBulkButton(checkedCount);
    }

    // ฟังก์ชัน: แสดง/ซ่อน ปุ่ม "ลบที่เลือก"
    function updateBulkButton(count) {
        const btn = document.getElementById('bulkActionBtn');
        if (btn) {
            if (count > 0) {
                btn.style.display = 'inline-block';
                btn.innerHTML = `<i class="bi bi-trash"></i> ลบที่เลือก (${count})`;
                btn.classList.add('animate__animated', 'animate__fadeIn');
            } else {
                btn.style.display = 'none';
            }
        }
    }

    // ---------------------------------------------------------
    // 4. ฟังก์ชันลบหลายรายการ (Bulk Delete)
    // ---------------------------------------------------------
    function deleteSelected(url) {
        const checkboxes = document.querySelectorAll('.row-checkbox:checked');
        
        if (checkboxes.length === 0) {
            Swal.fire('แจ้งเตือน', 'กรุณาเลือกรายการที่ต้องการลบ', 'warning');
            return;
        }

        const ids = Array.from(checkboxes).map(cb => cb.value);

        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: `คุณต้องการลบข้อมูล ${ids.length} รายการที่เลือกใช่หรือไม่?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ใช่, ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;

                const inputAction = document.createElement('input');
                inputAction.type = 'hidden';
                inputAction.name = 'action';
                inputAction.value = 'bulk_delete';
                form.appendChild(inputAction);

                const inputIds = document.createElement('input');
                inputIds.type = 'hidden';
                inputIds.name = 'ids';
                inputIds.value = ids.join(',');
                form.appendChild(inputIds);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function confirmDelete(url, text = 'คุณต้องการลบข้อมูลนี้ใช่หรือไม่?') {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            // สร้าง Form เพื่อส่งข้อมูลแบบ POST (ปลอดภัยกว่า GET)
            const form = document.createElement('form');
            form.method = 'POST';
            
            // แยก URL และ Parameters
            const [actionUrl, queryString] = url.split('?');
            form.action = actionUrl;
            
            if (queryString) {
                const params = new URLSearchParams(queryString);
                for (const [key, value] of params) {
                    const hiddenField = document.createElement('input');
                    hiddenField.type = 'hidden';
                    hiddenField.name = key;
                    hiddenField.value = value;
                    form.appendChild(hiddenField);
                }
            }
            
            // แนบ CSRF Token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const csrfField = document.createElement('input');
            csrfField.type = 'hidden';
            csrfField.name = 'csrf_token';
            csrfField.value = csrfToken || '';
            form.appendChild(csrfField);

            document.body.appendChild(form);
            form.submit();
        }
    });
}
</script>

</body>
</html>