<?php

namespace Cammac\Workflow\Http\Controllers;

class WorkflowController
{
    public function __invoke($workflow, $id, $transaction, $reason = null)
    {
        $workflow = $this->getWorkflowSetting($workflow);

        /** @var \Illuminate\Database\Eloquent\Model $model */
        $model = app($workflow['model'])->findOrFail($id);

        $stateMachine = new \SM\StateMachine\StateMachine($model, $workflow);

        $transaction = $this->cleanTransaction($transaction);

        $stateMachine->apply($transaction);

        try {
            \DB::transaction(function () use ($model, $workflow, $transaction, $reason) {
                if (!empty($reason)) {
                    if (!is_array($reason_field = data_get($workflow, "transitions.$transaction.with_reasons"))) {
                        $model->update([
                            $reason_field => $reason,
                        ]);
                    } else {

                        /** @var \Illuminate\Database\Eloquent\Model $reason_model */
                        $reason_model = app(data_get($workflow, "transitions.$transaction.with_reasons.model"));

                        $model->update([
                            $reason_model->getForeignKey() => $reason,
                        ]);
                    }
                } else {
                    $model->save();
                }

                $event = data_get($workflow, "transitions.$transaction.event", false);

                if ($event) {
                    event(new $event($model));
                }
            });
        } catch (\Exception $exception) {
            return response()->json($exception->getMessage(), 400);
        }
    }

    /**
     * @param $workflow
     * @return array|\Illuminate\Config\Repository|mixed
     */
    protected function getWorkflowSetting($workflow)
    {
        $workflow = collect(config("workflow.workflows.$workflow"));
        $workflow = $workflow->merge(['property_path' => $workflow['column']]);

        return $workflow->toArray();
    }

    private function cleanTransaction($transaction)
    {
        return str_replace('_', ' ', $transaction);
    }
}