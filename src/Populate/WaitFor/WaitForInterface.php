<?php

declare(strict_types=1);

namespace Biblioverse\TypesenseBundle\Populate\WaitFor;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(WaitForInterface::TAG_NAME)]
interface WaitForInterface
{
    public const TAG_NAME = 'typesensebundle.wait_for_service';

    /**
     * @param ?callable(int $step, int $maxSteps,\Throwable $exception):void $callable Callback on each failed step
     *
     * @throws \RuntimeException
     */
    public function wait(int $maxSteps, ?callable $callable = null, int $sleepInSeconds = 1): void;

    public function getName(): string;
}
