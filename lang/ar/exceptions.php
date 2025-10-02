<?php

return [
    'sla' => [
        'label' => 'مؤقت مستوى الخدمة',
        'due_in' => 'متبقي :time',
        'due_now' => 'يستحق الآن',
        'just_overdue' => 'تجاوز الاستحقاق للتو',
        'overdue' => 'تجاوز الاستحقاق بمقدار :time',
        'no_due' => 'لا يوجد موعد استحقاق',
        'resolved' => 'تم الحل',
    ],
    'statuses' => [
        'open' => 'مفتوحة',
        'in_review' => 'قيد المراجعة',
        'resolved' => 'تم الحل',
    ],
    'activity' => [
        'heading' => 'سجل النشاط',
        'events' => 'الأحداث',
        'empty' => 'لا توجد أنشطة حتى الآن.',
        'event' => 'الحدث',
        'occurred_at' => 'وقت الحدوث',
        'failures' => 'الإخفاقات',
        'rule_sets' => 'مجموعات القواعد',
        'template' => 'القالب',
        'available_at' => 'وقت التوفر',
        'unknown' => 'حدث غير معروف',
    ],
    'notifications' => [
        'common' => [
            'unknown_employee' => 'موظف غير معروف',
            'unassigned' => 'بدون تعيين',
        ],
        'status_changed' => [
            'title' => 'تم تحديث حالة الاستثناء',
            'body' => 'تغيّرت الحالة من :previous_status إلى :current_status للموظف :employee في الدفعة :reference.',
            'none' => 'غير محددة',
        ],
        'assignment_changed' => [
            'title' => 'تحديث مسؤول الاستثناء',
            'body_assigned' => 'تم تعيين :assignee على الاستثناء للموظف :employee في الدفعة :reference.',
            'body_unassigned' => 'تم إلغاء التعيين للموظف :employee في الدفعة :reference.',
        ],
    ],
];
