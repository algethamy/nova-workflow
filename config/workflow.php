<?php

use App\Request;

return [
    'workflows' => [

        'request' => [
            'model'       => Request::class,
            'column'      => 'status',
            'states'      => [
                'pending',
                'escalated',
                'approved',
                'rejected',
            ],
            'transitions' => [
                'Approve'  => [
                    'from'  => ['pending', 'escalated'],
                    'to'    => 'approved',
                    'event' => \App\Events\RequestApproved::class,
                ],
                'Escalate' => [
                    'from'         => ['pending'],
                    'to'           => 'escalated',
                    'with_reasons' => 'escalation_note', // the column name
                ],
                'Reject'   => [
                    'from'         => ['pending', 'escalated'],
                    'to'           => 'rejected',
                    'with_reasons' => [ // to create a dropdown
                        'model'   => \App\RejectionReason::class,
                        'columns' => [
                            'id'    => 'id',
                            'label' => 'title',
                        ],
                    ],
                ],


                'Back to My Employee' => [
                    'from' => ['escalated'],
                    'to'   => 'pending',
                ],
            ],
        ],
    ],
];