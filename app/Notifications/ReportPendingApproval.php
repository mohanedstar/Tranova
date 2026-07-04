<?php

namespace App\Notifications;

use App\Models\WeeklyReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportPendingApproval extends Notification
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
            'type' => 'report_pending',
            'title' => 'تقرير بانتظار الموافقة',
            'message' => "تقرير الأسبوع {$this->report->week_number} من {$this->report->student->user->name} بانتظار المراجعة",
            'report_id' => $this->report->id,
            'student_id' => $this->report->student_id,
            'week_number' => $this->report->week_number,
            'link' => "/api/admin/statistics",
        ];
    }
}
