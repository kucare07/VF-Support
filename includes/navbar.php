
<?php
// หา Base Path อัตโนมัติ เพื่อป้องกันปัญหาเรื่อง Folder
$path = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', dirname(__DIR__)));
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
  <div class="container">
    <a class="navbar-brand" href="<?php echo $path; ?>/modules/helpdesk/index.php">
        <i class="bi bi-hdd-network"></i> IT Support
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="<?php echo $path; ?>/modules/helpdesk/index.php">รายการงาน (Dashboard)</a>
        </li>
        <li class="nav-item">
          <a class="nav-link btn btn-light text-primary ms-2 px-3" href="<?php echo $path; ?>/modules/helpdesk/create.php">
             + แจ้งปัญหา
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>