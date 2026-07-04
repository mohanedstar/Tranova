<?php

namespace App\Notifications;

use App\Models\InternshipRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class EvaluationReady extends Notification
{
    use Queueable;

    protected $record;

    public function __construct(InternshipRecord $record)
    {
        $this->record = $record;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'evaluation_ready',
            'title' => 'تقييم نهائي جاهز',
            'message' => "التقييم النهائي للطالب {$this->record->student->user->name} جاهز للمراجعة",
            'record_id' => $this->record->id,
            'student_id' => $this->record->student_id,
            'final_grade' => $this->record->final_grade,
            'link' => "/api/admin/evaluations/final",
        ];
    }
}
