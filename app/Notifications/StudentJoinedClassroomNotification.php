<?php

namespace App\Notifications;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentJoinedClassroomNotification extends Notification
{
    use Queueable;

    public function __construct(public Classroom $classroom, public User $student) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Student joined classroom',
            'body' => sprintf('%s joined %s', $this->student->preferredName(), $this->classroom->name),
            'link' => route('classrooms.show', $this->classroom),
            'type' => 'student_joined',
            'related_id' => $this->classroom->id,
            'classroom_id' => $this->classroom->id,
            'student_id' => $this->student->id,
        ];
    }
}
