<?php
/* ============================================================
   VST — "Work With Us" settings (kept apart from config.php so
   updating this never touches the database password).
   ============================================================ */
// CVs are stored OUTSIDE the public website folder, so no URL can ever reach them.
// On HostGator: public_html/includes/../../vst-private/cv/  =  /home/<user>/vst-private/cv/
define('UPLOAD_DIR_CV', dirname(__DIR__, 2) . '/vst-private/cv/');
define('MAX_CV_BYTES', 5 * 1024 * 1024);                        // 5 MB max per CV
define('CAREERS_NOTIFY_EMAIL', 'vstranslation2025@gmail.com');  // alert for each application ('' = off)
define('MAX_APPLICATIONS_PER_HOUR', 5);                         // per IP address (anti-spam)

/* Allowed CV formats: extension => accepted MIME types (checked on the real file content) */
function cv_allowed_types(): array {
    return [
        'pdf'  => ['application/pdf'],
        'doc'  => ['application/msword', 'application/x-ole-storage', 'application/CDFV2', 'application/vnd.ms-office'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
    ];
}
