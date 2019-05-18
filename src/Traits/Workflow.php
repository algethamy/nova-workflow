<?php
namespace Cammac\Workflow\Traits;

trait Workflow
{
    public function update(array $attributes = [], array $options = [])
    {
        file_put_contents(storage_path('update.log'), now() . PHP_EOL, 8);

        return parent::update($attributes, $options);
    }
}