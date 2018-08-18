<?php namespace NLMenke\DeployVersion;

use Illuminate\Support\Facades\Facade;

class DeployVersionFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return DeployVersionService::class;
    }
}
