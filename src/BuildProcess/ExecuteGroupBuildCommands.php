<?php

namespace Laravel\VaporCli\BuildProcess;

use Laravel\VaporCli\Helpers;
use Laravel\VaporCli\Manifest;
use Symfony\Component\Process\Process;

class ExecuteGroupBuildCommands
{
    use ParticipatesInBuildProcess;


    /**
     * The group name.
     */
    protected $group;

    /**
     * Create a new group builder.
     *
     * @param  string  $group
     * @return void
     */
    public function __construct($group)
    {
        $this->group = $group;
    }
    /**
     * Execute the build process step.
     *
     * @return void
     */
    public function __invoke()
    {
        Helpers::step('<bright>Executing Group '.$this->group.' Build Commands</>');

        foreach (Manifest::groupBuildCommands($this->group) as $command) {
            Helpers::step('<comment>Running Command</comment>: '.$command);

            $process = new Process($command, $this->appPath, null, null, null);

            $process->mustRun(function ($type, $line) {
                Helpers::write($line);
            });
        }
    }
}
