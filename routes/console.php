<?php

use Illuminate\Support\Facades\Schedule;

/*
| Zamanlanmış görevler. Sunucuda her dakika şu cron satırı çalışmalı:
|   * * * * * cd /proje/yolu && php artisan schedule:run >> /dev/null 2>&1
*/

// Gece yedeği: veritabanı + hasta belgeleri (config/backup.php).
Schedule::command('backup:clean')->dailyAt('01:00');
Schedule::command('backup:run')->dailyAt('01:30')->onOneServer();

// Son yedek 1 günden eskiyse veya alan dolduysa yöneticiye bildirim gider.
Schedule::command('backup:monitor')->dailyAt('08:00');
