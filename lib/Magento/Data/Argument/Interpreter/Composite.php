<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Data\Argument\Interpreter;

use Magento\ObjectManager;
use Magento\Data\Argument\InterpreterInterface;

/**
 * Interpreter that aggregates named interpreters and delegates every evaluation to one of them
 */
class Composite implements InterpreterInterface
{
    /**
     * @var InterpreterInterface[] Format: array('<name>' => <instance>, ...)
     */
    private $interpreters;

    /**
     * Data key that holds name of an interpreter to be used for that data
     *
     * @var string
     */
    private $discriminator;

    /**
     * @param InterpreterInterface[] $interpreters
     * @param $discriminator
     * @throws \InvalidArgumentException
     */
    public function __construct(array $interpreters, $discriminator)
    {
        foreach ($interpreters as $interpreterName => $interpreterInstance) {
            if (!($interpreterInstance instanceof InterpreterInterface)) {
                throw new \InvalidArgumentException(
                    "Interpreter named '$interpreterName' is expected to be an argument interpreter instance."
                );
            }
        }
        $this->interpreters = $interpreters;
        $this->discriminator = $discriminator;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function evaluate(array $data)
    {
        if (!isset($data[$this->discriminator])) {
            throw new \InvalidArgumentException(sprintf(
                'Value for key "%s" is missing in the argument data.', $this->discriminator
            ));
        }
        $interpreterName = $data[$this->discriminator];
        unset($data[$this->discriminator]);
        $interpreter = $this->getInterpreter($interpreterName);
        return $interpreter->evaluate($data);
    }

    /**
     * Register interpreter instance under a given unique name
     *
     * @param string $name
     * @param InterpreterInterface $instance
     * @throws \InvalidArgumentException
     */
    public function addInterpreter($name, InterpreterInterface $instance)
    {
        if (isset($this->interpreters[$name])) {
            throw new \InvalidArgumentException("Argument interpreter named '$name' has already been defined.");
        }
        $this->interpreters[$name] = $instance;
    }

    /**
     * Retrieve interpreter instance by its unique name
     *
     * @param string $name
     * @return InterpreterInterface
     * @throws \InvalidArgumentException
     */
    protected function getInterpreter($name)
    {
        if (!isset($this->interpreters[$name])) {
            throw new \InvalidArgumentException("Argument interpreter named '$name' has not been defined.");
        }
        return $this->interpreters[$name];
    }
}
