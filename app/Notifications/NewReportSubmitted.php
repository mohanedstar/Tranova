<?php

namespace App\Notifications;

use App\Models\WeeklyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReportSubmitted extends Notification
{
    use Queueable;

    protected $report;

    public function __construct(WeeklyReport $report)
    {
        $this->report = $report;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'new_report',
            'title' => 'تقرير جديد',
            'message' => "{$this->report->student->user->name} أرسل تقرير الأسبوع {$this->report->week_number}",
            'report_id' => $this->report->id,
            'student_id' => $this->report->student_id,
            'week_number' => $this->report->week_number,
            'link' => "/api/supervisor/reports",
        ];
    }
}
