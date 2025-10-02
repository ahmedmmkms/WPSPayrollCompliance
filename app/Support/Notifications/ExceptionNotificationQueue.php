<?php

namespace App\Support\Notifications;

use App\Models\ExceptionNotification;
use App\Models\PayrollException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Lang;

class ExceptionNotificationQueue
{
    /**
     * Queue notifications for a status transition.
     */
    public function enqueueStatusChange(PayrollException $exception, ?string $from, ?string $to): void
    {
        if ($from === $to) {
            return;
        }

        $this->enqueue($exception, 'status_changed', [
            'status_from' => $from,
            'status_to' => $to,
        ]);
    }

    /**
     * Queue notifications for an assignment transition.
     */
    public function enqueueAssignmentChange(PayrollException $exception, ?string $from, ?string $to): void
    {
        if ($from === $to) {
            return;
        }

        $this->enqueue($exception, 'assignment_changed', [
            'assignee_from' => $from,
            'assignee_to' => $to,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function enqueue(PayrollException $exception, string $type, array $context): void
    {
        $exception->loadMissing(['payrollBatch', 'employee']);

        foreach ($this->locales() as $locale) {
            ExceptionNotification::query()->create([
                'payroll_exception_id' => $exception->getKey(),
                'type' => $type,
                'locale' => $locale,
                'title' => $this->resolveTitle($type, $locale),
                'body' => $this->resolveBody($type, $locale, $exception, $context),
                'payload' => $this->buildPayload($exception, $context),
                'queued_at' => Carbon::now(),
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    private function locales(): array
    {
        return ['en', 'ar'];
    }

    private function resolveTitle(string $type, string $locale): string
    {
        return (string) Lang::get("exceptions.notifications.{$type}.title", [], $locale);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveBody(string $type, string $locale, PayrollException $exception, array $context): string
    {
        $reference = $exception->payrollBatch?->reference ?? $exception->getKey();
        $employee = $exception->employee?->external_id;

        $parameters = [
            'reference' => $reference,
            'employee' => $employee ?: (string) Lang::get('exceptions.notifications.common.unknown_employee', [], $locale),
        ];

        if ($type === 'status_changed') {
            $parameters['current_status'] = $this->statusLabel($context['status_to'] ?? null, $locale);
            $parameters['previous_status'] = $this->statusLabel($context['status_from'] ?? null, $locale);

            return (string) Lang::get('exceptions.notifications.status_changed.body', $parameters, $locale);
        }

        $assignee = $context['assignee_to'] ?? null;

        if ($assignee) {
            $parameters['assignee'] = $assignee;

            return (string) Lang::get('exceptions.notifications.assignment_changed.body_assigned', $parameters, $locale);
        }

        $parameters['assignee'] = (string) Lang::get('exceptions.notifications.common.unassigned', [], $locale);

        return (string) Lang::get('exceptions.notifications.assignment_changed.body_unassigned', $parameters, $locale);
    }

    private function statusLabel(?string $status, string $locale): string
    {
        if (! $status) {
            return (string) Lang::get('exceptions.notifications.status_changed.none', [], $locale);
        }

        return (string) Lang::get("exceptions.statuses.{$status}", [], $locale);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function buildPayload(PayrollException $exception, array $context): array
    {
        return array_filter([
            'exception_id' => $exception->getKey(),
            'payroll_batch_id' => $exception->payroll_batch_id,
            'employee_id' => $exception->employee_id,
            'status_from' => $context['status_from'] ?? null,
            'status_to' => $context['status_to'] ?? null,
            'assignee_from' => $context['assignee_from'] ?? null,
            'assignee_to' => $context['assignee_to'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
