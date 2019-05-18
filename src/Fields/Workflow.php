<?php

namespace Cammac\Workflow\Fields;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Laravel\Nova\ResourceTool;

class Workflow extends ResourceTool
{
    private $hide = false;

    public $SM;
    /**
     * Workflow constructor.
     * @param string $workflow_name
     */
    public function __construct(string $workflow_name)
    {
        parent::__construct();

        try {
            $object = new \Cammac\Workflow\Workflow($workflow_name, array_last(request()->segments()));

            $array = $object->SM->getPossibleTransitions();

            $this->fetch_reasons($object->workflow, $array);

            $this->withMeta([
                'workflow'     => $workflow_name,
                'transactions' => $this->get_transitions($array),
                'styles'       => $this->get_styles($object->workflow),
                'actions'      => $this->get_actions($object->workflow, $array),
            ]);
        } catch (ModelNotFoundException $e) {
        }
    }

    /**
     * Get the displayable name of the resource tool.
     *
     * @return string
     */
    public function name()
    {
        return 'الإجراءات';
    }

    /**
     * Get the component name for the resource tool.
     *
     * @return string
     */
    public function component()
    {
        return 'workflow';
    }

    /**
     * Prepare the panel for JSON serialization.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return array_merge([
            'component'   => 'panel',
            'name'        => $this->name,
            'showToolbar' => $this->showToolbar,
        ], $this->element->meta());
    }

    /**
     * @param array $workflow
     * @param array $array
     */
    protected function fetch_reasons($workflow, array $array)
    {
        collect($workflow['transitions'])->filter(function ($trans, $trans_label) use ($array) {
            return in_array($trans_label, $array) && array_key_exists('with_reasons', $trans);
        })->each(function ($trans, $trans_label) {
            if (!is_array($trans['with_reasons'])) {
                return $this->setReasons([
                    $trans_label => 'textarea',
                ]);
            }

            /** @var \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder $model */
            $model = app($trans['with_reasons']['model']);

            $columns = $trans['with_reasons']['columns'];

            $this->setReasons([
                $trans_label => $model
                    ->get(array_values($columns))
                    ->groupBy($columns['id'])
                    ->map(function (Collection $rows) use ($columns) {
                        return data_get($rows, "0.$columns[label]");
                    })
                    ->toArray(),
            ]);
        });
    }

    public function setReasons($reasons)
    {
        $this->withMeta([
            "reasons" => collect(data_get($this, 'element.meta.reasons', []))->merge($reasons),
        ]);
    }

    private function get_transitions(array $array)
    {
        $transactions = [];
        foreach ($array as $trans) {
            $transactions[$trans] = false;
        }

        return $transactions;
    }

    private function get_styles(Collection $workflow)
    {
        return collect($workflow->get('transitions'))->reject(function ($trans) {
            return !isset($trans['style_classes']);
        })->map(function ($trans) {
            return $trans['style_classes'];
        })->toArray();
    }

    private function get_actions(Collection $workflow, array $array)
    {
        return collect($workflow->get('transitions'))->filter(function ($trans, $trans_label) use ($array) {
                return in_array($trans_label, $array) && isset($trans['action']);
            })
            ->map(function ($trans) {
                /** @var \Laravel\Nova\Actions\Action $action */
                $action = app($trans['action']);

                return $action->jsonSerialize();
            })
            ->toArray();
    }
}
