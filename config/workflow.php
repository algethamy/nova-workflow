<?php


return [
    'workflows' => [

        'request' => [ // workflow name
            'model'       => \App\Request::class, //workflow applied to this model
            'column'      => 'status', // on this column
            'states'      => [ // the possible statuses
                'pending',
                'escalated',
                'approved',
                'rejected',
            ],

            'transitions' => [
                'Approve'  => [
                    'from'  => ['pending', 'escalated'],
                    'to'    => 'approved',
                    'event' => \App\Events\RequestApproved::class, // fire event
                    'style_classes' => 'bg-success text-20'
                ],
                'Escalate' => [
                    'from'         => ['pending'],
                    'to'           => 'escalated',
                ],
                'Reject'   => [
                    'from'         => ['pending', 'escalated'],
                    'to'           => 'rejected',
                    'with_reasons' => [ // to create a dropdown
                        'model'   => \App\RejectionReason::class,
                        'columns' => [
                            'id'    => 'id', // value of the option
                            'label' => 'title', // option label
                        ],
                    ],
                    'style_classes' => 'bg-danger text-20'
                ],

                'Back to My Employee' => [
                    'from' => ['escalated'],
                    'to'   => 'pending',
                    'with_reasons' => 'escalation_note', // display a free textarea to write the comment on the this column name
                ],
            ],
        ],
    ],
];