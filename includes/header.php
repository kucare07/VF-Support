<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>IT Support System</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" />

    <style>
        :root {
            --primary-color: #4e73df;
            --primary-gradient: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            --sidebar-width: 230px; /* ต้องตรงกับใน sidebar.php */
            --top-navbar-height: 60px;
            --text-dark: #2c3e50;
            --text-gray: #5a6573;
        }

        body { 
            font-family: 'Sarabun', sans-serif; 
            background-color: #f3f6f9; 
            font-size: 0.9rem; /* ขนาดมาตรฐาน */
            color: var(--text-gray);
            overflow-x: hidden; /* ป้องกันหน้าเว็บเลื่อนซ้ายขวา */
        }

        /* --- Layout Styles --- */
        #wrapper {
            display: flex;
            width: 100%;
            overflow: hidden;
        }

        /* ส่วนเนื้อหาหลัก (Page Content) */
        #page-content-wrapper {
            width: 100%;
            min-height: 100vh;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
        }

        /* --- Navbar --- */
        .main-navbar {
            height: var(--top-navbar-height);
            background: #fff;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 0 1.5rem; 
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 999;
            box-shadow: 0 2px 15px rgba(0,0,0,0.03); 
        }

        .main-navbar span.fw-bold {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 700;
        }

        /* --- Content Area --- */
        .main-content-scroll {
            padding: 0 !important;
            flex: 1;
            overflow-x: hidden; /* ซ่อน Scrollbar แนวนอนของทั้งหน้า */
        }

        /* --- Component Styles --- */
        .card {
            border: none !important;
            border-radius: 12px !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03) !important;
            margin-bottom: 1rem;
        }
        
        .card-header {
            background-color: #fff !important;
            border-bottom: 1px solid #f0f0f0 !important;
            padding: 15px 20px !important;
            border-radius: 12px 12px 0 0 !important;
        }

        /* --- Tables (ปรับแต่งให้ไม่ล้นจอ) --- */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch; /* ลื่นขึ้นบนมือถือ */
        }
        
        .table thead th {
            background-color: #f8f9fc !important;
            color: #6e707e;
            font-weight: 600;
            font-size: 0.85rem;
            white-space: nowrap; /* ห้ามตัดบรรทัดหัวตาราง */
        }
        
        .table tbody td {
            vertical-align: middle;
            white-space: nowrap; /* ค่าเริ่มต้นห้ามตัดบรรทัด */
            max-width: 300px; /* ถ้าเกินนี้ให้ตัด ... */
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* --- DataTables Custom --- */
        .dataTables_wrapper .row:first-child, 
        .dataTables_wrapper .row:last-child {
            padding: 10px 15px;
            margin: 0;
        }
        .dataTables_filter input {
            border-radius: 20px;
            padding: 4px 15px;
            border: 1px solid #e0e0e0;
            min-width: 200px;
        }

        /* --- ✅ Responsive Rules (หัวใจสำคัญ) --- */
        
        /* กรณีหน้าจอ PC (กว้างกว่า 768px) */
        @media (min-width: 768px) {
            #page-content-wrapper {
                margin-left: var(--sidebar-width); /* เว้นที่ให้ Sidebar */
            }
            .main-navbar { padding: 0 2rem; }
            .main-content-scroll { padding: 0 !important; }
        }

        /* กรณีหน้าจอ Mobile / Tablet (เล็กกว่า 768px) */
        @media (max-width: 767.98px) {
            #page-content-wrapper {
                margin-left: 0; /* เต็มจอ */
                width: 100%;
            }
            
            .main-navbar {
                padding: 0 1rem;
            }
            
            .main-content-scroll {
               padding: 0 !important; /* ลดระยะขอบในมือถือ */
            }

            /* ปรับขนาดตัวหนังสือในมือถือให้พอดี */
            body { font-size: 0.85rem; }
            h1, h2, h3, h4, h5, h6 { font-size: 90%; }
            
            /* ซ่อนบางคอลัมน์ในตารางถ้าจำเป็น หรือให้ scroll เอา */
            .card-header { padding: 12px 15px !important; }
            
            /* ปรับปุ่มให้กดง่ายขึ้น */
            .btn-sm {
                padding: 0.4rem 0.8rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>
    <div id="wrapper">