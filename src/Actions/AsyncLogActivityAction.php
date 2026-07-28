<?php

namespace Bale\Core\Actions;

use Bale\Core\Jobs\LogActivityJob;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Actions\LogActivityAction as SpatieLogActivityAction;

class AsyncLogActivityAction extends SpatieLogActivityAction
{
    protected function save(Model $activity): void
    {
        $attributes = $activity->getAttributes();

        // getAttributes() returns raw values — for 'collection' cast,
        // this is a JSON string. Use model accessors to get the
        // Collection, then toArray() for a plain array (no double-encode).
        $attributes['properties'] = $activity->properties?->toArray();
        $attributes['attribute_changes'] = $activity->attribute_changes?->toArray();

        LogActivityJob::dispatch($attributes);
    }
}
