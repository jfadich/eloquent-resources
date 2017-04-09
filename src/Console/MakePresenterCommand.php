<?php

namespace jfadich\JsonResponder\Console;

use Illuminate\Filesystem\Filesystem;

/**
 * Command to generate a new presenter class
 */
class MakePresenterCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'make:presenter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new Presenter class';

    /**
     * Set the base class then call the parent
     *
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        $this->parentClass = config('transformers.classes.presenter');

        parent::__construct($files);
    }
}