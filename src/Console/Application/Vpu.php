<?php
/**
 * VisualPHPUnit
 *
 * VisualPHPUnit is a visual front-end for PHPUnit.
 *
 * PHP Version 5.6<
 *
 * @author    Johannes Skov Frandsen <localgod@heaven.dk>
 * @copyright 2011-2016 VisualPHPUnit
 * @license   http://opensource.org/licenses/BSD-3-Clause The BSD License
 * @link      https://github.com/VisualPHPUnit/VisualPHPUnit VisualPHPUnit
 */
namespace Visualphpunit\Console\Application;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Visualphpunit\Console\Command\Run;

/**
 * Visualphpunit consol application
 *
 * @author Johannes Skov Frandsen <localgod@heaven.dk>
 */
class Vpu extends Application
{

    /**
     * Gets the name of the command based on input.
     *
     * @param InputInterface $input
     *            The input interface
     *
     * @return string The command name
     */
    protected function getCommandName(InputInterface $input)
    {
        return 'vpu';
    }

    /**
     * Gets the default commands that should always be available.
     *
     * @return \Symfony\Component\Console\Command\Command[] An array of default Command instances
     */
    protected function getDefaultCommands()
    {
        $defaultCommands = parent::getDefaultCommands();
        $defaultCommands[] = new Run();
        return $defaultCommands;
    }

    /**
     * Overridden so that the application doesn't expect the command
     * name to be the first argument.
     *
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    public function getDefinition()
    {
        $inputDefinition = parent::getDefinition();
        // clear out the normal first argument, which is the command name
        $inputDefinition->setArguments();

        return $inputDefinition;
    }
}
