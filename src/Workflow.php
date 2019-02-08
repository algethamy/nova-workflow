<?php

namespace Cammac\Workflow;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Laravel\Nova\ResourceTool;

class Workflow extends ResourceTool
{
    private $hide = false;

    /**
     * Workflow constructor.
     * @param string $workflow_name
     */
    public function __construct(string $workflow_name)
    {
        parent::__construct();

        try {
            $workflow = collect(config("workflow.workflows." . $workflow_name));

            $workflow = $workflow->merge(['property_path' => $workflow['column']]);

            /** @var \Illuminate\Database\Eloquent\Model $model */
            $model = app($workflow['model'])->findOrFail(array_last(request()->segments()));

            $stateMachine = new \SM\StateMachine\StateMachine($model, $workflow->toArray());

            $array = $stateMachine->getPossibleTransitions();

            $this->fetch_reasons($workflow, $array);

            $transactions = [];
            foreach ($array as $trans) {
                $transactions[$trans] = false;
            }

            $this->withMeta([
                'workflow'     => $workflow_name,
                'transactions' => $transactions,
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
        return 'Workflow';
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
}
