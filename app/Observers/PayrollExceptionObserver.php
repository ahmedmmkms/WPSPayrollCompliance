<?php

namespace App\Observers;

use App\Models\PayrollException;
use App\Support\Notifications\ExceptionNotificationQueue;

class PayrollExceptionObserver
{
    public function __construct(private readonly ExceptionNotificationQueue $queue) {}

    public function updated(PayrollException $exception): void
    {
        $originalStatus = $exception->getOriginal('status');
        $currentStatus = $exception->status;

        $statusChanged = $originalStatus !== $currentStatus;

        $originalAssignee = $exception->getOriginal('assigned_to');
        $currentAssignee = $exception->assigned_to;

        $assigneeChanged = $originalAssignee !== $currentAssignee;

        if (! $statusChanged && ! $assigneeChanged) {
            return;
        }

        $exception->loadMissing(['payrollBatch', 'employee']);

        if ($statusChanged) {
            $this->queue->enqueueStatusChange($exception, $originalStatus, $currentStatus);
        }

        if ($assigneeChanged) {
            $this->queue->enqueueAssignmentChange($exception, $originalAssignee, $currentAssignee);
        }
    }
}
